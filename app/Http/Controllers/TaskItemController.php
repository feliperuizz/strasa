<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskItemController extends Controller
{
    public function store(Request $request, Task $task)
    {
        Gate::authorize('update', $task->project);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'assignee_id' => ['nullable', 'integer', $this->membroDaEmpresa($task)],
            'due_date' => ['nullable', 'date'],
        ]);

        $position = $task->items()->max('position') ?? 0;

        $item = $task->items()->create([
            'description' => $validated['description'],
            'position' => $position + 1,
            'is_completed' => false,
            'assignee_id' => $validated['assignee_id'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $extras = array_filter([
            $item->assignee_id ? 'responsável: '.User::find($item->assignee_id)?->name : null,
            $item->due_date ? 'prazo: '.$item->due_date->format('d/m/Y') : null,
        ]);

        TaskActivity::registrar($task, TaskActivity::TYPE_CHECKLIST_ADDED,
            'incluiu no checklist: "'.Str::limit($item->description, 80).'"'
            .($extras ? ' ('.implode(', ', $extras).')' : ''));

        return response()->json([
            'message' => 'Item adicionado.',
            'item' => $item,
        ]);
    }

    public function update(Request $request, TaskItem $item)
    {
        Gate::authorize('update', $item->task->project);

        // Cada controle do item salva sozinho (checkbox, responsável, data), então
        // todos os campos são opcionais e só o que veio no pedido é alterado.
        $validated = $request->validate([
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'is_completed' => ['sometimes', 'boolean'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', $this->membroDaEmpresa($item->task)],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $resumo = '"'.Str::limit($item->description, 80).'"';

        if (array_key_exists('description', $validated)) {
            $novo = trim($validated['description']);

            if ($novo !== $item->description) {
                TaskActivity::registrar($item->task, TaskActivity::TYPE_DESCRIPTION_CHANGED,
                    'renomeou o item '.$resumo.' para "'.Str::limit($novo, 80).'"');

                $item->description = $novo;
                $resumo = '"'.Str::limit($novo, 80).'"';
            }
        }

        if (array_key_exists('is_completed', $validated)) {
            // Marcar o item é o "terminei a minha parte" de quem está no card.
            // A tela reenvia o estado atual em alguns cliques; só vira histórico
            // quando o item realmente trocou de estado.
            $concluiu = (bool) $validated['is_completed'];

            if ((bool) $item->is_completed !== $concluiu) {
                $item->is_completed = $concluiu;

                TaskActivity::registrar(
                    $item->task,
                    $concluiu ? TaskActivity::TYPE_CHECKLIST_DONE : TaskActivity::TYPE_CHECKLIST_REOPENED,
                    ($concluiu ? 'concluiu ' : 'reabriu ').$resumo
                );
            }
        }

        if (array_key_exists('assignee_id', $validated)) {
            $novo = $validated['assignee_id'] ? (int) $validated['assignee_id'] : null;

            if ($novo !== $item->assignee_id) {
                $item->assignee_id = $novo;

                $nome = $novo ? User::find($novo)?->name : null;
                TaskActivity::registrar($item->task, TaskActivity::TYPE_ASSIGNEE_CHANGED,
                    $nome ? "definiu {$nome} como responsável por {$resumo}" : "tirou o responsável de {$resumo}");
            }
        }

        if (array_key_exists('due_date', $validated)) {
            $nova = $validated['due_date'] ? substr((string) $validated['due_date'], 0, 10) : null;
            $atual = $item->due_date?->format('Y-m-d');

            if ($nova !== $atual) {
                $item->due_date = $nova;

                TaskActivity::registrar($item->task, TaskActivity::TYPE_PUBLISH_DATE_CHANGED,
                    $nova
                        ? "marcou {$resumo} para ".\Carbon\Carbon::parse($nova)->format('d/m/Y')
                        : "tirou a data de {$resumo}");
            }
        }

        $item->save();

        return response()->json([
            'message' => 'Item atualizado.',
            'item' => $item->fresh(),
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
            'message' => 'Item excluído.',
        ]);
    }

    /** O responsável precisa ser alguém da mesma empresa da tarefa. */
    private function membroDaEmpresa(Task $task): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('users', 'id')->where('company_id', $task->company_id);
    }
}
