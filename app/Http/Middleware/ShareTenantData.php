<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\TaskApproval;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware multi-tenant da camada de apresentação.
 *
 * - Garante que o usuário logado pertence a uma empresa (senão 403).
 * - Compartilha com TODAS as views o tenant atual e a lista de clientes/
 *   projetos usada na sidebar (estilo Asana).
 *
 * O isolamento de DADOS por empresa é feito pelo CompanyScope global; aqui
 * apenas preparamos o contexto visual da aplicação.
 *
 * DESEMPENHO: isto roda em toda página. Como o MySQL da hospedagem cobra
 * dezenas de milissegundos por query, as consultas ficam em cache (arquivo,
 * ~0,1ms) e são invalidadas por eventos nos models — ver Client, Project e
 * TaskApproval. Assim o menu nunca fica desatualizado, mas também não custa
 * uma ida ao banco por clique.
 */
class ShareTenantData
{
    /** Chave do cache da sidebar de uma empresa. */
    public static function chaveSidebar(int $companyId): string
    {
        return "tenant:{$companyId}:sidebar";
    }

    /** Chave do contador de aprovações pendentes de uma empresa. */
    public static function chaveAprovacoes(int $companyId): string
    {
        return "tenant:{$companyId}:aprovacoes-pendentes";
    }

    /** Chamado pelos models quando algo do menu muda. */
    public static function esquecerSidebar(?int $companyId): void
    {
        if ($companyId) {
            Cache::forget(self::chaveSidebar($companyId));
        }
    }

    public static function esquecerAprovacoes(?int $companyId): void
    {
        if ($companyId) {
            Cache::forget(self::chaveAprovacoes($companyId));
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || $user->company_id === null, 403, 'Usuário sem empresa associada.');

        $companyId = $user->company_id;

        // Clientes ativos + projetos ativos para a sidebar (evita N+1 com with()).
        $sidebarClients = Cache::remember(
            self::chaveSidebar($companyId),
            now()->addHours(6),
            fn () => Client::query()
                ->active()
                ->with(['projects' => fn ($q) => $q->active()->orderBy('name')])
                ->orderBy('name')
                ->get()
        );

        // A ordem da sidebar é preferência de cada usuário, então é aplicada
        // depois do cache, que é compartilhado pela empresa inteira.
        $order = $user->sidebar_client_order;
        if (is_array($order) && ! empty($order)) {
            $sidebarClients = $sidebarClients->sortBy(function ($client) use ($order) {
                $pos = array_search($client->id, $order);

                return $pos !== false ? $pos : 99999;
            })->values();
        }

        // Badge da aba "Aprovações": peças aguardando resposta do cliente.
        $aguardandoAprovacao = Cache::remember(
            self::chaveAprovacoes($companyId),
            now()->addHours(6),
            fn () => TaskApproval::where('status', TaskApproval::PENDING)->count()
        );

        // $user->company dispara uma query por request se não for cacheado.
        $company = Cache::remember(
            "tenant:{$companyId}:company",
            now()->addHours(12),
            fn () => $user->company
        );

        View::share('currentCompany', $company);
        View::share('sidebarClients', $sidebarClients);
        View::share('aguardandoAprovacao', $aguardandoAprovacao);

        return $next($request);
    }
}
