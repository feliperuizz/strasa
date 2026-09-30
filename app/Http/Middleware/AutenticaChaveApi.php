<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticação da API de postagem automática: "Authorization: Bearer str_...".
 *
 * Não há usuário logado nessa API — quem chama é o sistema parceiro. A chave
 * encontrada vai para $request->attributes('api_token') e todo o resto da API
 * filtra por ela (empresa e clientes permitidos).
 *
 * Implementa AuthenticatesRequests para o Laravel rodá-lo ANTES do limitador
 * (throttle): o limite é por chave, e sem isto ele contava por IP.
 */
class AutenticaChaveApi implements AuthenticatesRequests
{
    /** Tentativas com chave errada por IP, por minuto, antes de bloquear. */
    private const TENTATIVAS_ERRADAS_POR_MINUTO = 30;

    public function handle(Request $request, Closure $next): Response
    {
        // Quem erra a chave muitas vezes seguidas é bloqueado por um minuto:
        // adivinhar uma chave de 44 caracteres já é inviável, mas assim nem
        // se chega a tentar, e o banco não é martelado.
        $contador = 'api-chave-errada:'.$request->ip();
        if (RateLimiter::tooManyAttempts($contador, self::TENTATIVAS_ERRADAS_POR_MINUTO)) {
            return self::erro(429, 'rate_limited', 'Muitas tentativas com chave inválida. Aguarde o tempo indicado em Retry-After.')
                ->withHeaders(['Retry-After' => RateLimiter::availableIn($contador)]);
        }

        $segredo = (string) $request->bearerToken();

        if ($segredo === '') {
            return self::erro(401, 'unauthenticated', 'Envie a chave no cabeçalho "Authorization: Bearer <sua chave>".');
        }

        $chave = ApiToken::peloSegredo($segredo);

        if (! $chave) {
            RateLimiter::hit($contador, 60);

            return self::erro(401, 'invalid_token', 'Chave de API inválida.');
        }

        if ($chave->estaRevogada()) {
            return self::erro(401, 'token_revoked', 'Esta chave foi revogada. Peça uma nova ao administrador do STRASA.');
        }

        if ($chave->estaExpirada()) {
            return self::erro(401, 'token_expired', 'Esta chave expirou. Peça uma nova ao administrador do STRASA.');
        }

        if (! $chave->aceitaIp($request->ip())) {
            return self::erro(403, 'ip_not_allowed', 'O IP '.$request->ip().' não está autorizado a usar esta chave.');
        }

        // Último uso: grava no máximo uma vez por minuto, não a cada chamada.
        if (! $chave->last_used_at || $chave->last_used_at->lt(now()->subMinute()) || $chave->last_used_ip !== $request->ip()) {
            $chave->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        }

        $request->attributes->set('api_token', $chave);

        return $next($request);
    }

    /** Formato único de erro da API: {"error": {"code": "...", "message": "..."}}. */
    public static function erro(int $status, string $codigo, string $mensagem, array $extra = []): Response
    {
        $resposta = response()->json(['error' => array_merge(['code' => $codigo, 'message' => $mensagem], $extra)], $status);

        if ($status === 401) {
            $resposta->headers->set('WWW-Authenticate', 'Bearer realm="strasa-api"');
        }

        return $resposta;
    }
}
