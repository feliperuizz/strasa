<?php

namespace App\Services;

use App\Models\Column;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskApproval;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\ClientApprovalResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Regras do painel de aprovação.
 *
 * Fica num serviço porque três caminhos diferentes disparam as mesmas
 * transições: arrastar o card para a coluna de aprovação, o botão "Enviar
 * para aprovação" e a resposta do próprio cliente no portal.
 */
class ApprovalService
{
    /**
     * Submete um card ao painel do cliente.
     *
     * Se já existe uma rodada aguardando resposta, ela é reaproveitada — não
     * adianta empilhar submissões da mesma peça. Depois de uma resposta,
     * um novo envio abre a rodada seguinte.
     */
    public function submit(Task $task, ?User $user = null, ?Column $originColumn = null): TaskApproval
    {
        $atual = $task->approvals()->first();

        if ($atual && $atual->isPending()) {
            return $atual;
        }

        $proximaRodada = ($atual?->round ?? 0) + 1;

        // A coluna de origem é para onde o card volta se o cliente pedir
        // ajuste. Quando o envio veio de um arraste, quem chama já informa a
        // coluna anterior; no botão, a origem é a coluna atual do card.
        $origem = $originColumn ?: $task->column;

        $aprovacao = TaskApproval::create([
            'company_id' => $task->company_id,
            'client_id' => $task->client_id,
            'task_id' => $task->id,
            'round' => $proximaRodada,
            'status' => TaskApproval::PENDING,
            'submitted_at' => now(),
            'submitted_by' => $user?->id,
            'origin_column_id' => $origem?->id,
        ]);

        $this->log($task, TaskActivity::TYPE_COLUMN_CHANGED,
            "enviou para aprovação do cliente (rodada {$proximaRodada})",
            ['approval_id' => $aprovacao->id], $user?->id);

        return $aprovacao;
    }

    /**
     * Cliente aprovou a peça.
     */
    public function approve(TaskApproval $aprovacao, string $reviewerName, ?string $feedback = null): void
    {
        DB::transaction(function () use ($aprovacao, $reviewerName, $feedback) {
            $aprovacao->forceFill([
                'status' => TaskApproval::APPROVED,
                'responded_at' => now(),
                'reviewer_name' => $reviewerName,
                'feedback' => $feedback ?: null,
            ])->save();

            $task = $aprovacao->task;

            $this->log($task, TaskActivity::TYPE_PUBLISHED,
                "{$reviewerName} aprovou a peça no painel do cliente",
                ['approval_id' => $aprovacao->id]);

            if (filled($feedback)) {
                $this->registrarComentarioDoCliente($task, $reviewerName, $feedback);
            }
        });

        $this->notificarResponsaveis($aprovacao, 'approved');
    }

    /**
     * Cliente pediu ajuste. O card volta para a coluna de onde saiu e o
     * feedback vira comentário no feed da equipe.
     */
    public function reject(TaskApproval $aprovacao, string $reviewerName, ?string $feedback = null): void
    {
        DB::transaction(function () use ($aprovacao, $reviewerName, $feedback) {
            $aprovacao->forceFill([
                'status' => TaskApproval::REJECTED,
                'responded_at' => now(),
                'reviewer_name' => $reviewerName,
                'feedback' => $feedback ?: null,
            ])->save();

            $task = $aprovacao->task;

            // Devolve o card para a coluna de ajustes.
            $destino = $this->colunaDeRetorno($aprovacao);

            if ($destino && $task->column_id !== $destino->id) {
                $task->forceFill([
                    'column_id' => $destino->id,
                    'is_published' => false,
                    'published_at' => null,
                ])->save();
            }

            if (filled($feedback)) {
                $task->forceFill(['rejection_reason' => $feedback])->save();
            }

            $this->log($task, TaskActivity::TYPE_REJECTED,
                "{$reviewerName} pediu ajuste no painel do cliente",
                ['approval_id' => $aprovacao->id, 'reason' => $feedback]);

            if (filled($feedback)) {
                $this->registrarComentarioDoCliente($task, $reviewerName, $feedback);
            }
        });

        $this->notificarResponsaveis($aprovacao, 'rejected');
    }

    /**
     * Comentário avulso do cliente (sem aprovar nem reprovar).
     */
    public function comment(TaskApproval $aprovacao, string $autor, string $corpo): TaskComment
    {
        $comentario = $this->registrarComentarioDoCliente($aprovacao->task, $autor, $corpo);

        $this->notificarResponsaveis($aprovacao, 'commented', $corpo);

        return $comentario;
    }

    /* --------------------------------------------------------------------- */
    /* Internos                                                              */
    /* --------------------------------------------------------------------- */

    /**
     * Para onde o card volta quando o cliente pede ajuste.
     *
     * Preferimos a coluna de onde ele saiu. Se ela não existe mais, cai na
     * primeira coluna do projeto que não seja de aprovação nem de publicação
     * — nunca deixamos o card preso na coluna de aprovação.
     */
    private function colunaDeRetorno(TaskApproval $aprovacao): ?Column
    {
        if ($aprovacao->origin_column_id) {
            $origem = Column::withoutGlobalScopes()
                ->where('id', $aprovacao->origin_column_id)
                ->where('company_id', $aprovacao->company_id)
                ->first();

            if ($origem && ! $origem->is_approval_column) {
                return $origem;
            }
        }

        return Column::withoutGlobalScopes()
            ->where('company_id', $aprovacao->company_id)
            ->where('project_id', $aprovacao->task->project_id)
            ->where('is_approval_column', false)
            ->where('marks_published', false)
            ->orderBy('position')
            ->first();
    }

    private function registrarComentarioDoCliente(Task $task, string $autor, string $corpo): TaskComment
    {
        return TaskComment::create([
            'company_id' => $task->company_id,
            'task_id' => $task->id,
            'user_id' => null,
            'is_from_client' => true,
            'client_author_name' => $autor,
            'visible_to_client' => true,
            'body' => $corpo,
        ]);
    }

    private function log(Task $task, string $type, string $descricao, array $meta = [], ?int $userId = null): void
    {
        $task->activities()->create([
            'company_id' => $task->company_id,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'description' => $descricao,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Push para os responsáveis configurados no portal do cliente.
     *
     * Enviado de forma síncrona (a fila do projeto só roda a cada minuto pelo
     * cron) e dentro de try/catch: um push que falha nunca pode derrubar a
     * aprovação que o cliente acabou de fazer.
     */
    private function notificarResponsaveis(TaskApproval $aprovacao, string $tipo, ?string $trecho = null): void
    {
        try {
            $portal = $aprovacao->client->portal;

            if (! $portal) {
                return;
            }

            $destinatarios = $portal->notifiables();

            if ($destinatarios->isEmpty()) {
                return;
            }

            foreach ($destinatarios as $usuario) {
                $usuario->notify(new ClientApprovalResponse($aprovacao, $tipo, $trecho));
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao notificar resposta do cliente no portal', [
                'approval_id' => $aprovacao->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
