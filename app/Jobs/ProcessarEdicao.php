<?php

namespace App\Jobs;

use App\Models\VideoJob;
use App\Video\Claude\LimiteDoClaude;
use App\Video\Edicao;
use App\Video\FalhaDeFerramenta;
use App\Video\Nuvem;
use App\Video\Plano\PlanoInvalido;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Executa uma ação do estúdio numa edição: editar (do zero ou retomando),
 * ajustar (pedido em texto), rerenderizar (plano editado à mão) ou finalizar.
 *
 * Proteções de servidor:
 *  - roda na conexão "video" (retry_after de 4 h) com worker próprio — não
 *    trava convites/avisos nem é processada em dobro;
 *  - trava por edição (duas ações na mesma edição nunca rodam juntas);
 *  - limite global de edições simultâneas (config video.fila.simultaneas):
 *    sem vaga, volta para a fila em 30 s;
 *  - limite de uso do Claude: a edição espera e volta sozinha;
 *  - tries = 1: falha vira "erro" com botão de tentar de novo (que retoma
 *    do ponto em que parou), nunca um loop de re-render.
 */
class ProcessarEdicao implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 0 = sem limite de tentativas. Esperar a vez ($this->release) conta como
     * tentativa no Laravel: com tries = 1, a 2ª edição enviada enquanto outra
     * renderizava morria com "attempted too many times". O limite real é de
     * tempo (retryUntil) e erro de verdade já vira "erro" dentro do handle().
     */
    public int $tries = 0;

    public int $maxExceptions = 1;

    public int $timeout = 7000;

    public bool $failOnTimeout = true;

    public function __construct(public int $jobId, public string $acao = 'editar', public array $dados = [])
    {
        $this->onConnection(config('video.fila.conexao', 'video'));
        $this->onQueue(config('video.fila.nome', 'video'));
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(12);
    }

    public function handle(): void
    {
        $job = VideoJob::withoutGlobalScopes()->with('client')->find($this->jobId);

        // Só roda se o pedido ainda vale: "cancelar" tira a edição desses estados.
        if (! $job || ! in_array($job->status, [VideoJob::NA_FILA, VideoJob::AJUSTANDO, VideoJob::FINALIZANDO, VideoJob::AGUARDANDO_LIMITE], true)) {
            return;
        }

        $travaEdicao = Cache::lock("video:edicao:{$job->id}", 7200);
        if (! $travaEdicao->get()) {
            $this->release(60);

            return;
        }

        $vaga = $this->pegarVaga();
        if (! $vaga) {
            $travaEdicao->release();
            $job->progresso($job->progresso, 'Na fila: outra edição está usando o servidor');
            $this->release(30);

            return;
        }

        $statusEmCurso = match ($this->acao) {
            'ajustar', 'rerenderizar' => VideoJob::AJUSTANDO,
            'finalizar' => VideoJob::FINALIZANDO,
            default => VideoJob::PROCESSANDO,
        };

        $job->forceFill([
            'status' => $statusEmCurso, 'erro' => null, 'sinal_em' => now(),
            'iniciado_em' => $job->iniciado_em ?? now(), 'tentativas' => $job->tentativas + 1,
        ])->save();

        try {
            $edicao = new Edicao($job);

            match ($this->acao) {
                'ajustar' => $edicao->ajustar((string) ($this->dados['pedido'] ?? '')),
                'rerenderizar' => $edicao->rerenderizar((array) ($this->dados['plano'] ?? [])),
                'finalizar' => $edicao->finalizar(),
                default => $edicao->processar(),
            };

            $job->refresh();
            $job->forceFill([
                'status' => $this->acao === 'finalizar' ? VideoJob::PRONTO : VideoJob::REVISAO,
                'progresso' => 100, 'mensagem' => null, 'etapa' => null,
                'concluido_em' => $this->acao === 'finalizar' ? now() : $job->concluido_em,
            ])->save();

            // Final pronto: sobe para o Drive e libera o servidor (em segundo plano).
            if ($this->acao === 'finalizar' && Nuvem::ativo()) {
                $job->forceFill(['nuvem_status' => 'enviando'])->save();
                ArquivarNoDrive::dispatch($job->id);
            }
        } catch (LimiteDoClaude $e) {
            $minutos = (int) config('video.claude.espera_limite_minutos', 30);
            $job->forceFill(['status' => VideoJob::AGUARDANDO_LIMITE, 'mensagem' => "O Claude bateu o limite de uso da assinatura. Tento de novo em {$minutos} min."])->save();
            $job->registrar('sistema', "Limite de uso do Claude atingido. A edição volta para a fila sozinha em {$minutos} minutos.");
            self::dispatch($job->id, $this->acao, $this->dados)->delay(now()->addMinutes($minutos));
        } catch (FalhaDeFerramenta|PlanoInvalido $e) {
            $this->falhou($job, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Estúdio de vídeo: falha inesperada', ['edicao' => $job->id, 'acao' => $this->acao, 'erro' => $e->getMessage(), 'arquivo' => $e->getFile().':'.$e->getLine()]);
            $this->falhou($job, 'Erro inesperado: '.$e->getMessage());
        } finally {
            optional($vaga)->release();
            $travaEdicao->release();
        }
    }

    /** O worker morreu de vez (timeout, memória): a edição não pode ficar "editando" para sempre. */
    public function failed(?\Throwable $e): void
    {
        $job = VideoJob::withoutGlobalScopes()->find($this->jobId);

        if ($job && $job->emAndamento()) {
            $this->falhou($job, 'O processamento foi interrompido ('.($e ? mb_substr($e->getMessage(), 0, 200) : 'motivo desconhecido').').');
        }
    }

    private function falhou(VideoJob $job, string $mensagem): void
    {
        // Excluída no meio do processamento: não há mais onde registrar.
        if (! VideoJob::withoutGlobalScopes()->whereKey($job->id)->exists()) {
            return;
        }

        // Ajuste ou final que falha não perde a prévia que já existia.
        $voltaPara = in_array($this->acao, ['ajustar', 'rerenderizar', 'finalizar'], true) && $job->previa_path ? VideoJob::REVISAO : VideoJob::ERRO;

        $job->forceFill([
            'status' => $voltaPara,
            'erro' => mb_substr($mensagem, 0, 2000),
            'mensagem' => null,
            'etapa' => $voltaPara === VideoJob::ERRO ? $job->etapa : null,
        ])->save();

        $job->registrar('erro', $mensagem, ['acao' => $this->acao]);
    }

    /** Semáforo simples com N travas: uma vaga por edição rodando no servidor. */
    private function pegarVaga(): ?\Illuminate\Contracts\Cache\Lock
    {
        $vagas = max(1, (int) config('video.fila.simultaneas', 1));

        for ($i = 1; $i <= $vagas; $i++) {
            $trava = Cache::lock("video:vaga:$i", 7200);
            if ($trava->get()) {
                return $trava;
            }
        }

        return null;
    }
}
