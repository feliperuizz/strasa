<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TaskItemController extends Controller
{
    public function store(Request $request, Task $task)
    {
        Gate::authorize('update', $task->project);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $position = $task->items()->max('position') ?? 0;

        $item = $task->items()->create([
            'description' => $validated['description'],
            'position' => $position + 1,
            'is_completed' => false,
        ]);

        TaskActivity::registrar($task, TaskActivity::TYPE_CHECKLIST_ADDED,
            'incluiu no checklist: "'.Str::limit($item->description, 80).'"');

        return response()->json([
            'message' => 'Item adicionado.',
            'item' => $item
        ]);
    }

    public function update(Request $request, TaskItem $item)
    {
        Gate::authorize('update', $item->task->project);

        $validated = $request->validate([
            'is_completed' => 'required|boolean',
        ]);

        // Marcar o item é o "terminei a minha parte" de quem está no card.
        // Antes isso não deixava rastro nenhum para quem acompanha de fora.
        $concluiu = (bool) $validated['is_completed'];
        $mudou = (bool) $item->is_completed !== $concluiu;

        $item->update([
            'is_completed' => $concluiu,
        ]);

        // A tela reenvia o estado atual em alguns cliques; só vira histórico
        // quando o item realmente trocou de estado.
        if ($mudou) {
            TaskActivity::registrar(
                $item->task,
                $concluiu ? TaskActivity::TYPE_CHECKLIST_DONE : TaskActivity::TYPE_CHECKLIST_REOPENED,
                ($concluiu ? 'concluiu ' : 'reabriu ').'"'.Str::limit($item->description, 80).'"'
            );
        }

        return response()->json([
            'message' => 'Item atualizado.',
            'item' => $item
        ]);
    }

    public function destroy(TaskItem $item)
    {
        Gate::authorize('update', $item->task->project);

        $descricao = $item->description;
        $task = $item->task;

        $item->delete();

        TaskActivity::registrar($task, TaskActivity::TYPE_CHECKLIST_REMOVED,
            'removeu do checklist: "'.Str::limit($descricao, 80).'"');

        return response()->json([
            'message' => 'Item excluído.'
        ]);
    }
}
