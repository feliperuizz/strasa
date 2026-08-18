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
            $count = \App\Models\Phrase::where('type', 'daily')->count();
            if ($count > 0) {
                $phrase = \App\Models\Phrase::where('type', 'daily')->skip($now->dayOfYear % $count)->first();
                if ($phrase) $phraseContent = $phrase->content;
            }
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

        $totalTasks = Task::count();
        $publishedTasks = Task::where('is_published', true)->count();
        $pendingTasks = Task::where('is_published', false)->count();
        $lateTasksCount = Task::where('is_published', false)
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '<', $today)
            ->count();

        $stats = [
            'tasks' => $totalTasks,
            'published' => $publishedTasks,
            'pending' => $pendingTasks,
            'late' => $lateTasksCount,
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
            ->get()
            ->map(function ($member) {
                $total = $member->tasks_total;
                $completed = $member->tasks_completed;
                $member->progress_percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                
                // Última tarefa trabalhada ou atribuída
                $member->latest_task = Task::where('assignee_id', $member->id)
                    ->with('client', 'project', 'column')
                    ->latest('updated_at')
                    ->first();

                return $member;
            });

        // Gráfico comparativo de distribuição / evolução da equipe
        $teamChartData = [
            'labels' => $teamMembers->pluck('name')->toArray(),
            'completed' => $teamMembers->pluck('tasks_completed')->toArray(),
            'pending' => $teamMembers->pluck('tasks_pending')->toArray(),
            'late' => $teamMembers->pluck('tasks_late')->toArray(),
        ];

        // Gráfico de Produtividade Global (Últimos 14 dias)
        $chartData = ['labels' => [], 'data' => []];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartData['labels'][] = $date->format('d/m');
            $chartData['data'][] = Task::where('is_published', true)
                ->whereDate('published_at', $date->toDateString())
                ->count();
        }

        // Tarefas atrasadas da equipe
        $lateTasks = Task::with(['client', 'project', 'assignee', 'column'])
            ->where('is_published', false)
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '<', $today)
            ->orderBy('publish_date')
            ->limit(6)
            ->get();

        // Próximas entregas / posts agendados da agência
        $upcoming = Task::with(['client', 'project', 'assignee', 'column'])
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

        $myTotalTasks = Task::where('assignee_id', $user->id)->count();
        $myCompletedTasks = Task::where('assignee_id', $user->id)->where('is_published', true)->count();
        $myPendingTasksCount = Task::where('assignee_id', $user->id)->where('is_published', false)->count();
        $myLateTasksCount = Task::where('assignee_id', $user->id)
            ->where('is_published', false)
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '<', $today)
            ->count();

        $myStats = [
            'total' => $myTotalTasks,
            'completed' => $myCompletedTasks,
            'pending' => $myPendingTasksCount,
            'late' => $myLateTasksCount,
            'completion_rate' => $myTotalTasks > 0 ? round(($myCompletedTasks / $myTotalTasks) * 100) : 0,
            'projects_count' => Project::whereHas('tasks', fn ($q) => $q->where('assignee_id', $user->id))->count(),
        ];

        // Minha Fila de Tarefas Pendentes ordenada por prioridade/prazo
        $myPendingTasks = Task::with(['client', 'project', 'column', 'tags'])
            ->where('assignee_id', $user->id)
            ->where('is_published', false)
            ->orderByRaw('CASE WHEN publish_date IS NOT NULL AND publish_date < ? THEN 0 WHEN publish_date IS NOT NULL THEN 1 ELSE 2 END', [$today])
            ->orderBy('publish_date', 'asc')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Meus Próximos Posts / Entregas agendadas
        $myUpcomingTasks = Task::with(['client', 'project', 'column'])
            ->where('assignee_id', $user->id)
            ->where('is_published', false)
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '>=', $today)
            ->orderBy('publish_date', 'asc')
            ->limit(6)
            ->get();

        // Meus Projetos Ativos (onde o colaborador tem tarefas pendentes)
        $myProjects = Project::whereHas('tasks', fn ($q) => $q->where('assignee_id', $user->id))
            ->with('client')
            ->withCount([
                'tasks as my_total' => fn ($q) => $q->where('assignee_id', $user->id),
                'tasks as my_completed' => fn ($q) => $q->where('assignee_id', $user->id)->where('is_published', true),
                'tasks as my_pending' => fn ($q) => $q->where('assignee_id', $user->id)->where('is_published', false),
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
            ->where('assignee_id', $user->id)
            ->where('is_published', true)
            ->latest('published_at')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Gráfico de Produtividade Pessoal (Últimos 14 dias)
        $chartData = ['labels' => [], 'data' => []];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartData['labels'][] = $date->format('d/m');
            $chartData['data'][] = Task::where('assignee_id', $user->id)
                ->where('is_published', true)
                ->whereDate('published_at', $date->toDateString())
                ->count();
        }

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
}
