<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\EspecificacaoApi;
use App\Support\GuiaApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Documentação pública da API de postagem (não contém segredo nenhum).
 */
class DocumentacaoApiController extends Controller
{
    /** /api/docs — Swagger interativo. */
    public function swagger(): View
    {
        return view('api-docs.swagger');
    }

    /** /api/openapi.json — especificação OpenAPI 3.1. */
    public function openapi(): JsonResponse
    {
        return response()->json(
            EspecificacaoApi::openapi($this->raiz()),
            200,
            ['Cache-Control' => 'public, max-age=300'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /** /api/docs.md — guia em Markdown para a equipe/IA do parceiro. */
    public function guia(): Response
    {
        return response(GuiaApi::markdown($this->raiz()), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /** Domínio oficial (APP_URL), para a documentação nunca mostrar localhost ou IP. */
    private function raiz(): string
    {
        return rtrim((string) config('app.url'), '/');
    }
}
