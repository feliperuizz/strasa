<?php

use App\Http\Controllers\Api\DocumentacaoApiController;
use App\Http\Controllers\Api\V1\PostagemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de postagem automática
|--------------------------------------------------------------------------
| Prefixo /api (bootstrap/app.php). Sem sessão e sem CSRF: quem chama é o
| sistema parceiro, com "Authorization: Bearer <chave>" — as chaves são
| criadas pelo administrador em /integracoes.
|
| Documentação pública (não tem segredo nenhum):
|   /api/docs          Swagger (interativo)
|   /api/openapi.json  especificação OpenAPI 3.1
|   /api/docs.md       guia em texto, feito para a IA/equipe do parceiro
*/

Route::get('docs', [DocumentacaoApiController::class, 'swagger'])->name('api.docs');
Route::get('openapi.json', [DocumentacaoApiController::class, 'openapi'])->name('api.openapi');
Route::get('docs.md', [DocumentacaoApiController::class, 'guia'])->name('api.guia');

Route::prefix('v1')->name('api.')->group(function () {
    // Mídia por link assinado (a URL vem pronta dentro de cada post; sem Bearer).
    Route::get('media/{attachment}', [PostagemController::class, 'media'])
        ->middleware(['signed', 'throttle:api-postagem'])
        ->whereNumber('attachment')
        ->name('media');

    Route::middleware(['api.chave', 'throttle:api-postagem'])->group(function () {
        Route::get('me', [PostagemController::class, 'me'])->name('me');
        Route::get('clients', [PostagemController::class, 'clients'])->name('clients');
        Route::get('posts', [PostagemController::class, 'posts'])->name('posts');
        Route::get('posts/{post}', [PostagemController::class, 'post'])->whereNumber('post')->name('posts.show');
        Route::post('posts/{post}/publications', [PostagemController::class, 'reportarPublicacao'])->whereNumber('post')->name('posts.publications');
    });
});
