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

    /**
     * Flags da empresa mais as predefinidas que ainda não existem.
     *
     * As padrão aparecem sempre, mesmo em empresa nova ou onde a migration
     * de seed não tenha rodado — vêm com id nulo e viram registro de verdade
     * na primeira vez que alguém aplica uma. Assim a lista nunca depende de
     * um passo de deploy ter dado certo.
     *
     * @return \Illuminate\Support\Collection<int, array{id:?int, name:string, color:string, sugestao:bool}>
     */
    public static function comPredefinidas(): \Illuminate\Support\Collection
    {
        $existentes = self::orderBy('name')->get(['id', 'name', 'color']);

        $nomes = $existentes->map(fn ($t) => mb_strtolower($t->name));

        $sugestoes = collect(self::PREDEFINIDAS)
            ->reject(fn ($cor, $nome) => $nomes->contains(mb_strtolower($nome)))
            ->map(fn ($cor, $nome) => [
                'id' => null,
                'name' => $nome,
                'color' => $cor,
                'sugestao' => true,
                'padrao' => true,
            ])
            ->values();

        $nomesPadrao = collect(self::PREDEFINIDAS)->keys()->map(fn ($n) => mb_strtolower($n));

        return $existentes
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->color,
                'sugestao' => false,
                // Só as padrão entram na lista de escolha; as demais aparecem
                // apenas nos cards onde já estão, para poderem ser retiradas.
                'padrao' => $nomesPadrao->contains(mb_strtolower($t->name)),
            ])
            ->concat($sugestoes)
            ->sortBy(fn ($f) => mb_strtolower($f['name']))
            ->values();
    }
}
