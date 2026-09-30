<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AutenticaChaveApi;
use App\Http\Resources\Api\ClientResource;
use App\Http\Resources\Api\PostResource;
use App\Http\Resources\Api\PublicationResource;
use App\Models\ApiToken;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskPublication;
use App\Services\AttachmentStreamer;
use App\Services\RetornoDePostagem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * API de postagem automática (v1).
 *
 * Quem chama é o sistema parceiro que publica nas redes. Não existe usuário
 * logado aqui: tudo é filtrado pela chave (empresa + clientes permitidos),
 * explicitamente — o escopo global de empresa só age com usuário logado.
 */
class PostagemController extends Controller
{
    /** GET /me — confere a chave e mostra o que ela enxerga. */
    public function me(Request $request): JsonResponse
    {
        $chave = $this->chave($request);

        return response()->json(['data' => [
            'token_name' => $chave->name,
            'token_prefix' => $chave->token_prefix,
            'company' => $chave->company?->name,
            'scope' => $chave->all_clients ? 'all_clients' : 'selected_clients',
            'clients' => ClientResource::collection($chave->clientesPermitidos()->orderBy('name')->get())->resolve($request),
            'auto_complete_on_published' => (bool) $chave->auto_complete,
            'expires_at' => $chave->expires_at?->toIso8601String(),
            'rate_limit_per_minute' => (int) config('services.api_postagem.limite_por_minuto', 120),
            'media_url_ttl_days' => \App\Http\Resources\Api\MediaResource::VALIDADE_EM_DIAS,
            'timezone' => PostResource::FUSO,
            'server_time' => now()->toIso8601String(),
        ]]);
    }

    /** GET /clients — clientes que a chave pode ver. */
    public function clients(Request $request): JsonResponse
    {
        $clientes = $this->chave($request)->clientesPermitidos()->orderBy('name')->get();

        return response()->json(['data' => ClientResource::collection($clientes)->resolve($request)]);
    }

