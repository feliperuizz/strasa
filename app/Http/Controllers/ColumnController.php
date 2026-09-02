<?php

namespace App\Http\Controllers;

use App\Models\Column;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ColumnController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $project->columns()->create([
            'company_id' => $project->company_id,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#64748b',
            'position' => (int) $project->columns()->max('position') + 1,
        ]);

        $this->forgetCache($project);

        return back()->with('status', 'Coluna adicionada.');
    }

    public function update(Request $request, Column $column): RedirectResponse
    {
        $this->authorize('update', $column->project);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
            'is_publish_column' => ['nullable', 'boolean'],
            'is_approval_column' => ['nullable', 'boolean'],
            'marks_published' => ['nullable', 'boolean'],
        ]);

        $ehConcluido = (bool) ($data['marks_published'] ?? false);

        DB::transaction(function () use ($column, $data, $ehConcluido) {
            // Só pode existir UMA coluna de concluído por projeto: é ela que o
            // botão de concluir usa como destino. Duas seriam ambíguas.
            if ($ehConcluido) {
                Column::where('project_id', $column->project_id)
                    ->where('id', '!=', $column->id)
                    ->update(['marks_published' => false]);
            }

            $column->update([
                'name' => $data['name'],
                'color' => $data['color'] ?? $column->color,
                'is_publish_column' => $data['is_publish_column'] ?? false,
                'is_approval_column' => $data['is_approval_column'] ?? false,
                'marks_published' => $ehConcluido,
            ]);
        });

        $this->forgetCache($column->project);

        return back()->with('status', 'Coluna atualizada.');
    }

    public function destroy(Column $column): RedirectResponse
    {
        $project = $column->project;
        $this->authorize('update', $project);

        if ($column->tasks()->exists()) {
            return back()->withErrors(['column' => 'Mova ou exclua as tarefas desta coluna antes de removê-la.']);
        }

        $column->delete();
        $this->forgetCache($project);

        return back()->with('status', 'Coluna removida.');
    }

    /** Reordena as colunas a partir de uma lista de IDs (drag & drop). */
    public function reorder(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*' => ['integer'],
        ]);

        $valid = $project->columns()->pluck('id')->all();

        foreach ($data['columns'] as $position => $columnId) {
            if (in_array((int) $columnId, $valid, true)) {
                Column::where('id', $columnId)->update(['position' => $position]);
            }
        }

        $this->forgetCache($project);

        return back()->with('status', 'Colunas reordenadas.');
    }

    private function forgetCache(Project $project): void
    {
        cache()->forget("project:{$project->id}:columns");
    }
}
