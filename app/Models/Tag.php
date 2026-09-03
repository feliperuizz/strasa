<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use BelongsToCompany;

    /**
     * Flags criadas em toda empresa como ponto de partida do fluxo de
     * conteúdo. A equipe pode renomear a cor, adicionar e excluir.
     */
    public const PREDEFINIDAS = [
        'Programar' => '#3b82f6',
        'Captação' => '#a855f7',
        'Edição' => '#f97316',
        'Programado' => '#22c55e',
    ];

    /** Paleta oferecida no seletor de cor. */
    public const CORES = [
        '#ef4444', '#f97316', '#eab308', '#22c55e',
        '#14b8a6', '#3b82f6', '#a855f7', '#ec4899', '#94a3b8',
    ];

    protected $fillable = ['company_id', 'name', 'color'];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_tag');
    }
}