    /** GET /posts — lista paginada, por padrão só o que está pronto para ir ao ar. */
    public function posts(Request $request): JsonResponse|Response
    {
        $chave = $this->chave($request);

        $filtros = $request->validate([
            'status' => ['nullable', Rule::in(['ready', 'pending', 'published', 'all'])],
            'client_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! empty($filtros['client_id']) && ! $chave->permiteCliente((int) $filtros['client_id'])) {
            return AutenticaChaveApi::erro(403, 'client_not_in_scope', 'Esta chave não tem acesso ao cliente '.$filtros['client_id'].'.');
        }

        $status = $filtros['status'] ?? 'ready';

        $consulta = $this->postsDaChave($chave)
            ->when($status === 'ready', fn (Builder $q) => $q->where('tasks.is_published', false)
                ->whereNotNull('tasks.publish_date')
                ->whereNotNull('tasks.publish_time')
                ->whereHas('column', fn ($c) => $c->withoutGlobalScopes()->where('is_publish_column', true)))
            ->when($status === 'pending', fn (Builder $q) => $q->where('tasks.is_published', false))
            ->when($status === 'published', fn (Builder $q) => $q->where('tasks.is_published', true))
            ->when($filtros['client_id'] ?? null, fn (Builder $q, $id) => $q->where('tasks.client_id', $id))
            ->when($filtros['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('tasks.publish_date', '>=', $d))
            ->when($filtros['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('tasks.publish_date', '<=', $d))
            ->when($filtros['updated_since'] ?? null, fn (Builder $q, $d) => $q->where('tasks.updated_at', '>', Carbon::parse($d, PostResource::FUSO)->utc()))
            ->orderByRaw('tasks.publish_date IS NULL')
            ->orderBy('tasks.publish_date')
            ->orderByRaw('tasks.publish_time IS NULL')
            ->orderBy('tasks.publish_time')
            ->orderBy('tasks.id');

        $pagina = $consulta->paginate((int) ($filtros['per_page'] ?? 50))->withQueryString();

        return response()->json([
            'data' => PostResource::collection($pagina->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'per_page' => $pagina->perPage(),
                'total' => $pagina->total(),
                'last_page' => $pagina->lastPage(),
                'status_filter' => $status,
                'server_time' => now()->toIso8601String(),
            ],
            'links' => [
                'next' => $pagina->nextPageUrl(),
                'prev' => $pagina->previousPageUrl(),
            ],
        ]);
    }

    /** GET /posts/{id} — um post, com links de mídia novos. Use antes de publicar. */
    public function post(Request $request, int $post): JsonResponse|Response
    {
        $task = $this->postsDaChave($this->chave($request))->where('tasks.id', $post)->first();

        if (! $task) {
            return AutenticaChaveApi::erro(404, 'post_not_found', 'Post não encontrado (não existe, foi excluído ou não pertence a um cliente desta chave).');
        }

        return response()->json(['data' => PostResource::make($task)->resolve($request)]);
    }

    /** POST /posts/{id}/publications — webhook de retorno: como ficou a postagem numa rede. */
    public function reportarPublicacao(Request $request, int $post, RetornoDePostagem $retorno): JsonResponse|Response
    {
        $chave = $this->chave($request);

        $dados = $request->validate([
            'network' => ['required', 'string', Rule::in(array_keys(TaskPublication::REDES))],
            'status' => ['required', 'string', Rule::in(array_keys(TaskPublication::STATUS))],
            'external_id' => ['nullable', 'string', 'max:191'],
            'permalink' => ['nullable', 'url', 'max:2048'],
            'error_message' => ['nullable', 'string', 'max:2000'],
            'scheduled_for' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
        ]);

        $task = $this->postsDaChave($chave)->where('tasks.id', $post)->first();

        if (! $task) {
            return AutenticaChaveApi::erro(404, 'post_not_found', 'Post não encontrado (não existe, foi excluído ou não pertence a um cliente desta chave).');
        }

        [$publicacao, $concluiu] = $retorno->registrar($chave, $task, $dados);
        $task->refresh()->load('column');

        return response()->json([
            'data' => PublicationResource::make($publicacao)->resolve($request),
            'post' => [
                'id' => $task->id,
                'is_published' => (bool) $task->is_published,
                'marked_published_now' => $concluiu,
                'column' => $task->column?->name,
            ],
        ]);
    }

    /** GET /media/{id}?expires=..&k=..&signature=.. — arquivo original, por link assinado (sem cabeçalho). */
    public function media(Request $request, int $attachment): Response
    {
        // A assinatura (middleware "signed") já garante que o link não foi
        // alterado nem expirou. Aqui: a chave que gerou o link ainda vale?
        $chave = ApiToken::withoutGlobalScopes()->find((int) $request->query('k'));

        if (! $chave || ! $chave->estaAtiva()) {
            return AutenticaChaveApi::erro(401, 'token_revoked', 'A chave que gerou este link não está mais ativa.');
        }

        $anexo = TaskAttachment::withoutGlobalScopes()
            ->where('id', $attachment)
            ->where('company_id', $chave->company_id)
            ->first();

        $task = $anexo ? Task::withoutGlobalScopes()->find($anexo->task_id) : null;

        if (! $anexo || ! $task || ! $chave->permiteCliente((int) $task->client_id)) {
            return AutenticaChaveApi::erro(404, 'media_not_found', 'Arquivo não encontrado.');
        }

        return app(AttachmentStreamer::class)->stream($request, $anexo);
    }

    /* --------------------------------------------------------------------- */

    private function chave(Request $request): ApiToken
    {
        return $request->attributes->get('api_token');
    }

    /** Posts que a chave enxerga: da empresa dela e só dos clientes permitidos. */
    private function postsDaChave(ApiToken $chave): Builder
    {
        return Task::withoutGlobalScopes()
            ->select('tasks.*')
            ->where('tasks.company_id', $chave->company_id)
            ->whereIn('tasks.client_id', $chave->clientesPermitidos()->select('clients.id'))
            ->with([
                'client' => fn ($q) => $q->withoutGlobalScopes(),
                'project' => fn ($q) => $q->withoutGlobalScopes(),
                'column' => fn ($q) => $q->withoutGlobalScopes(),
                'tags' => fn ($q) => $q->withoutGlobalScopes(),
                'attachments' => fn ($q) => $q->withoutGlobalScopes(),
                'folders' => fn ($q) => $q->withoutGlobalScopes(),
                'approvals' => fn ($q) => $q->withoutGlobalScopes(),
                // Cada parceiro vê só o que ele mesmo informou.
                'publications' => fn ($q) => $q->withoutGlobalScopes()->where('api_token_id', $chave->id),
            ]);
    }
}
