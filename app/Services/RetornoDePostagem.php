<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskPublication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Webhook de retorno da API: o sistema parceiro avisa como ficou a postagem
 * de um post numa rede (agendado, publicando, publicado, falhou, cancelado).
 *
 * Idempotente: o mesmo aviso reenviado atualiza o mesmo registro (post ×
 * chave × rede) e só gera linha no histórico do card quando algo muda.
 */
class RetornoDePostagem
{
    /**
     * @param  array{network: string, status: string, external_id?: ?string, permalink?: ?string,
     *               error_message?: ?string, scheduled_for?: ?string, published_at?: ?string}  $dados
     * @return array{0: TaskPublication, 1: bool}  [registro, se concluiu o card agora]
     */
    public function registrar(ApiToken $chave, Task $task, array $dados): array
    {
        return DB::transaction(function () use ($chave, $task, $dados) {
            $anterior = TaskPublication::withoutGlobalScopes()
                ->where('task_id', $task->id)
                ->where('api_token_id', $chave->id)
                ->where('network', $dados['network'])
                ->lockForUpdate()
                ->first();

            $publicacao = $anterior ?? new TaskPublication([
                'company_id' => $task->company_id,
                'task_id' => $task->id,
                'api_token_id' => $chave->id,
                'network' => $dados['network'],
            ]);

            $publicacao->fill([
                'status' => $dados['status'],
                'external_id' => $dados['external_id'] ?? $publicacao->external_id,
                'permalink' => $dados['permalink'] ?? $publicacao->permalink,
                // Erro só vale para a falha; um "publicado" depois limpa o erro antigo.
                'error_message' => $dados['status'] === 'failed' ? ($dados['error_message'] ?? null) : null,
                'scheduled_for' => isset($dados['scheduled_for']) ? self::emUtc($dados['scheduled_for']) : $publicacao->scheduled_for,
                'published_at' => $dados['status'] === 'published'
                    ? (isset($dados['published_at']) ? self::emUtc($dados['published_at']) : ($publicacao->published_at ?? now()))
                    : $publicacao->published_at,
                'reported_at' => now(),
            ]);

            $mudou = ! $anterior
                || $anterior->getOriginal('status') !== $publicacao->status
                || $anterior->getOriginal('error_message') !== $publicacao->error_message
                || $anterior->getOriginal('permalink') !== $publicacao->permalink;

            $publicacao->save();

            if ($mudou) {
                TaskActivity::registrar($task, TaskActivity::TYPE_AUTO_POST, $this->descricao($publicacao), [
                    'via' => 'api',
                    'api_token_id' => $chave->id,
                    'author' => $chave->name,
                    'network' => $publicacao->network,
                    'status' => $publicacao->status,
                    'permalink' => $publicacao->permalink,
                    'error' => $publicacao->error_message,
                ]);
            }

            // Foi ao ar: o card vai para a coluna de concluído, como no botão
            // de concluir. Desligável por chave, no painel de Integrações.
            $concluiu = false;
            if ($publicacao->status === 'published' && $chave->auto_complete && ! $task->is_published) {
                [$destino] = $task->concluir();
                $concluiu = true;

                TaskActivity::registrar($task, TaskActivity::TYPE_PUBLISHED,
                    'marcou como publicado automaticamente'.($destino ? ' e moveu para "'.$destino->name.'"' : ''),
                    ['via' => 'api', 'api_token_id' => $chave->id, 'author' => $chave->name]);
            }

            return [$publicacao, $concluiu];
        });
    }

    /**
     * O banco guarda em UTC e o Eloquent não converte fuso ao gravar: sem isto
     * "18:00-03:00" viraria "18:00" UTC (3h de erro). Data sem fuso é lida
     * como horário de Brasília.
     */
    private static function emUtc(string $valor): Carbon
    {
        return Carbon::parse($valor, 'America/Sao_Paulo')->utc();
    }

    private function descricao(TaskPublication $p): string
    {
        $rede = $p->redeLabel();

        return match ($p->status) {
            'published' => "publicou no {$rede}".($p->permalink ? ' ('.$p->permalink.')' : ''),
            'failed' => "não conseguiu publicar no {$rede}".($p->error_message ? ': '.Str::limit($p->error_message, 200) : ''),
            'scheduled' => "agendou no {$rede}".($p->scheduled_for ? ' para '.$p->scheduled_for->copy()->setTimezone('America/Sao_Paulo')->format('d/m H:i') : ''),
            'publishing' => "está publicando no {$rede}",
            'cancelled' => "cancelou a postagem no {$rede}",
            default => "atualizou a postagem no {$rede}",
        };
    }
}
