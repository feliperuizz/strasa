<?php

namespace App\Http\Controllers;

use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Log de atividades: o que cada pessoa fez hoje, ontem e anteontem.
 *
 * É a mesma linha do tempo que aparece dentro de cada card, só que virada
 * do avesso — agrupada por pessoa e por dia, para o administrador ver o
 * movimento da equipe sem abrir card por card. Só admin enxerga.
 */
class ActivityLogController extends Controller
{
    /** Quantos dias entram no log, contando hoje. */
    private const DIAS = 3;

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $companyId = $request->user()->company_id;

        $filtros = $request->validate([
            'dia' => ['nullable', 'date_format:Y-m-d'],
            'usuario' => ['nullable', 'integer'],
        ]);

        // Os dias do filtro, do mais recente para o mais antigo.
        $dias = collect(range(0, self::DIAS - 1))
            ->map(fn ($i) => now()->subDays($i)->startOfDay());

        $diaEscolhido = isset($filtros['dia']) ? Carbon::createFromFormat('Y-m-d', $filtros['dia'])->startOfDay() : null;
        if ($diaEscolhido && ! $dias->contains(fn ($d) => $d->isSameDay($diaEscolhido))) {
            $diaEscolhido = null;
        }

        $inicio = $diaEscolhido ?: $dias->last();
        $fim = ($diaEscolhido ?: $dias->first())->copy()->endOfDay();

        $atividades = TaskActivity::query()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$inicio, $fim])
            ->when($filtros['usuario'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->with(['user', 'task.project.client', 'task.column'])
            ->orderByDesc('created_at')
            ->get();

        // Quem aparece no filtro: toda a equipe (desativados também — o log
        // é justamente onde se confere o que alguém fez antes de sair).
        $equipe = User::where('company_id', $companyId)->orderBy('name')->get();

        return view('activity-log.index', [
            'dias' => $dias,
            'diaEscolhido' => $diaEscolhido,
            'usuarioEscolhido' => $filtros['usuario'] ?? null,
            'equipe' => $equipe,
            'grupos' => $this->agrupar($atividades, $dias),
            'total' => $atividades->count(),
        ]);
    }

    /**
     * Pessoa → dia → atividades, com um resumo por tipo em cada pessoa.
     * Atividades sem usuário são do cliente pelo painel de aprovação e
     * ficam num grupo próprio no fim.
     */
    private function agrupar(Collection $atividades, Collection $dias): Collection
    {
        return $atividades
            ->groupBy(fn (TaskActivity $a) => $a->user_id ?: 'cliente')
            ->map(function (Collection $doUsuario, $chave) use ($dias) {
                $resumo = $doUsuario
                    ->groupBy('type')
                    ->map(fn ($g) => $g->count())
                    ->sortDesc()
                    ->take(4)
                    ->map(fn ($n, $tipo) => $n.' '.(TaskActivity::LABELS[$tipo] ?? $tipo));

                $porDia = $dias
                    ->mapWithKeys(fn (Carbon $dia) => [$dia->toDateString() => $doUsuario->filter(fn ($a) => $a->created_at->isSameDay($dia))->values()])
                    ->filter(fn ($lista) => $lista->isNotEmpty());

                return [
                    'pessoa' => $chave === 'cliente' ? null : $doUsuario->first()->user,
                    'chave' => $chave,
                    'total' => $doUsuario->count(),
                    'resumo' => $resumo->values()->all(),
                    'porDia' => $porDia,
                ];
            })
            ->sortBy([
                fn ($a, $b) => ($a['pessoa'] === null) <=> ($b['pessoa'] === null),
                fn ($a, $b) => $b['total'] <=> $a['total'],
            ])
            ->values();
    }
}
