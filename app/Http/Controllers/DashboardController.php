<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $stats = [
            'clients' => Client::active()->count(),
            'projects' => Project::active()->count(),
            'tasks' => Task::count(),
            'published' => Task::where('is_published', true)->count(),
            'late' => Task::where('is_published', false)
                ->whereNotNull('publish_date')
                ->whereDate('publish_date', '<', now()->toDateString())
                ->count(),
        ];

        // Próximos posts (com data de publicação a partir de hoje).
        $upcoming = Task::with(['client', 'project', 'assignee', 'column'])
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '>=', now()->toDateString())
            ->where('is_published', false)
            ->orderBy('publish_date')
            ->limit(6)
            ->get();

        // Minhas tarefas.
        $myTasks = Task::with(['client', 'project', 'column'])
            ->where('assignee_id', $request->user()->id)
            ->where('is_published', false)
            ->orderBy('publish_date')
            ->limit(6)
            ->get();

        // Dados para o Gráfico de Produtividade (Últimos 7 dias)
        $chartData = ['labels' => [], 'data' => []];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartData['labels'][] = $date->format('d/m');
            $chartData['data'][] = Task::where('is_published', true)
                ->whereDate('published_at', $date->toDateString())
                ->count();
        }

        return view('dashboard', compact('stats', 'upcoming', 'myTasks', 'chartData'));
    }
}
