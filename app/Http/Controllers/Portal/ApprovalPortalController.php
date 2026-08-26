<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePortalAccess;
use App\Models\ClientPortal;
use App\Models\Task;
use App\Models\TaskApproval;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Services\ApprovalService;
use App\Services\AttachmentStreamer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Painel de aprovação do cliente — acesso público por link + código.
 *
 * REGRA DE OURO DESTE ARQUIVO: não existe usuário autenticado aqui, e o
 * CompanyScope só filtra quando existe. Portanto nenhuma query pode partir de
 * um id enviado pelo cliente sem antes ser amarrada ao portal resolvido pelo
 * middleware. Todos os acessos passam por tasksDoPortal()/acharTask(), que
 * filtram por company_id + client_id do portal.
 */
class ApprovalPortalController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvals
    ) {}

    /* --------------------------------------------------------------------- */
    /* Entrada                                                               */
    /* --------------------------------------------------------------------- */

    public function showLogin(Request $request, string $token)
    {
        $portal = $this->portalPorToken($token);

        if (! $portal->is_active) {
            return response()->view('portal.revoked', ['portal' => $portal], 403);
        }

        // Já validou nesta sessão? Vai direto para o painel.
        if ($request->session()->get(EnsurePortalAccess::sessionKey($token))) {
            return redirect()->route('portal.index', $token);
        }

        return view('portal.login', [
            'portal' => $portal,
            'client' => $portal->client,
        ]);
    }

    public function login(Request $request, string $token)
    {
        $portal = $this->portalPorToken($token);

        abort_unless($portal->is_active, 403);

        // Trava de força bruta: o código tem 8 caracteres úteis, então sem
        // isto seria só questão de tempo. 6 tentativas por minuto por IP.
        $chave = 'portal:'.sha1($token.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($chave, 6)) {
            throw ValidationException::withMessages([
                'code' => 'Muitas tentativas. Aguarde '
                    .RateLimiter::availableIn($chave).' segundos e tente de novo.',
            ]);
        }

        $dados = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ]);

        if (! $portal->codeMatches($dados['code'])) {
            RateLimiter::hit($chave, 60);

            throw ValidationException::withMessages([
                'code' => 'Código incorreto. Confira a mensagem que você recebeu.',
            ]);
        }

        RateLimiter::clear($chave);

        $request->session()->regenerate();
        $request->session()->put(EnsurePortalAccess::sessionKey($token), true);

        $portal->registerAccess();

        return redirect()->route('portal.index', $token);
    }

    public function logout(Request $request, string $token)
    {
        $request->session()->forget(EnsurePortalAccess::sessionKey($token));

        return redirect()->route('portal.login', $token);
    }

    /* --------------------------------------------------------------------- */
    /* Painel                                                                */
    /* --------------------------------------------------------------------- */

    public function index(Request $request)
    {
        $portal = $this->portalDaRequest($request);

        $aprovacoes = $this->aprovacoesDoPortal($portal)->get();

        $pendentes = $aprovacoes->where('status', TaskApproval::PENDING);
        $respondidas = $aprovacoes->whereIn('status', [TaskApproval::APPROVED, TaskApproval::REJECTED]);

        return view('portal.index', [
            'portal' => $portal,
            'client' => $portal->client,
            'pendentes' => $pendentes,
            'respondidas' => $respondidas->sortByDesc('responded_at'),
        ]);
    }

    public function show(Request $request, string $token, int $approval)
    {
        $portal = $this->portalDaRequest($request);
        $aprovacao = $this->acharAprovacao($portal, $approval);

        $task = $aprovacao->task;
        $task->load(['attachments', 'items']);

        return view('portal.show', [
            'portal' => $portal,
            'client' => $portal->client,
            'approval' => $aprovacao,
            'task' => $task,
            'midias' => $task->approvalMedia(),
            'comentarios' => $this->comentariosVisiveis($task),
        ]);
    }

    /* --------------------------------------------------------------------- */
    /* Ações do cliente                                                      */
    /* --------------------------------------------------------------------- */

    public function approve(Request $request, string $token, int $approval)
    {
        $portal = $this->portalDaRequest($request);
        $aprovacao = $this->acharAprovacao($portal, $approval);

        abort_unless($aprovacao->isPending(), 409, 'Esta peça já foi respondida.');

        $dados = $request->validate([
            'reviewer_name' => ['required', 'string', 'max:120'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->approvals->approve($aprovacao, $dados['reviewer_name'], $dados['feedback'] ?? null);

        $this->lembrarNome($request, $dados['reviewer_name']);

        return redirect()
            ->route('portal.index', $token)
            ->with('portal_status', 'Peça aprovada. Obrigado!');
    }

    public function reject(Request $request, string $token, int $approval)
    {
        $portal = $this->portalDaRequest($request);
        $aprovacao = $this->acharAprovacao($portal, $approval);

        abort_unless($aprovacao->isPending(), 409, 'Esta peça já foi respondida.');

        $dados = $request->validate([
            'reviewer_name' => ['required', 'string', 'max:120'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->approvals->reject($aprovacao, $dados['reviewer_name'], $dados['feedback'] ?? null);

        $this->lembrarNome($request, $dados['reviewer_name']);

        return redirect()
            ->route('portal.index', $token)
            ->with('portal_status', 'Ajuste solicitado. Nossa equipe já foi avisada.');
    }

    public function comment(Request $request, string $token, int $approval)
    {
        $portal = $this->portalDaRequest($request);
        $aprovacao = $this->acharAprovacao($portal, $approval);

        $dados = $request->validate([
            'reviewer_name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $this->approvals->comment($aprovacao, $dados['reviewer_name'], $dados['body']);

        $this->lembrarNome($request, $dados['reviewer_name']);

        return redirect()
            ->route('portal.show', [$token, $aprovacao->id])
            ->with('portal_status', 'Comentário enviado para a equipe.');
    }

    /* --------------------------------------------------------------------- */
    /* Mídia                                                                 */
    /* --------------------------------------------------------------------- */

    /**
     * Serve a imagem/vídeo da peça.
     *
     * A rota autenticada de anexos exige usuário logado, então o portal tem a
     * sua própria — e ela só entrega arquivos de tasks que pertencem a este
     * cliente E que estão submetidas para aprovação.
     */
    public function media(Request $request, string $token, int $attachment)
    {
        $portal = $this->portalDaRequest($request);

        $anexo = TaskAttachment::withoutGlobalScopes()
            ->where('id', $attachment)
            ->where('company_id', $portal->company_id)
            ->first();

        abort_if(! $anexo, 404);

        // O anexo tem de pertencer a uma peça submetida deste cliente.
        $permitido = $this->tasksDoPortal($portal)
            ->where('tasks.id', $anexo->task_id)
            ->exists();

        abort_unless($permitido, 404);

        return app(AttachmentStreamer::class)->stream($request, $anexo);
    }

    /* --------------------------------------------------------------------- */
    /* Consultas escopadas                                                   */
    /* --------------------------------------------------------------------- */

    private function portalPorToken(string $token): ClientPortal
    {
        $portal = ClientPortal::withoutGlobalScopes()
            ->with('client')
            ->where('token', $token)
            ->first();

        abort_if(! $portal || ! $portal->client, 404);

        return $portal;
    }

    private function portalDaRequest(Request $request): ClientPortal
    {
        $portal = $request->attributes->get('portal');

        abort_if(! $portal instanceof ClientPortal, 403);

        return $portal;
    }

    /** Query base: só tasks submetidas ao painel deste cliente. */
    private function tasksDoPortal(ClientPortal $portal)
    {
        return Task::withoutGlobalScopes()
            ->where('tasks.company_id', $portal->company_id)
            ->where('tasks.client_id', $portal->client_id)
            ->whereExists(function ($q) use ($portal) {
                $q->selectRaw('1')
                    ->from('task_approvals')
                    ->whereColumn('task_approvals.task_id', 'tasks.id')
                    ->where('task_approvals.company_id', $portal->company_id)
                    ->where('task_approvals.client_id', $portal->client_id);
            });
    }

    private function aprovacoesDoPortal(ClientPortal $portal)
    {
        return TaskApproval::withoutGlobalScopes()
            ->where('company_id', $portal->company_id)
            ->where('client_id', $portal->client_id)
            ->with(['task' => fn ($q) => $q->withoutGlobalScopes()->with('attachments')])
            ->orderByDesc('submitted_at');
    }

    private function acharAprovacao(ClientPortal $portal, int $id): TaskApproval
    {
        $aprovacao = $this->aprovacoesDoPortal($portal)->where('task_approvals.id', $id)->first();

        abort_if(! $aprovacao || ! $aprovacao->task, 404);

        return $aprovacao;
    }

    /**
     * O cliente vê os próprios comentários e apenas os nossos que foram
     * explicitamente marcados como resposta a ele. O histórico interno da
     * equipe nunca sai daqui.
     */
    private function comentariosVisiveis(Task $task)
    {
        return TaskComment::withoutGlobalScopes()
            ->where('company_id', $task->company_id)
            ->where('task_id', $task->id)
            ->where(function ($q) {
                $q->where('is_from_client', true)
                    ->orWhere('visible_to_client', true);
            })
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get();
    }

    private function lembrarNome(Request $request, string $nome): void
    {
        $request->session()->put('portal_reviewer_name', $nome);
    }
}
