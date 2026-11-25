<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um arquivo bruto de uma edição (vídeo, foto ou áudio). Chega em pedaços
 * (upload retomável): "recebido" é quantos bytes já estão no disco.
 * Nunca é alterado depois de pronto — tudo o que o motor gera vai para a
 * pasta de trabalho.
 */
class VideoJobFile extends Model
{
    use BelongsToCompany;
    use Concerns\GuardaEmUtc;

    public const PAPEIS = ['bruto', 'antes', 'depois'];

    protected $fillable = [
        'company_id', 'video_job_id', 'papel', 'ordem', 'nome_original', 'path', 'mime',
        'tamanho', 'recebido', 'status', 'tipo', 'duracao', 'largura', 'altura', 'fps',
        'rotacao', 'hdr', 'tem_audio', 'sondagem',
    ];

    protected function casts(): array
    {
        return [
            'tamanho' => 'integer',
            'recebido' => 'integer',
            'duracao' => 'float',
            'fps' => 'float',
            'hdr' => 'boolean',
            'tem_audio' => 'boolean',
            'sondagem' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VideoJob::class, 'video_job_id');
    }

    /** Chave curta e estável usada no plano (f1, f2…): o Claude não vê ids do banco. */
    public function chave(): string
    {
        return 'f'.$this->id;
    }

    public function caminhoAbsoluto(): string
    {
        return $this->job->pasta()->absoluto($this->path);
    }

    public function pronto(): bool
    {
        return $this->status === 'pronto';
    }
}
