<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Quadro Kanban do projeto. Monta colunas + cards com filtros e cuida do
 * desempenho com eager loading (with) para evitar N+1.
 */
class BoardController extends Controller
{
    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->load('client');

        // Colunas do projeto (cache curto, conforme requisito de performance).
        $columns = cache()->remember(
            "project:{$project->id}:columns",
            now()->addMinutes(10),
            fn () => $project->columns()->orderBy('position')->get()
        );

        // Tarefas com filtros, já com relações para os cards (evita N+1).
        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with(['assignee', 'tags', 'attachments', 'items'])
            ->when($request->filled('assignee'), fn ($q) => $q->where('assignee_id', $request->integer('assignee')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('publish_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('publish_date', '<=', $request->date('to')))
            ->orderBy('position')
            ->get()
            ->groupBy('column_id');

        $members = User::where('company_id', $project->company_id)->orderBy('name')->get();

        return view('projects.board', [
            'project' => $project,
            'client' => $project->client,
            'columns' => $columns,
            'tasksByColumn' => $tasks,
            'members' => $members,
            'filters' => $request->only(['assignee', 'from', 'to']),
        ]);
    }
    public function list(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $project->load('client');

        $columns = cache()->remember(
            "project:{$project->id}:columns",
            now()->addMinutes(10),
            fn () => $project->columns()->orderBy('position')->get()
        );

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with(['assignee', 'tags', 'attachments', 'items'])
            ->when($request->filled('assignee'), fn ($q) => $q->where('assignee_id', $request->integer('assignee')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('publish_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('publish_date', '<=', $request->date('to')))
            ->orderBy('position')
            ->get()
            ->groupBy('column_id');

        $members = User::where('company_id', $project->company_id)->orderBy('name')->get();

        return view('projects.list', [
            'project' => $project,
            'client' => $project->client,
            'columns' => $columns,
            'tasksByColumn' => $tasks,
            'members' => $members,
            'filters' => $request->only(['assignee', 'from', 'to']),
        ]);
    }
}
