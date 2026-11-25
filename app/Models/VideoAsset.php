<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item da biblioteca do estúdio: música, efeito sonoro ou fonte. Arquivo no
 * disco local do estúdio (pasta "biblioteca"). Música só entra com a
 * licença anotada — o vídeo vai para o Instagram do cliente.
 */
class VideoAsset extends Model
{
    use BelongsToCompany;
    use Concerns\GuardaEmUtc;

    protected $fillable = [
        'company_id', 'client_id', 'tipo', 'nome', 'path', 'mime', 'tamanho', 'duracao',
        'bpm', 'batidas', 'humor', 'licenca', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'batidas' => 'array',
            'duracao' => 'float',
            'bpm' => 'float',
            'tamanho' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Da agência inteira + os exclusivos deste cliente. */
    public function scopeParaCliente(Builder $q, ?int $clientId): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('client_id')->when($clientId, fn ($x) => $x->orWhere('client_id', $clientId)));
    }

    public function caminhoAbsoluto(): string
    {
        return \App\Video\Armazenamento::biblioteca($this->path);
    }

    public function duracaoTexto(): string
    {
        $s = (int) round($this->duracao ?? 0);

        return sprintf('%d:%02d', intdiv($s, 60), $s % 60);
    }
}
