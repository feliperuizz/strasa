<?php

namespace App\Http\Middleware;

use App\Models\ClientPortal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarda das rotas do painel do cliente.
 *
 * ATENÇÃO — o CompanyScope só filtra quando existe usuário autenticado, e
 * aqui nunca existe. Por isso este middleware é a única porta de entrada: ele
 * resolve o portal a partir do token, confirma que a sessão já validou o
 * código e injeta o portal resolvido na request. Os controllers do portal
 * partem SEMPRE desse objeto e nunca consultam models por id vindo do
 * usuário, para que nenhum dado de outra empresa seja alcançável.
 */
class EnsurePortalAccess
{
    /** Chave de sessão que marca um portal como liberado. */
    public static function sessionKey(string $token): string
    {
        return 'portal_ok_'.sha1($token);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->route('token');

        $portal = ClientPortal::withoutGlobalScopes()
            ->with('client')
            ->where('token', $token)
            ->first();

        if (! $portal || ! $portal->client) {
            abort(404);
        }

        if (! $portal->is_active) {
            return response()->view('portal.revoked', ['portal' => $portal], 403);
        }

        if (! $request->session()->get(self::sessionKey($token))) {
            return redirect()->route('portal.login', $token);
        }

        // Disponibiliza o portal já resolvido e escopado para os controllers.
        $request->attributes->set('portal', $portal);

        return $next($request);
    }
}
