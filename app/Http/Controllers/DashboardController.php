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
        ];

        // Próximos posts (com data de publicação a partir de hoje).
        $upcoming = Task::with(['client', 'project', 'assignee', 'column'])
            ->whereNotNull('publish_date')
            ->whereDate('publish_date', '>=', now()->toDateString())
            ->orderBy('publish_date')
            ->limit(8)
            ->get();

        // Minhas tarefas.
        $myTasks = Task::with(['client', 'project', 'column'])
            ->where('assignee_id', $request->user()->id)
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard', compact('stats', 'upcoming', 'myTasks'));
    }
}
