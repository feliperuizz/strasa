<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Column;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function create(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $data = $this->formData($project);
        $data['task'] = new Task(['project_id' => $project->id]); // Mock task for form

        if ($request->ajax() || $request->wantsJson()) {
            return view('tasks.partials.slideover', $data);
        }

        return view('tasks.create', $data);
    }

    public function store(TaskRequest $request, Project $project)
    {
        $this->authorize('view', $project);

        $column = $this->resolveColumn($request->integer('column_id'), $project);

        $task = DB::transaction(function () use ($request, $project, $column) {
            $task = $project->tasks()->create([
                'company_id' => $project->company_id,
                'client_id' => $project->client_id,
                'column_id' => $column->id,
                'created_by' => $request->user()->id,
                'title' => $request->validated('title') ?? 'Nova Tarefa', // Permite salvar sem título temporariamente
                'description' => $request->validated('description'),
                'content_type' => $request->validated('content_type'),
                'publish_date' => $request->validated('publish_date'),
                'position' => (int) $project->tasks()->where('column_id', $column->id)->max('position') + 1,
                'is_published' => $column->marks_published,
                'published_at' => $column->marks_published ? now() : null,
            ]);

            $this->syncTags($task, $request->input('tags', []));
            if ($request->has('has_assignees') || $request->has('assignees')) {
                $task->assignees()->sync($request->input('assignees', []));
            }
            
            $this->log($task, TaskActivity::TYPE_CREATED, 'criou a tarefa');

            return $task;
        });

        if ($request->ajax() || $request->wantsJson()) {
            $html = view('components.task-card', ['task' => $task])->render();
            return response()->json(['task' => $task, 'message' => 'Criado com sucesso', 'html' => $html]);
        }

        return redirect()->route('tasks.show', $task)->with('status', 'Tarefa criada.');
    }

    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $task->load([
            'client', 'project.columns', 'column', 'assignees', 'creator', 'tags',
            'attachments.uploader',
            'comments.user',
            'activities.user',
        ]);

        $members = User::where('company_id', $task->company_id)->orderBy('name')->get();
        $data = array_merge($this->formData($task->project), ['task' => $task, 'members' => $members]);

        if ($request->ajax() || $request->wantsJson()) {
            return view('tasks.partials.slideover', $data);
        }

        return view('tasks.show', $data);
    }

    public function edit(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = array_merge($this->formData($task->project), ['task' => $task]);

        if ($request->ajax() || $request->wantsJson()) {
            return view('tasks.partials.slideover', $data);
        }

        return view('tasks.edit', $data);
    }

    public function update(TaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        // O slideover não troca de coluna (não há seletor): se column_id não vier, mantém a
        // coluna atual. A troca de coluna acontece só via drag/menu de contexto (rota move).
        $column = $request->filled('column_id')
            ? $this->resolveColumn($request->integer('column_id'), $task->project)
            : ($task->column ?? $task->project->columns()->orderBy('position')->first());

        DB::transaction(function () use ($request, $task, $column) {
            $this->trackChanges($request, $task, $column);

            $task->update([
                'column_id' => $column->id,
                'title' => $request->validated('title') ?? 'Nova Tarefa',
                'description' => $request->validated('description'),
                'content_type' => $request->validated('content_type'),
                'publish_date' => $request->validated('publish_date'),
                'is_published' => $column->marks_published,
                'published_at' => $column->marks_published ? ($task->published_at ?? now()) : null,
            ]);

            $this->syncTags($task, $request->input('tags', []));
            if ($request->has('has_assignees') || $request->has('assignees')) {
                $task->assignees()->sync($request->input('assignees', []));
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['task' => $task, 'message' => 'Atualizado com sucesso']);
        }

        return redirect()->route('tasks.show', $task)->with('status', 'Tarefa atualizada.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $project = $task->project;

        // Apaga os arquivos no bucket antes de remover os registros.
        foreach ($task->attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $task->delete();

        return redirect()->route('projects.board', $project)->with('status', 'Tarefa excluída.');
    }

    /**
     * Move o card entre colunas (drag & drop do Kanban).
     * Aplica as regras de workflow: "Postado" publica; "Rejeitado" exige motivo.
     */
    public function move(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'column_id' => ['required', 'integer'],
            'ordered_ids' => ['nullable', 'array'],
            'ordered_ids.*' => ['integer'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $target = $this->resolveColumn((int) $data['column_id'], $task->project);
        $origin = $task->column;
        $changedColumn = $origin->id !== $target->id;

        if ($changedColumn && $target->requires_rejection_reason && blank($data['rejection_reason'] ?? null)) {
            return response()->json([
                'ok' => false,
                'requires_reason' => true,
                'message' => 'Informe o motivo da rejeição.',
            ], 422);
        }

        DB::transaction(function () use ($task, $target, $origin, $changedColumn, $data) {
            $task->column_id = $target->id;

            if ($target->marks_published) {
                $task->is_published = true;
                $task->published_at ??= now();
            } elseif ($origin->marks_published) {
                $task->is_published = false;
                $task->published_at = null;
            }

            if ($target->requires_rejection_reason && ! blank($data['rejection_reason'] ?? null)) {
                $task->rejection_reason = $data['rejection_reason'];
            }

            $task->save();

            // Persiste a nova ordem dos cards na coluna destino.
            foreach ($data['ordered_ids'] ?? [] as $position => $id) {
                Task::where('id', $id)
                    ->where('project_id', $task->project_id)
                    ->update(['column_id' => $target->id, 'position' => $position]);
            }

            if ($changedColumn) {
                $this->log($task, TaskActivity::TYPE_COLUMN_CHANGED,
                    "moveu de \"{$origin->name}\" para \"{$target->name}\"");

                if ($target->marks_published) {
                    $this->log($task, TaskActivity::TYPE_PUBLISHED, 'marcou como publicado');
                }
                if ($target->requires_rejection_reason) {
                    $this->log($task, TaskActivity::TYPE_REJECTED, 'rejeitou a tarefa',
                        ['reason' => $task->rejection_reason]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    /* --------------------------------------------------------------------- */
    /* Helpers                                                               */
    /* --------------------------------------------------------------------- */

    private function formData(Project $project): array
    {
        return [
            'project' => $project->load('client', 'columns'),
            'members' => User::where('company_id', $project->company_id)->orderBy('name')->get(),
            'contentTypes' => Task::CONTENT_TYPES,
            'allTags' => Tag::orderBy('name')->get(),
        ];
    }

    public function complete(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $lastColumn = $task->project->columns()->orderBy('position', 'desc')->first();

        if (! $lastColumn) {
            return response()->json(['ok' => false, 'message' => 'Nenhuma coluna no projeto'], 422);
        }

        $task->update([
            'column_id' => $lastColumn->id,
            'is_published' => $lastColumn->marks_published,
            'published_at' => $lastColumn->marks_published ? ($task->published_at ?? now()) : null,
        ]);

        return response()->json(['ok' => true]);
    }

    /** Garante que a coluna pertence ao projeto (e à empresa via scope). */
    private function resolveColumn(int $columnId, Project $project): Column
    {
        if (!$columnId) {
            $column = $project->columns()->orderBy('position')->first();
            abort_if(!$column, 422, 'O projeto precisa ter pelo menos uma coluna.');
            return $column;
        }
        return $project->columns()->findOrFail($columnId);
    }

    private function syncTags(Task $task, array $tagsInput): void
    {
        $ids = collect($tagsInput)
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->map(function ($input) use ($task) {
                $parts = explode('|', $input);
                $name = trim($parts[0]);
                $color = count($parts) > 1 ? trim($parts[1]) : '#94a3b8';
                
                // Try to find the tag by name, or create it with the specified color
                $tag = Tag::where('company_id', $task->company_id)->where('name', $name)->first();
                if ($tag) {
                    if (count($parts) > 1 && $tag->color !== $color) {
                        $tag->update(['color' => $color]);
                    }
                } else {
                    $tag = Tag::create([
                        'company_id' => $task->company_id,
                        'name' => $name,
                        'color' => $color
                    ]);
                }
                return $tag->id;
            });

        $task->tags()->sync($ids);
    }

    private function trackChanges(TaskRequest $request, Task $task, Column $column): void
    {
        $currentAssignees = $task->assignees()->pluck('users.id')->map(fn($id) => (int)$id)->toArray();
        $newAssignees = collect($request->input('assignees', []))->map(fn($id) => (int)$id)->toArray();
        sort($currentAssignees);
        sort($newAssignees);
        
        if ($currentAssignees !== $newAssignees) {
            $this->log($task, TaskActivity::TYPE_ASSIGNEE_CHANGED, 'alterou os responsáveis');
        }

        if ((string) $request->validated('publish_date') !== (string) optional($task->publish_date)->toDateString()) {
            $this->log($task, TaskActivity::TYPE_PUBLISH_DATE_CHANGED, 'alterou a data de publicação');
        }

        if ($column->id !== $task->column_id) {
            $this->log($task, TaskActivity::TYPE_COLUMN_CHANGED, "moveu para \"{$column->name}\"");
        }
    }

    private function log(Task $task, string $type, string $description, array $meta = []): void
    {
        $task->activities()->create([
            'company_id' => $task->company_id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }
}
