<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ShareTenantData::class,
            'portal' => \App\Http\Middleware\EnsurePortalAccess::class,
            'api.chave' => \App\Http\Middleware\AutenticaChaveApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API de postagem: todo erro sai em JSON, no mesmo formato
        // {"error": {"code", "message"}}, mesmo sem o parceiro mandar
        // "Accept: application/json". Nada de página HTML de erro para robô.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            $erro = fn (int $status, string $codigo, string $mensagem, array $extra = [], array $headers = []) => response()
                ->json(['error' => array_merge(['code' => $codigo, 'message' => $mensagem], $extra)], $status, $headers);

            return match (true) {
                $e instanceof \Illuminate\Validation\ValidationException => $erro(422, 'validation_failed',
                    'Dados inválidos. Veja "details".', ['details' => $e->errors()]),
                $e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException => $erro(429, 'rate_limited',
                    'Muitas requisições. Aguarde o tempo indicado em Retry-After.', [], $e->getHeaders()),
                $e instanceof \Illuminate\Routing\Exceptions\InvalidSignatureException => $erro(403, 'invalid_or_expired_link',
                    'Link de mídia inválido ou expirado. Busque o post de novo (GET /posts/{id}) para receber links novos.'),
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException,
                $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => $erro(404, 'not_found', 'Recurso não encontrado.'),
                $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException => $erro(405, 'method_not_allowed', 'Método HTTP não permitido nesta rota.'),
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $erro($e->getStatusCode(), 'http_error',
                    $e->getMessage() ?: 'Erro HTTP '.$e->getStatusCode().'.', [], $e->getHeaders()),
                default => (function () use ($e, $erro) {
                    report($e);

                    return $erro(500, 'server_error', 'Erro interno no STRASA. Tente de novo em instantes; se persistir, avise a agência.');
                })(),
            };
        });

        // Quando o arquivo passa do post_max_size, o PHP descarta o corpo da
        // request antes do app ver qualquer coisa. Sem isto o usuário recebia
        // um erro genérico sem dizer que o problema era o tamanho.
        $exceptions->render(function (PostTooLargeException $e, $request) {
            $mensagem = 'Arquivo grande demais para o servidor aceitar de uma vez (limite atual: '
                .ini_get('post_max_size').'). Fale com o suporte da hospedagem para aumentar '
                .'o post_max_size / client_max_body_size.';

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => $mensagem], 413);
            }

            return back()->withErrors(['files' => $mensagem]);
        });
    })->create();
