<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ShareTenantData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
