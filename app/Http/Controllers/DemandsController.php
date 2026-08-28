<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Demandas por colaborador: o que cada pessoa tem para entregar, e quando.
 *
 * DESEMPENHO: uma consulta traz as tarefas do período com as relações que os
 * cards precisam; o agrupamento por pessoa e por dia é feito em memória. Não
 * há consulta por colaborador nem por dia.
 */
class DemandsController extends Controller
{
    /** Atalhos de período oferecidos na barra de filtros. */
    private const ATALHOS = [
        'hoje' => 'Hoje',
        'amanha' => 'Amanhã',
        'semana' => 'Esta semana',
        'proxima' => 'Próxima semana',
        'mes' => 'Este mês',
        'atrasadas' => 'Atrasadas',
        'sem-data' => 'Sem data',
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $user->isManager(), 403,
            'Apenas administradores acompanham as demandas da equipe.');

        $filtros = $request->validate([
            'atalho' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::ATALHOS))],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date'],
            'user' => ['nullable', 'integer'],
            'client' => ['nullable', 'integer'],
            'concluidas' => ['nullable', 'boolean'],
        ]);

        // Sem nada escolhido, a tela abre na semana corrente — é a pergunta
        // que a gestão faz com mais frequência.
        $atalho = $filtros['atalho'] ?? (isset($filtros['de']) || isset($filtros['ate']) ? null : 'semana');

        [$de, $ate, $modo] = $this->intervalo($atalho, $filtros['de'] ?? null, $filtros['ate'] ?? null);

        $tarefas = Task::query()
            ->with([
                'assignees:id,name,avatar_path,avatar_disk,avatar_color',
                'client:id,name,color,logo_path,logo_disk',
                'project:id,name',
                'column:id,name,color',
            ])
            ->when($modo === 'sem-data', fn ($q) => $q->whereNull('publish_date'))
            ->when($modo === 'atrasadas', fn ($q) => $q
                ->whereNotNull('publish_date')
                ->whereDate('publish_date', '<', now()->toDateString())
                ->where('is_published', false))
            ->when($modo === 'intervalo', fn ($q) => $q
                ->whereNotNull('publish_date')
                ->whereDate('publish_date', '>=', $de)
                ->whereDate('publish_date', '<=', $ate))
            ->when(! empty($filtros['client']), fn ($q) => $q->where('client_id', $filtros['client']))
            ->when(! empty($filtros['user']), fn ($q) => $q->whereHas('assignees',
                fn ($q) => $q->where('users.id', $filtros['user'])))
            ->when(empty($filtros['concluidas']), fn ($q) => $q->where('is_published', false))
            ->orderBy('publish_date')
            ->orderBy('publish_time')
            ->get();

        return view('demands.index', [
            'colaboradores' => $this->agruparPorPessoa($tarefas),
            'semResponsavel' => $tarefas->filter(fn ($t) => $t->assignees->isEmpty())->values(),
            'resumo' => $this->resumo($tarefas),
            'equipe' => User::where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
            'clientes' => Client::active()->orderBy('name')->get(['id', 'name']),
            'atalhos' => self::ATALHOS,
            'filtros' => $filtros + ['atalho' => $atalho, 'de' => $de, 'ate' => $ate],
            'modo' => $modo,
        ]);
    }

    /* --------------------------------------------------------------------- */

    /**
     * Traduz o atalho (ou as datas digitadas) num intervalo concreto.
     *
     * @return array{0:?string, 1:?string, 2:string}  [de, ate, modo]
     */
    private function intervalo(?string $atalho, ?string $de, ?string $ate): array
    {
        $hoje = now()->startOfDay();

        return match ($atalho) {
            'hoje' => [$hoje->toDateString(), $hoje->toDateString(), 'intervalo'],
            'amanha' => [$hoje->copy()->addDay()->toDateString(), $hoje->copy()->addDay()->toDateString(), 'intervalo'],
            'semana' => [$hoje->copy()->startOfWeek()->toDateString(), $hoje->copy()->endOfWeek()->toDateString(), 'intervalo'],
            'proxima' => [
                $hoje->copy()->addWeek()->startOfWeek()->toDateString(),
                $hoje->copy()->addWeek()->endOfWeek()->toDateString(),
                'intervalo',
            ],
            'mes' => [$hoje->copy()->startOfMonth()->toDateString(), $hoje->copy()->endOfMonth()->toDateString(), 'intervalo'],
            'atrasadas' => [null, null, 'atrasadas'],
            'sem-data' => [null, null, 'sem-data'],
            default => [
                $de ?: $hoje->copy()->startOfWeek()->toDateString(),
                $ate ?: $hoje->copy()->endOfWeek()->toDateString(),
                'intervalo',
            ],
        };
    }

    /**
     * Uma tarefa com dois responsáveis aparece na lista dos dois — é o que a
     * gestão espera ao perguntar "o que fulano tem para hoje".
     */
    private function agruparPorPessoa($tarefas)
    {
        $porPessoa = [];

        foreach ($tarefas as $tarefa) {
            foreach ($tarefa->assignees as $pessoa) {
                $porPessoa[$pessoa->id] ??= ['pessoa' => $pessoa, 'tarefas' => collect()];
                $porPessoa[$pessoa->id]['tarefas']->push($tarefa);
            }
        }

        // Quem tem mais coisa na mão aparece primeiro.
        uasort($porPessoa, fn ($a, $b) => $b['tarefas']->count() <=> $a['tarefas']->count());

        foreach ($porPessoa as $id => $dados) {
            $porPessoa[$id]['atrasadas'] = $dados['tarefas']
                ->filter(fn ($t) => $t->publish_date
                    && $t->publish_date->isBefore(now()->startOfDay())
                    && ! $t->is_published)
                ->count();

            // Agrupa por dia para a listagem sair em blocos de data.
            $porPessoa[$id]['porDia'] = $dados['tarefas']
                ->groupBy(fn ($t) => $t->publish_date?->toDateString() ?? 'sem-data');
        }

        return $porPessoa;
    }

    /** @return array<string, int> */
    private function resumo($tarefas): array
    {
        $hoje = now()->startOfDay();

        return [
            'total' => $tarefas->count(),
            'atrasadas' => $tarefas->filter(fn ($t) => $t->publish_date
                && $t->publish_date->isBefore($hoje)
                && ! $t->is_published)->count(),
            'concluidas' => $tarefas->where('is_published', true)->count(),
            'sem_responsavel' => $tarefas->filter(fn ($t) => $t->assignees->isEmpty())->count(),
        ];
    }
}
