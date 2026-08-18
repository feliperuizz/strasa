<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
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
 */
class ShareTenantData
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || $user->company_id === null, 403, 'Usuário sem empresa associada.');

        // Clientes ativos + projetos ativos para a sidebar (evita N+1 com with()).
        $sidebarClients = Client::query()
            ->active()
            ->with(['projects' => fn ($q) => $q->active()->orderBy('name')])
            ->orderBy('name')
            ->get();

        $order = $user->sidebar_client_order;
        if (is_array($order) && !empty($order)) {
            $sidebarClients = $sidebarClients->sortBy(function($client) use ($order) {
                $pos = array_search($client->id, $order);
                return $pos !== false ? $pos : 99999;
            })->values();
        }

        View::share('currentCompany', $user->company);
        View::share('sidebarClients', $sidebarClients);

        return $next($request);
    }
}
