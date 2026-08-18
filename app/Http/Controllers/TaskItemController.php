<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        $item->update([
            'is_completed' => $validated['is_completed'],
        ]);

        return response()->json([
            'message' => 'Item atualizado.',
            'item' => $item
        ]);
    }

    public function destroy(TaskItem $item)
    {
        Gate::authorize('update', $item->task->project);

        $item->delete();

        return response()->json([
            'message' => 'Item excluído.'
        ]);
    }
}
