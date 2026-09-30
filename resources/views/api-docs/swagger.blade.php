<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API de Postagem Automática · STRASA</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
        .topo { background: #0b0b0c; color: #fafafa; padding: 18px 24px; }
        .topo-inner { max-width: 1460px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
        .marca { display: flex; align-items: center; gap: 12px; }
        .marca img { height: 22px; filter: brightness(0) invert(1); }
        .marca span { font-size: 14px; color: #a3a3a8; border-left: 1px solid #2a2a2e; padding-left: 12px; }
        .links { display: flex; flex-wrap: wrap; gap: 8px; }
        .links a { color: #fafafa; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid #2a2a2e; border-radius: 999px; padding: 7px 14px; }
        .links a:hover { background: #1a1a1d; }
        .aviso { max-width: 1460px; margin: 16px auto 0; padding: 0 24px; box-sizing: border-box; font-size: 13.5px; color: #3b3b41; line-height: 1.55; }
        .aviso code { background: #efeff2; padding: 1px 6px; border-radius: 4px; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <div class="topo">
        <div class="topo-inner">
            <div class="marca">
                <img src="{{ asset('strasalogo.png') }}" alt="STRASA">
                <span>API de Postagem Automática · v1</span>
            </div>
            <div class="links">
                <a href="{{ route('api.guia') }}">Guia em texto (para IA)</a>
                <a href="{{ route('api.openapi') }}">OpenAPI JSON</a>
            </div>
        </div>
    </div>

    <p class="aviso">
        URL base: <code>{{ rtrim(config('app.url'), '/') }}/api/v1</code>. Para testar aqui, clique em
        <strong>Authorize</strong> e cole a chave (<code>str_...</code>) criada pela agência no STRASA.
        A chave fica só neste navegador.
    </p>

    <div id="swagger"></div>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script>
        window.ui = SwaggerUIBundle({
            url: '{{ route('api.openapi') }}',
            dom_id: '#swagger',
            deepLinking: true,
            persistAuthorization: true,
            displayRequestDuration: true,
            tryItOutEnabled: true,
            docExpansion: 'list',
            defaultModelsExpandDepth: 1
        });
    </script>
</body>
</html>
