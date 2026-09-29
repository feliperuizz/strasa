<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MyTasksController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $tasks = Task::query()
            ->where('tasks.company_id', $request->user()->company_id)
            // O vínculo com a pessoa traz a prioridade que ela mesma definiu.
            ->join('task_user as minha', function ($join) use ($userId) {
                $join->on('minha.task_id', '=', 'tasks.id')->where('minha.user_id', $userId);
            })
            // Tarefa concluída sai da lista: aqui é o que a pessoa AINDA tem
            // para fazer. Vale tanto para o botão verde quanto para o arraste
            // até a coluna de concluído — os dois marcam is_published.
            ->where('tasks.is_published', false)
            ->select('tasks.*', 'minha.personal_position')
            ->with(['project.client', 'column', 'tags'])
            // Ordem que a pessoa arrastou; as ainda não ordenadas vão para o
            // fim do dia, pelo horário de publicação.
            ->orderByRaw('minha.personal_position IS NULL')
            ->orderBy('minha.personal_position')
            ->orderByRaw('tasks.publish_time IS NULL')
            ->orderBy('tasks.publish_time')
            ->orderBy('tasks.id')
            ->get();

        return view('my-tasks.index', [
            'tasks' => $tasks,
            'grupos' => $this->agruparPorDia($tasks),
        ]);
    }

    /**
     * Nova ordem de um dia, vinda do arraste. Só mexe na prioridade de quem
     * arrastou — os outros responsáveis do card mantêm a deles.
     */
    public function reorder(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $userId = $request->user()->id;

        $minhas = DB::table('task_user')
            ->where('user_id', $userId)
            ->whereIn('task_id', $dados['ids'])
            ->pluck('task_id')
            ->all();

        abort_if(count($minhas) !== count($dados['ids']), 422, 'Alguma tarefa não está com você.');

        DB::transaction(function () use ($dados, $userId) {
            foreach ($dados['ids'] as $posicao => $taskId) {
                DB::table('task_user')
                    ->where('user_id', $userId)
                    ->where('task_id', $taskId)
                    ->update(['personal_position' => $posicao]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Atrasadas → hoje → amanhã → cada dia seguinte → sem data. A ordem de
     * dentro de cada grupo é a da consulta (a prioridade pessoal).
     *
     * @return Collection<int, array{chave: string, titulo: string, subtitulo: ?string, tom: string, tarefas: Collection}>
     */
    private function agruparPorDia(Collection $tasks): Collection
    {
        $hoje = today();

        $chaveDe = function (Task $t) use ($hoje) {
            if (! $t->publish_date) {
                return 'sem-data';
            }

            return $t->publish_date->lt($hoje) ? 'atrasadas' : $t->publish_date->toDateString();
        };

        return $tasks
            ->groupBy($chaveDe)
            ->map(function (Collection $tarefas, string $chave) use ($hoje) {
                $grupo = ['chave' => $chave, 'subtitulo' => null, 'tom' => 'normal', 'tarefas' => $tarefas->values()];

                if ($chave === 'atrasadas') {
                    return $grupo + ['titulo' => 'Atrasadas', 'tom' => 'alerta', 'ordem' => '0'];
                }
                if ($chave === 'sem-data') {
                    return $grupo + ['titulo' => 'Sem data', 'tom' => 'apagado', 'ordem' => '9'];
                }

                $dia = Carbon::parse($chave);
                $titulo = match (true) {
                    $dia->isSameDay($hoje) => 'Hoje',
                    $dia->isSameDay($hoje->copy()->addDay()) => 'Amanhã',
                    default => ucfirst($dia->translatedFormat('l')),
                };

                return $grupo + [
                    'titulo' => $titulo,
                    'subtitulo' => $dia->translatedFormat('d \d\e F'),
                    'tom' => $dia->isSameDay($hoje) ? 'destaque' : 'normal',
                    'ordem' => '1'.$chave,
                ];
            })
            ->sortBy('ordem')
            ->values();
    }
}
