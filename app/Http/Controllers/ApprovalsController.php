<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskApproval;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Aba "Aprovações" — acompanhamento das respostas dos clientes,
 * mais as ações de enviar/cancelar uma peça a partir do card.
 */
class ApprovalsController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvals
    ) {}

    public function index(Request $request)
    {
        $empresa = $request->user()->company_id;

        $filtros = $request->validate([
            'client' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'periodo' => ['nullable', 'in:7,30,90,todos'],
        ]);

        $periodo = $filtros['periodo'] ?? '30';

        $query = TaskApproval::query()
            ->where('company_id', $empresa)
            ->with([
                'client:id,name,logo_path,logo_disk,color',
                'task:id,title,content_type,project_id,client_id',
                'submitter:id,name',
            ])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('responded_at')
            ->orderByDesc('submitted_at');

        if (! empty($filtros['client'])) {
            $query->where('client_id', $filtros['client']);
        }

        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        if ($periodo !== 'todos') {
            $query->where('submitted_at', '>=', now()->subDays((int) $periodo));
        }

        $aprovacoes = $query->paginate(30)->withQueryString();

        // Contadores do topo, sempre sobre o período escolhido.
        $base = TaskApproval::query()->where('company_id', $empresa);

        if ($periodo !== 'todos') {
            $base->where('submitted_at', '>=', now()->subDays((int) $periodo));
        }

        $resumo = [
            'pendentes' => (clone $base)->where('status', TaskApproval::PENDING)->count(),
            'aprovadas' => (clone $base)->where('status', TaskApproval::APPROVED)->count(),
            'ajustes' => (clone $base)->where('status', TaskApproval::REJECTED)->count(),
        ];

        return view('approvals.index', [
            'aprovacoes' => $aprovacoes,
            'resumo' => $resumo,
            'clientes' => Client::active()->orderBy('name')->get(['id', 'name']),
            'filtros' => $filtros + ['periodo' => $periodo],
        ]);
    }

    /** Botão "Enviar para aprovação" dentro do card. */
    public function submit(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        if (! $task->client_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Esta tarefa não está ligada a um cliente.',
            ], 422);
        }

        $portal = $task->client?->portal;

        if (! $portal) {
            return response()->json([
                'ok' => false,
                'message' => 'Crie o painel de aprovação deste cliente antes de enviar peças.',
            ], 422);
        }

        $aprovacao = $this->approvals->submit($task, $request->user());

        return response()->json([
            'ok' => true,
            'status' => $aprovacao->status,
            'round' => $aprovacao->round,
            'message' => "Peça enviada para o painel de {$task->client->name}.",
        ]);
    }

    /** Retira do painel uma peça que ainda não foi respondida. */
    public function cancel(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $aprovacao = $task->approvals()->first();

        if (! $aprovacao || ! $aprovacao->isPending()) {
            return response()->json([
                'ok' => false,
                'message' => 'Não há envio pendente para cancelar.',
            ], 422);
        }

        $aprovacao->delete();

        return response()->json(['ok' => true, 'message' => 'Envio cancelado.']);
    }

    /** Marca um comentário interno como visível para o cliente. */
    public function toggleCommentVisibility(Request $request, \App\Models\TaskComment $comment): JsonResponse
    {
        abort_unless($comment->company_id === $request->user()->company_id, 403);

        // Comentário do próprio cliente não é nosso para esconder.
        abort_if($comment->is_from_client, 422);

        $comment->forceFill(['visible_to_client' => ! $comment->visible_to_client])->save();

        return response()->json([
            'ok' => true,
            'visible_to_client' => $comment->visible_to_client,
        ]);
    }
}
