<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Calendário de posts por cliente (mensal/semanal). A view renderiza a grade
 * e busca os eventos via JSON no endpoint events().
 */
class CalendarController extends Controller
{
    public function client(Request $request, Client $client)
    {
        $this->authorize('view', $client);

        return view('calendar.show', [
            'client' => $client,
            'project' => null,
            'projects' => $client->projects()->orderBy('name')->get(),
        ]);
    }

    public function project(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return view('calendar.show', [
            'client' => $project->client,
            'project' => $project,
            'projects' => $project->client->projects()->orderBy('name')->get(),
        ]);
    }

    /**
     * Eventos do calendário em JSON para um intervalo.
     * Filtros: ?from=YYYY-MM-DD&to=YYYY-MM-DD&social=instagram&project=ID
     */
    public function events(Request $request, Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $tasks = Task::query()
            ->where('client_id', $client->id)
            ->with(['project', 'assignee', 'column'])
            ->forCalendar($from->toDateString(), $to->toDateString())
            ->when($request->filled('project'), fn ($q) => $q->where('project_id', $request->integer('project')))
            ->get();

        $events = $tasks->map(fn (Task $task) => [
            'id' => $task->id,
            'title' => $task->title,
            'date' => $task->publish_date->toDateString(),
            'url' => route('tasks.show', $task),
            'color' => $task->column->color,
            'content_type' => $task->contentTypeLabel(),
            'project' => $task->project->name,
            'assignee' => $task->assignee?->name,
            'is_published' => $task->is_published,
        ]);

        return response()->json(['events' => $events]);
    }
}
