<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Lógica de Frases
        $tz = 'America/Sao_Paulo';
        $now = now()->timezone($tz);
        $hour = $now->hour;
        $dateStr = $now->toDateString();

        $phraseContent = "Bora pra cima! Mais um dia de conquistas.";

        try {
            if ($hour >= 5 && $hour < 12 && session('greeted_morning') !== $dateStr) {
                $phrase = \App\Models\Phrase::where('type', 'morning')->inRandomOrder()->first();
                if ($phrase) {
                    $phraseContent = $phrase->content;
                    session(['greeted_morning' => $dateStr]);
                }
            } elseif ($hour >= 18 && session('greeted_evening') !== $dateStr) {
                $phrase = \App\Models\Phrase::where('type', 'evening')->inRandomOrder()->first();
                if ($phrase) {
                    $phraseContent = $phrase->content;
                    session(['greeted_evening' => $dateStr]);
                }
            } else {
                // A frase do dia é a mesma o dia inteiro para todo mundo, então
                // não faz sentido pagar duas idas ao banco por request para
                // buscá-la. Fica em cache até o fim do dia.
                $doDia = \Illuminate\Support\Facades\Cache::remember(
                    "frase-do-dia:{$dateStr}",
                    $now->copy()->endOfDay(),
                    function () use ($now) {
                        $count = \App\Models\Phrase::where('type', 'daily')->count();

                        if ($count === 0) {
                            return null;
                        }

                        return \App\Models\Phrase::where('type', 'daily')
                            ->orderBy('id')
                            ->skip($now->dayOfYear % $count)
                            ->value('content');
                    }
                );

                if ($doDia) {
                    $phraseContent = $doDia;
                }
            }
        } catch (\Exception $e) {
            // Em caso de falha no banco (ex: tabela não existe), cai aqui silenciosamente
            \Log::error("Erro ao buscar frase do dia: " . $e->getMessage());
        }

        if ($user->isAdmin()) {
            return $this->adminDashboard($request, $phraseContent);
        }

        return $this->memberDashboard($request, $phraseContent);
    }

    private function adminDashboard(Request $request, $phraseContent)
    {
        $companyId = $request->user()->company_id;
        $today = now()->toDateString();

        // Os quatro contadores em UMA query só (antes eram 4 SELECT COUNT).
        $counts = Task::query()
            ->selectRaw(
                'COUNT(*) as total,'
                .' SUM(is_published = 1) as published,'
                .' SUM(is_published = 0) as pending,'
                .' SUM(is_published = 0 AND publish_date IS NOT NULL AND publish_date < ?) as late',
                [$today]
            )
            ->first();

        $totalTasks = (int) $counts->total;
        $publishedTasks = (int) $counts->published;

        $stats = [
            'tasks' => $totalTasks,
            'published' => $publishedTasks,
            'pending' => (int) $counts->pending,
            'late' => (int) $counts->late,
            'projects' => Project::active()->count(),
            'clients' => Client::active()->count(),
            'completion_rate' => $totalTasks > 0 ? round(($publishedTasks / $totalTasks) * 100) : 0,
        ];

        // Membros da equipe com acompanhamento e evolução
        $teamMembers = User::where('company_id', $companyId)
            ->withCount([
                'assignedTasks as tasks_total',
                'assignedTasks as tasks_completed' => fn ($q) => $q->where('is_published', true),
                'assignedTasks as tasks_pending' => fn ($q) => $q->where('is_published', false),
                'assignedTasks as tasks_late' => fn ($q) => $q->where('is_published', false)
                    ->whereNotNull('publish_date')
                    ->whereDate('publish_date', '<', $today),
            ])
            ->orderBy('name')
            ->get();

        // Última tarefa de cada membro: antes era 1 query por membro (+3 de
        // relações). Agora são 2 queries no total, independente do tamanho do time.
        $latestTasks = $this->latestTaskPerUser($teamMembers->pluck('id')->all());

        $teamMembers = $teamMembers->map(function ($member) use ($latestTasks) {
            $total = $member->tasks_total;
            $completed = $member->tasks_completed;
            $member->progress_percent = $total > 0 ? round(($completed / $total) * 100) : 0;
            $member->latest_task = $latestTasks->get($member->id);

            return $member;
        });

        // Gráfico comparativo de distribuição / evolução da equipe
        $teamChartData = [
            'labels' => $teamMembers->pluck('name')->toArray(),
            'completed' => $teamMembers->pluck('tasks_completed')->toArray(),
            'pending' => $teamMembers->pluck('tasks_pending')->toArray(),
            'late' => $teamMembers->pluck('tasks_late')->toArray(),
        ];

        // Gráfico de Produtividade Global (Últimos 14 dias) — 1 query agrupada
        // no lugar das 14 que existiam antes (uma por dia).
        $chartData = $this->publishedPerDayChart(Task::query());

        // Tarefas atrasadas da equipe
        $lateTasks = Task::with(['client', 'project', 'assignees', 'column'])
            ->where('is_published', false)
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '<', $today)
            ->orderBy('publish_date')
            ->limit(6)
            ->get();

        // Próximas entregas / posts agendados da agência
        $upcoming = Task::with(['client', 'project', 'assignees', 'column'])
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '>=', $today)
            ->where('is_published', false)
            ->orderBy('publish_date')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'isAdmin' => true,
            'stats' => $stats,
            'teamMembers' => $teamMembers,
            'teamChartData' => $teamChartData,
            'chartData' => $chartData,
            'lateTasks' => $lateTasks,
            'upcoming' => $upcoming,
            'phraseContent' => $phraseContent,
        ]);
    }

    private function memberDashboard(Request $request, $phraseContent)
    {
        $user = $request->user();
        $today = now()->toDateString();

        // Os quatro contadores em UMA query só (antes eram 4).
        $myCounts = Task::query()
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))
            ->selectRaw(
                'COUNT(*) as total,'
                .' SUM(is_published = 1) as completed,'
                .' SUM(is_published = 0) as pending,'
                .' SUM(is_published = 0 AND publish_date IS NOT NULL AND publish_date < ?) as late',
                [$today]
            )
            ->first();

        $myTotalTasks = (int) $myCounts->total;
        $myCompletedTasks = (int) $myCounts->completed;

        $myStats = [
            'total' => $myTotalTasks,
            'completed' => $myCompletedTasks,
            'pending' => (int) $myCounts->pending,
            'late' => (int) $myCounts->late,
            'completion_rate' => $myTotalTasks > 0 ? round(($myCompletedTasks / $myTotalTasks) * 100) : 0,
            'projects_count' => Project::whereHas('tasks', fn ($q) => $q->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id)))->count(),
        ];

        // Minha Fila de Tarefas Pendentes ordenada por prioridade/prazo
        $myPendingTasks = Task::with(['client', 'project', 'column', 'tags'])
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))
            ->where('is_published', false)
            ->orderByRaw('CASE WHEN publish_date IS NOT NULL AND publish_date < ? THEN 0 WHEN publish_date IS NOT NULL THEN 1 ELSE 2 END', [$today])
            ->orderBy('publish_date', 'asc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Meus Próximos Posts / Entregas agendadas
        $myUpcomingTasks = Task::with(['client', 'project', 'column'])
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))
            ->where('is_published', false)
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '>=', $today)
            ->orderBy('publish_date', 'asc')
            ->limit(6)
            ->get();

        // Meus Projetos Ativos (onde o colaborador tem tarefas pendentes)
        $myProjects = Project::whereHas('tasks', fn ($q) => $q->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id)))
            ->with('client')
            ->withCount([
                'tasks as my_total' => fn ($q) => $q->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id)),
                'tasks as my_completed' => fn ($q) => $q->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))->where('is_published', true),
                'tasks as my_pending' => fn ($q) => $q->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))->where('is_published', false),
            ])
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(function ($proj) {
                $proj->progress_percent = $proj->my_total > 0 ? round(($proj->my_completed / $proj->my_total) * 100) : 0;
                return $proj;
            });

        // Minhas entregas concluídas recentemente
        $myRecentlyCompleted = Task::with(['client', 'project', 'column'])
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))
            ->where('is_published', true)
            ->latest('published_at')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Gráfico de Produtividade Pessoal (Últimos 14 dias) — 1 query agrupada.
        $chartData = $this->publishedPerDayChart(
            Task::query()->whereHas('assignees', fn ($q) => $q->where('users.id', $user->id))
        );

        return view('dashboard', [
            'isAdmin' => false,
            'myStats' => $myStats,
            'myPendingTasks' => $myPendingTasks,
            'myUpcomingTasks' => $myUpcomingTasks,
            'myProjects' => $myProjects,
            'myRecentlyCompleted' => $myRecentlyCompleted,
            'chartData' => $chartData,
            'phraseContent' => $phraseContent,
        ]);
    }

    /**
     * Contagem de publicações dos últimos 14 dias em UMA query agrupada.
     * Antes o dashboard rodava um SELECT COUNT por dia (14 idas ao banco).
     */
    private function publishedPerDayChart(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $start = now()->subDays(13)->startOfDay();

        $perDay = $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $start)
            ->selectRaw('DATE(published_at) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $chartData = ['labels' => [], 'data' => []];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartData['labels'][] = $date->format('d/m');
            $chartData['data'][] = (int) ($perDay[$date->toDateString()] ?? 0);
        }

        return $chartData;
    }

    /**
     * Última tarefa (por updated_at) de cada usuário informado, em 3 queries
     * fixas — antes era 1 query por membro, mais 3 de relações cada.
     *
     * Feito em dois passos (data máxima por usuário, depois a tarefa daquela
     * data) para não depender de window function nem de função específica do
     * MySQL: os testes rodam em SQLite.
     */
    private function latestTaskPerUser(array $userIds): \Illuminate\Support\Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        $maxDates = \Illuminate\Support\Facades\DB::table('task_user as tu')
            ->join('tasks as t', 't.id', '=', 'tu.task_id')
            ->whereIn('tu.user_id', $userIds)
            ->groupBy('tu.user_id')
            ->selectRaw('tu.user_id as user_id, MAX(t.updated_at) as ultima')
            ->get();

        if ($maxDates->isEmpty()) {
            return collect();
        }

        $taskIdByUser = \Illuminate\Support\Facades\DB::table('task_user as tu')
            ->join('tasks as t', 't.id', '=', 'tu.task_id')
            ->where(function ($query) use ($maxDates) {
                foreach ($maxDates as $row) {
                    $query->orWhere(fn ($q) => $q
                        ->where('tu.user_id', $row->user_id)
                        ->where('t.updated_at', $row->ultima));
                }
            })
            // Ordem crescente porque o pluck sobrescreve a chave: em empate de
            // data, o último a entrar (maior id) é o que fica.
            ->orderBy('t.id')
            ->pluck('tu.task_id', 'tu.user_id');

        if ($taskIdByUser->isEmpty()) {
            return collect();
        }

        $tasks = Task::with(['client', 'project', 'column'])
            ->whereIn('id', $taskIdByUser->values()->all())
            ->get()
            ->keyBy('id');

        return $taskIdByUser->map(fn ($taskId) => $tasks->get((int) $taskId))->filter();
    }
}
