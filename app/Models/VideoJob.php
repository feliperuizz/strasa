<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Video\Armazenamento;
use App\Video\Estilos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma edição de vídeo do Estúdio.
 *
 * Fluxo: rascunho (enviando arquivos) → na_fila → processando (preparar,
 * transcrever, decidir, renderizar) → revisao (prévia pronta, aceita
 * pedidos de ajuste) → finalizando → pronto. Qualquer etapa pode cair em
 * "erro" (com botão de tentar de novo, que retoma de onde parou) ou em
 * "aguardando_limite" (a assinatura do Claude bateu o limite de uso).
 */
class VideoJob extends Model
{
    use BelongsToCompany;
    use Concerns\GuardaEmUtc;

    public const RASCUNHO = 'rascunho';

    public const NA_FILA = 'na_fila';

    public const PROCESSANDO = 'processando';

    public const REVISAO = 'revisao';

    public const AJUSTANDO = 'ajustando';

    public const FINALIZANDO = 'finalizando';

    public const PRONTO = 'pronto';

    public const ERRO = 'erro';

    public const AGUARDANDO_LIMITE = 'aguardando_limite';

    public const CANCELADO = 'cancelado';

    /** Estados em que um worker está (ou deveria estar) mexendo na edição. */
    public const EM_ANDAMENTO = [self::NA_FILA, self::PROCESSANDO, self::AJUSTANDO, self::FINALIZANDO];

    public const FORMATOS = [
        '9:16' => ['largura' => 1080, 'altura' => 1920, 'nome' => 'Reels / Stories / TikTok'],
        '4:5' => ['largura' => 1080, 'altura' => 1350, 'nome' => 'Feed (retrato)'],
        '1:1' => ['largura' => 1080, 'altura' => 1080, 'nome' => 'Feed (quadrado)'],
        '16:9' => ['largura' => 1920, 'altura' => 1080, 'nome' => 'YouTube / horizontal'],
    ];

    protected $fillable = [
        'company_id', 'client_id', 'created_by', 'titulo', 'estilo', 'formato', 'duracao_alvo',
        'briefing', 'opcoes', 'musica_id', 'status', 'etapa', 'progresso', 'mensagem', 'erro',
        'plano', 'plano_versao', 'previa_versao', 'final_versao', 'previa_path', 'final_path',
        'capa_path', 'nuvem_disco', 'nuvem_final', 'nuvem_capa', 'nuvem_tamanho', 'nuvem_status', 'nuvem_erro', 'nuvem_em',
        'legenda_post', 'feito_por_claude', 'task_id', 'claude_sessao', 'claude_uso',
        'tentativas', 'sinal_em', 'iniciado_em', 'concluido_em', 'limpo_em',
    ];

    protected function casts(): array
    {
        return [
            'opcoes' => 'array',
            'plano' => 'array',
            'claude_uso' => 'array',
            'feito_por_claude' => 'boolean',
            'sinal_em' => 'datetime',
            'iniciado_em' => 'datetime',
            'concluido_em' => 'datetime',
            'limpo_em' => 'datetime',
            'nuvem_em' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function musica(): BelongsTo
    {
        return $this->belongsTo(VideoAsset::class, 'musica_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function arquivos(): HasMany
    {
        return $this->hasMany(VideoJobFile::class)->orderBy('ordem')->orderBy('id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(VideoJobEvent::class)->orderBy('id');
    }

    /* Estado ------------------------------------------------------------- */

    public function emAndamento(): bool
    {
        return in_array($this->status, self::EM_ANDAMENTO, true);
    }

    public function aceitaArquivos(): bool
    {
        return $this->status === self::RASCUNHO;
    }

    public function aceitaAjustes(): bool
    {
        return in_array($this->status, [self::REVISAO, self::PRONTO], true) && $this->plano;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::RASCUNHO => 'Rascunho',
            self::NA_FILA => 'Na fila',
            self::PROCESSANDO => 'Editando',
            self::REVISAO => 'Prévia pronta',
            self::AJUSTANDO => 'Ajustando',
            self::FINALIZANDO => 'Gerando final',
            self::PRONTO => 'Pronto',
            self::ERRO => 'Erro',
            self::AGUARDANDO_LIMITE => 'Aguardando limite do Claude',
            self::CANCELADO => 'Cancelado',
            default => $this->status,
        };
    }

    /** Classes da etiqueta de status (mesma linguagem das aprovações). */
    public function statusCor(): string
    {
        return match ($this->status) {
            self::PRONTO => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
            self::REVISAO => 'border-sky-500/30 bg-sky-500/10 text-sky-400',
            self::ERRO => 'border-rose-500/30 bg-rose-500/10 text-rose-400',
            self::AGUARDANDO_LIMITE => 'border-amber-500/30 bg-amber-500/10 text-amber-400',
            self::RASCUNHO, self::CANCELADO => 'border-ink-500/40 bg-ink-700/40 text-slate-400',
            default => 'border-violet-500/30 bg-violet-500/10 text-violet-300',
        };
    }

    public function estiloInfo(): array
    {
        return Estilos::get($this->estilo);
    }

    public function dimensoes(): array
    {
        return self::FORMATOS[$this->formato] ?? self::FORMATOS['9:16'];
    }

    public function opcao(string $chave, mixed $padrao = null): mixed
    {
        return data_get($this->opcoes ?? [], $chave, $padrao);
    }

    public function pasta(): Armazenamento
    {
        return new Armazenamento($this);
    }

    /** Linha do tempo + atualização de estado numa chamada só. */
    public function registrar(string $tipo, string $texto, array $dados = [], ?int $userId = null): VideoJobEvent
    {
        return $this->eventos()->create([
            'tipo' => $tipo,
            'texto' => $texto,
            'dados' => $dados ?: null,
            'user_id' => $userId,
        ]);
    }

    /** Batimento do worker + mensagem de progresso. Barato: um UPDATE. */
    public function progresso(int $percentual, ?string $mensagem = null, ?string $etapa = null): void
    {
        $dados = ['progresso' => max(0, min(100, $percentual)), 'sinal_em' => now()];

        if ($mensagem !== null) {
            $dados['mensagem'] = mb_substr($mensagem, 0, 255);
        }

        if ($etapa !== null) {
            $dados['etapa'] = $etapa;
        }

        $this->forceFill($dados)->saveQuietly();
    }

    /** O vídeo final já está no Drive (e pode ter saído do servidor). */
    public function noDrive(): bool
    {
        return $this->nuvem_status === 'salvo' && filled($this->nuvem_final);
    }

    /** Há vídeo final para assistir/baixar (no servidor ou no Drive). */
    public function temFinal(): bool
    {
        return $this->noDrive() || ($this->final_path && is_file($this->pasta()->absoluto($this->final_path)));
    }
}
