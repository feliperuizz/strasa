<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskFolder;
use Illuminate\Http\Request;

class TaskFolderController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder = $task->folders()->create([
            'company_id' => $task->company_id,
            'name' => $validated['name'],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['folder' => $folder, 'message' => 'Pasta criada com sucesso.']);
        }

        return back()->with('status', 'Pasta criada.');
    }

    public function update(Request $request, TaskFolder $folder)
    {
        $this->authorize('update', $folder->task);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder->update(['name' => $validated['name']]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['folder' => $folder, 'message' => 'Pasta renomeada com sucesso.']);
        }

        return back()->with('status', 'Pasta renomeada.');
    }

    public function destroy(Request $request, TaskFolder $folder)
    {
        $this->authorize('update', $folder->task);

        // Os anexos que estavam na pasta não são apagados, pois a FK tem nullOnDelete().
        // Eles voltarão a ser "soltos" (sem pasta).
        $folder->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Pasta excluída com sucesso.']);
        }

        return back()->with('status', 'Pasta excluída.');
    }
}
