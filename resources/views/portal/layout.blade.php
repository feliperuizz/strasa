@php
    /**
     * Layout do painel do cliente.
     *
     * CSS próprio de propósito: o portal usa a marca do CLIENTE, não a da
     * agência, e assim a personalização não depende do build do Tailwind.
     * A cor de destaque vem de $client->color e alimenta as variáveis abaixo.
     */
    $accent = $client->color ?: '#111827';
    $logo = $client->logo_url;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Aprovação' }} · {{ $client->name }}</title>

    @if($logo)
        <link rel="icon" href="{{ $logo }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: {{ $accent }};
            --bg: #f6f7f9;
            --surface: #ffffff;
            --line: #e6e8ec;
            --line-strong: #d3d7de;
            --text: #14161a;
            --muted: #6b7280;
            --dim: #9aa1ad;
            --ok: #12805c;
            --ok-bg: #e7f6f0;
            --warn: #b4413a;
            --warn-bg: #fdeceb;
            --radius: 14px;
            --ease: cubic-bezier(0.22, 1, 0.36, 1);
        }

        *, *::before, *::after { box-sizing: border-box; }
        * { margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        button, input, textarea { font: inherit; color: inherit; }

        .wrap { width: 100%; max-width: 1080px; margin: 0 auto; padding: 0 20px; }

        /* ---------------- Cabeçalho ---------------- */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 20;
        }
        .topbar-inner {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; height: 70px;
        }
        .ident { display: flex; align-items: center; gap: 13px; min-width: 0; }
        .ident .logo {
            height: 38px; width: auto; max-width: 150px; object-fit: contain;
        }
        .ident .fallback {
            width: 38px; height: 38px; border-radius: 10px;
            background: var(--accent); color: #fff;
            display: grid; place-items: center;
            font-weight: 700; font-size: 15px; flex-shrink: 0;
        }
        .ident .who { min-width: 0; }
        .ident .who b {
            display: block; font-size: 15px; font-weight: 700; letter-spacing: -0.015em;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .ident .who span { font-size: 12px; color: var(--muted); }

        .linkout {
            font-size: 13px; color: var(--muted); font-weight: 500;
            background: none; border: 0; cursor: pointer; padding: 8px 4px;
        }
        .linkout:hover { color: var(--text); }

        /* ---------------- Conteúdo ---------------- */
        main { flex: 1; padding: 34px 0 60px; }

        .page-head { margin-bottom: 26px; }
        .page-head h1 {
            font-size: clamp(24px, 3.4vw, 31px); font-weight: 800;
            letter-spacing: -0.03em; line-height: 1.15; margin-bottom: 6px;
        }
        .page-head p { color: var(--muted); font-size: 15px; }

        .welcome {
            background: var(--surface);
            border: 1px solid var(--line);
            border-left: 3px solid var(--accent);
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 14.5px; color: var(--muted);
            margin-bottom: 24px;
        }

        /* ---------------- Avisos ---------------- */
        .flash {
            display: flex; align-items: center; gap: 10px;
            background: var(--ok-bg); color: var(--ok);
            border: 1px solid rgba(18,128,92,0.2);
            border-radius: 10px; padding: 13px 16px;
            font-size: 14.5px; font-weight: 500; margin-bottom: 22px;
        }
        .errbox {
            background: var(--warn-bg); color: var(--warn);
            border: 1px solid rgba(180,65,58,0.2);
            border-radius: 10px; padding: 12px 15px;
            font-size: 14px; margin-bottom: 18px;
        }

        /* ---------------- Botões ---------------- */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 20px; border-radius: 10px;
            font-size: 14.5px; font-weight: 600; letter-spacing: -0.01em;
            border: 1px solid transparent; cursor: pointer;
            transition: all 0.2s var(--ease);
        }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-accent { background: var(--accent); color: #fff; }
        .btn-accent:hover:not(:disabled) { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-ok { background: var(--ok); color: #fff; }
        .btn-ok:hover:not(:disabled) { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-warn { background: var(--surface); color: var(--warn); border-color: rgba(180,65,58,0.35); }
        .btn-warn:hover:not(:disabled) { background: var(--warn-bg); transform: translateY(-1px); }
        .btn-plain { background: var(--surface); border-color: var(--line-strong); color: var(--text); }
        .btn-plain:hover:not(:disabled) { border-color: var(--dim); }
        .btn-block { width: 100%; }

        /* ---------------- Formulário ---------------- */
        .field { margin-bottom: 16px; }
        .field label {
            display: block; font-size: 13px; font-weight: 600;
            color: var(--muted); margin-bottom: 7px;
        }
        .input, .textarea {
            width: 100%; background: var(--surface);
            border: 1px solid var(--line-strong); border-radius: 10px;
            padding: 12px 14px; font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input:focus, .textarea:focus {
            outline: none; border-color: var(--accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
        }
        .textarea { resize: vertical; min-height: 92px; line-height: 1.55; }

        /* ---------------- Selos ---------------- */
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11.5px; font-weight: 700; letter-spacing: 0.03em;
            text-transform: uppercase;
            padding: 5px 10px; border-radius: 100px;
        }
        .badge-pending { background: #fff5e5; color: #a15c00; }
        .badge-approved { background: var(--ok-bg); color: var(--ok); }
        .badge-rejected { background: var(--warn-bg); color: var(--warn); }

        /* ---------------- Rodapé ---------------- */
        footer {
            border-top: 1px solid var(--line);
            padding: 22px 0; background: var(--surface);
        }
        .foot {
            display: flex; align-items: center; justify-content: space-between;
            gap: 14px; flex-wrap: wrap;
            font-size: 12.5px; color: var(--dim);
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition-duration: 0.001ms !important;
                animation-duration: 0.001ms !important;
            }
        }

        @yield('styles')
    </style>
</head>
<body>

    @unless($hideChrome ?? false)
        <div class="topbar">
            <div class="wrap topbar-inner">
                <a class="ident" href="{{ route('portal.index', $portal->token) }}">
                    @if($logo)
                        <img class="logo" src="{{ $logo }}" alt="{{ $client->name }}">
                    @else
                        <span class="fallback">{{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}</span>
                    @endif
                    <span class="who">
                        <b>{{ $client->name }}</b>
                        <span>Painel de aprovação</span>
                    </span>
                </a>

                <form method="POST" action="{{ route('portal.logout', $portal->token) }}">
                    @csrf
                    <button type="submit" class="linkout">Sair</button>
                </form>
            </div>
        </div>
    @endunless

    <main>
        <div class="wrap">
            @if(session('portal_status'))
                <div class="flash">✓ {{ session('portal_status') }}</div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer>
        <div class="wrap foot">
            <span>{{ $client->name }} · Aprovação de conteúdo</span>
            <span>Painel fornecido por STRASA</span>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
