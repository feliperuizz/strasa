<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="STRASA — plataforma interna de gestão de projetos, documentos e fluxo de trabalho. Kanban, anexos, financeiro e equipe em um só lugar.">
    <meta name="theme-color" content="#050505">
    <title>STRASA — Gestão inteligente de projetos</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v={{ config('app.icon_version') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ config('app.icon_version') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @verbatim
    <style>
        /* =====================================================================
           STRASA — landing page
           CSS proprio, sem Tailwind e sem etapa de build: a pagina e um arquivo
           so. Se voce mexer aqui, nao precisa rodar npm run build.
           ===================================================================== */

        *, *::before, *::after { box-sizing: border-box; }
        * { margin: 0; padding: 0; }

        :root {
            --bg:        #050505;
            --bg-soft:   #0a0a0b;
            --surface:   #111113;
            --surface-2: #17171a;
            --line:      rgba(255, 255, 255, 0.09);
            --line-str:  rgba(255, 255, 255, 0.16);
            --text:      #fafafa;
            --muted:     #a3a3a8;
            --dim:       #6d6d74;

            --radius:    18px;
            --maxw:      1180px;
            --ease:      cubic-bezier(0.22, 1, 0.36, 1);
        }

        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
            overflow-x: hidden;
        }

        ::selection { background: #fff; color: #000; }

        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }

        /* Grao sutil por cima de tudo, tira o aspecto "chapado" do preto. */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.035;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        .wrap { width: 100%; max-width: var(--maxw); margin: 0 auto; padding: 0 24px; }

        /* ------------------------------------------------------------------ */
        /* Navegacao                                                           */
        /* ------------------------------------------------------------------ */
        .nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            transition: all 0.4s var(--ease);
            border-bottom: 1px solid transparent;
        }
        .nav.is-stuck {
            background: rgba(5, 5, 5, 0.72);
            backdrop-filter: blur(16px) saturate(160%);
            -webkit-backdrop-filter: blur(16px) saturate(160%);
            border-bottom-color: var(--line);
        }
        .nav-inner {
            display: flex; align-items: center; justify-content: space-between;
            height: 74px;
            transition: height 0.4s var(--ease);
        }
        .nav.is-stuck .nav-inner { height: 62px; }

        .brand { display: flex; align-items: center; gap: 11px; }
        .brand img { height: 26px; filter: brightness(0) invert(1); opacity: 0.95; }

        .nav-links { display: flex; align-items: center; gap: 34px; }
        .nav-links a {
            font-size: 14px; color: var(--muted); font-weight: 500;
            transition: color 0.25s;
        }
        .nav-links a:hover { color: var(--text); }
        @media (max-width: 860px) { .nav-links { display: none; } }

        /* ------------------------------------------------------------------ */
        /* Botoes                                                              */
        /* ------------------------------------------------------------------ */
        .btn {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 13px 24px;
            border-radius: 100px;
            font-size: 14.5px; font-weight: 600; letter-spacing: -0.01em;
            border: 1px solid transparent;
            cursor: pointer;
            position: relative;
            transition: transform 0.3s var(--ease), background 0.3s, color 0.3s, border-color 0.3s, box-shadow 0.3s;
            white-space: nowrap;
        }
        .btn-primary {
            background: #fff; color: #000;
            box-shadow: 0 0 0 rgba(255, 255, 255, 0);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px -8px rgba(255, 255, 255, 0.45);
        }
        .btn-ghost { border-color: var(--line-str); color: var(--text); background: rgba(255,255,255,0.02); }
        .btn-ghost:hover { border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.06); transform: translateY(-2px); }
        .btn-sm { padding: 9px 18px; font-size: 13.5px; }
        .btn .arrow { transition: transform 0.3s var(--ease); }
        .btn:hover .arrow { transform: translateX(4px); }

        /* ------------------------------------------------------------------ */
        /* Hero                                                                */
        /* ------------------------------------------------------------------ */
        .hero {
            position: relative;
            padding: 172px 0 92px;
            overflow: hidden;
        }
        /* Halo que segue o mouse. */
        .hero-glow {
            position: absolute;
            width: 900px; height: 900px;
            left: 50%; top: -300px;
            transform: translate(-50%, 0);
            background: radial-gradient(circle, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.03) 32%, transparent 62%);
            pointer-events: none;
            transition: transform 0.6s var(--ease);
        }
        /* Grade de fundo em perspectiva. */
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
            background-size: 62px 62px;
            mask-image: radial-gradient(ellipse 90% 60% at 50% 0%, #000 20%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 90% 60% at 50% 0%, #000 20%, transparent 75%);
            pointer-events: none;
        }

        .hero-inner { position: relative; text-align: center; }

        .eyebrow {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 7px 15px 7px 10px;
            border: 1px solid var(--line);
            border-radius: 100px;
            font-size: 12.5px; font-weight: 500; color: var(--muted);
            background: rgba(255,255,255,0.03);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }
        .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #fff;
            box-shadow: 0 0 0 0 rgba(255,255,255,0.7);
            animation: pulse 2.6s infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(255,255,255,0.55); }
            70%  { box-shadow: 0 0 0 9px rgba(255,255,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
        }

        h1.display {
            font-size: clamp(44px, 8.2vw, 96px);
            line-height: 0.98;
            font-weight: 800;
            letter-spacing: -0.045em;
            margin-bottom: 26px;
        }
        h1.display .shine {
            background: linear-gradient(100deg, #fff 20%, #6e6e74 45%, #fff 70%);
            background-size: 260% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: shine 7s linear infinite;
        }
        @keyframes shine { to { background-position: -260% center; } }

        .lead {
            font-size: clamp(16px, 2.1vw, 19.5px);
            color: var(--muted);
            max-width: 620px;
            margin: 0 auto 40px;
            line-height: 1.65;
        }
        .hero-cta { display: flex; gap: 13px; justify-content: center; flex-wrap: wrap; }

        /* ------------------------------------------------------------------ */
        /* Mockup 3D do quadro                                                 */
        /* ------------------------------------------------------------------ */
        .stage { perspective: 1800px; margin-top: 82px; }
        .board-3d {
            transform: rotateX(26deg) scale(0.94);
            transform-origin: 50% 0%;
            transition: transform 1.4s var(--ease);
            border-radius: 20px;
            border: 1px solid var(--line-str);
            background: linear-gradient(180deg, var(--surface) 0%, var(--bg-soft) 100%);
            padding: 16px;
            box-shadow:
                0 60px 120px -40px rgba(0,0,0,0.95),
                0 0 90px -30px rgba(255,255,255,0.10);
            position: relative;
        }
        .board-3d.is-flat { transform: rotateX(6deg) scale(1); }
        .board-3d::after {
            content: '';
            position: absolute; inset: 0;
            border-radius: 20px;
            background: linear-gradient(180deg, transparent 55%, var(--bg) 98%);
            pointer-events: none;
        }
        .board-bar {
            display: flex; align-items: center; gap: 7px;
            padding: 4px 6px 15px;
        }
        .board-bar i { width: 10px; height: 10px; border-radius: 50%; background: rgba(255,255,255,0.16); }
        .board-bar b {
            margin-left: 12px; font-size: 12px; font-weight: 500;
            color: var(--dim); letter-spacing: 0.01em;
        }
        .cols { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        @media (max-width: 760px) { .cols { grid-template-columns: repeat(2, 1fr); } }
        .col {
            background: rgba(255,255,255,0.022);
            border: 1px solid var(--line);
            border-radius: 13px;
            padding: 12px;
            min-height: 232px;
        }
        .col h4 {
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em;
            color: var(--dim); font-weight: 600; margin-bottom: 12px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .col h4 em { font-style: normal; color: rgba(255,255,255,0.28); }
        .kcard {
            background: var(--surface-2);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 11px 12px;
            margin-bottom: 9px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.45);
        }
        .kcard .tag {
            display: inline-block; font-size: 9.5px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
            padding: 3px 7px; border-radius: 5px;
            background: rgba(255,255,255,0.09); color: var(--muted);
            margin-bottom: 8px;
        }
        .kcard p { font-size: 12.5px; color: #e2e2e5; line-height: 1.45; }
        .kcard .meta {
            display: flex; align-items: center; gap: 6px; margin-top: 10px;
            font-size: 10.5px; color: var(--dim);
        }
        .kcard .av {
            width: 17px; height: 17px; border-radius: 50%;
            background: linear-gradient(135deg, #45454b, #202024);
            border: 1px solid rgba(255,255,255,0.14);
        }
        /* Card "sendo arrastado" — reforca que o quadro e drag & drop. */
        .kcard.dragging {
            transform: rotate(-2.6deg) translateY(-3px);
            border-color: rgba(255,255,255,0.42);
            box-shadow: 0 16px 34px rgba(0,0,0,0.75);
            animation: float 3.4s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: rotate(-2.6deg) translateY(-3px); }
            50%      { transform: rotate(-1.4deg) translateY(-10px); }
        }
        .ghost {
            border: 1px dashed rgba(255,255,255,0.22);
            border-radius: 10px;
            height: 58px;
            background: rgba(255,255,255,0.015);
        }

        /* ------------------------------------------------------------------ */
        /* Faixa de capacidades                                                */
        /* ------------------------------------------------------------------ */
        .strip {
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 26px 0;
            overflow: hidden;
            background: var(--bg-soft);
        }
        .marquee { display: flex; gap: 56px; width: max-content; animation: slide 34s linear infinite; }
        .marquee span {
            font-size: 13px; font-weight: 600; letter-spacing: 0.13em;
            text-transform: uppercase; color: var(--dim);
            display: flex; align-items: center; gap: 56px;
            white-space: nowrap;
        }
        .marquee span::after { content: ''; width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,0.2); }
        @keyframes slide { to { transform: translateX(-50%); } }
        .strip:hover .marquee { animation-play-state: paused; }

        /* ------------------------------------------------------------------ */
        /* Secoes                                                              */
        /* ------------------------------------------------------------------ */
        section { position: relative; }
        .sec { padding: 118px 0; }
        .sec-head { max-width: 700px; margin-bottom: 62px; }
        .sec-head.center { margin-left: auto; margin-right: auto; text-align: center; }
        .kicker {
            font-size: 12px; font-weight: 700; letter-spacing: 0.16em;
            text-transform: uppercase; color: var(--dim); margin-bottom: 16px;
        }
        h2 {
            font-size: clamp(31px, 4.6vw, 52px);
            line-height: 1.06;
            letter-spacing: -0.038em;
            font-weight: 800;
            margin-bottom: 18px;
        }
        .sec-head p { color: var(--muted); font-size: 17px; line-height: 1.65; }

        /* ------------------------------------------------------------------ */
        /* Cards 3D (bento)                                                    */
        /* ------------------------------------------------------------------ */
        .bento {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        @media (max-width: 940px) { .bento { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 620px) { .bento { grid-template-columns: 1fr; } }

        .card {
            position: relative;
            background: linear-gradient(160deg, var(--surface) 0%, var(--bg-soft) 100%);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 30px 28px 32px;
            overflow: hidden;
            transform-style: preserve-3d;
            transition: border-color 0.4s, box-shadow 0.4s;
            will-change: transform;
        }
        .card:hover { border-color: var(--line-str); box-shadow: 0 26px 60px -26px rgba(0,0,0,0.9); }
        .card.wide { grid-column: span 2; }
        @media (max-width: 620px) { .card.wide { grid-column: span 1; } }

        /* Brilho que acompanha o cursor dentro do card. */
        .card .glare {
            position: absolute; inset: 0;
            background: radial-gradient(420px circle at var(--mx, 50%) var(--my, 50%), rgba(255,255,255,0.09), transparent 42%);
            opacity: 0;
            transition: opacity 0.4s;
            pointer-events: none;
        }
        .card:hover .glare { opacity: 1; }

        .card .inner { position: relative; transform: translateZ(38px); }
        .ico {
            width: 46px; height: 46px;
            border-radius: 13px;
            border: 1px solid var(--line-str);
            background: rgba(255,255,255,0.05);
            display: grid; place-items: center;
            margin-bottom: 20px;
            transition: transform 0.45s var(--ease), background 0.4s;
        }
        .card:hover .ico { transform: translateZ(24px) scale(1.06); background: rgba(255,255,255,0.11); }
        .ico svg { width: 21px; height: 21px; stroke: #fff; fill: none; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; }
        .card h3 { font-size: 18.5px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 9px; }
        .card p { font-size: 14.5px; color: var(--muted); line-height: 1.62; }

        /* ------------------------------------------------------------------ */
        /* Blocos alternados                                                   */
        /* ------------------------------------------------------------------ */
        .split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 72px;
            align-items: center;
        }
        @media (max-width: 900px) { .split { grid-template-columns: 1fr; gap: 44px; } }
        .split + .split { margin-top: 128px; }
        .split.rev .visual { order: -1; }
        @media (max-width: 900px) { .split.rev .visual { order: 0; } }

        .split h3 {
            font-size: clamp(26px, 3.4vw, 38px);
            letter-spacing: -0.035em; font-weight: 800; line-height: 1.1;
            margin-bottom: 16px;
        }
        .split p.body { color: var(--muted); font-size: 16.5px; line-height: 1.68; margin-bottom: 26px; }
        .checks { list-style: none; display: grid; gap: 13px; }
        .checks li { display: flex; gap: 12px; align-items: flex-start; font-size: 15px; color: #d6d6da; }
        .checks svg {
            width: 19px; height: 19px; flex-shrink: 0; margin-top: 2px;
            stroke: #fff; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
            opacity: 0.85;
        }

        .visual {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: linear-gradient(160deg, var(--surface) 0%, var(--bg-soft) 100%);
            padding: 22px;
            box-shadow: 0 40px 90px -40px rgba(0,0,0,0.95);
            transform-style: preserve-3d;
            will-change: transform;
        }

        /* Lista de arquivos */
        .file {
            display: flex; align-items: center; gap: 13px;
            padding: 13px 14px;
            border: 1px solid var(--line);
            border-radius: 11px;
            background: rgba(255,255,255,0.022);
            margin-bottom: 10px;
        }
        .file .thumb {
            width: 38px; height: 38px; border-radius: 9px;
            background: rgba(255,255,255,0.07);
            display: grid; place-items: center; flex-shrink: 0;
            border: 1px solid var(--line);
            font-size: 9.5px; font-weight: 700; letter-spacing: 0.05em; color: var(--muted);
        }
        .file .info { flex: 1; min-width: 0; }
        .file .info b { display: block; font-size: 13.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file .info small { font-size: 11.5px; color: var(--dim); }
        .bar { height: 4px; border-radius: 4px; background: rgba(255,255,255,0.09); margin-top: 8px; overflow: hidden; }
        .bar i { display: block; height: 100%; background: #fff; border-radius: 4px; animation: fill 3.6s var(--ease) infinite; }
        @keyframes fill { 0% { width: 8%; } 60% { width: 92%; } 100% { width: 8%; } }

        /* Tabela financeira */
        .row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 4px;
            border-bottom: 1px solid var(--line);
            font-size: 14px;
        }
        .row:last-child { border-bottom: 0; }
        .row .who { display: flex; align-items: center; gap: 11px; }
        .row .av2 {
            width: 30px; height: 30px; border-radius: 8px;
            background: linear-gradient(135deg, #3a3a40, #1c1c20);
            border: 1px solid var(--line);
            display: grid; place-items: center;
            font-size: 11px; font-weight: 700; color: var(--muted);
            flex-shrink: 0;
        }
        .row .who b { font-size: 13.5px; font-weight: 600; }
        .row .who small { font-size: 11.5px; color: var(--dim); }
        .pill {
            font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
            padding: 4px 10px; border-radius: 100px; border: 1px solid var(--line-str);
            color: var(--muted); white-space: nowrap;
        }
        .pill.ok { background: #fff; color: #000; border-color: #fff; }

        /* ------------------------------------------------------------------ */
        /* Passos                                                              */
        /* ------------------------------------------------------------------ */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        @media (max-width: 820px) { .steps { grid-template-columns: 1fr; } }
        .step {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 32px 28px;
            background: linear-gradient(160deg, var(--surface) 0%, var(--bg-soft) 100%);
            position: relative;
            overflow: hidden;
            transition: border-color 0.4s, transform 0.5s var(--ease);
        }
        .step:hover { border-color: var(--line-str); transform: translateY(-6px); }
        .step .n {
            font-size: 68px; font-weight: 900; letter-spacing: -0.06em;
            line-height: 1; color: transparent;
            -webkit-text-stroke: 1.4px rgba(255,255,255,0.22);
            margin-bottom: 18px;
        }
        .step h4 { font-size: 17.5px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 9px; }
        .step p { font-size: 14.5px; color: var(--muted); line-height: 1.62; }

        /* ------------------------------------------------------------------ */
        /* CTA final                                                           */
        /* ------------------------------------------------------------------ */
        .cta {
            border: 1px solid var(--line-str);
            border-radius: 26px;
            padding: 78px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 130% at 50% 0%, rgba(255,255,255,0.09), transparent 62%),
                linear-gradient(180deg, var(--surface) 0%, var(--bg) 100%);
        }
        .cta h2 { margin-bottom: 16px; }
        .cta p { color: var(--muted); max-width: 500px; margin: 0 auto 34px; font-size: 17px; }

        /* ------------------------------------------------------------------ */
        /* Rodape                                                              */
        /* ------------------------------------------------------------------ */
        footer { border-top: 1px solid var(--line); padding: 52px 0 60px; margin-top: 118px; }
        .foot {
            display: flex; align-items: center; justify-content: space-between;
            gap: 26px; flex-wrap: wrap;
        }
        .foot-links { display: flex; gap: 28px; flex-wrap: wrap; }
        .foot-links a { font-size: 13.5px; color: var(--dim); transition: color 0.25s; }
        .foot-links a:hover { color: var(--text); }
        .foot small { font-size: 13px; color: var(--dim); }

        /* ------------------------------------------------------------------ */
        /* Animacao de entrada no scroll                                       */
        /* ------------------------------------------------------------------ */
        [data-reveal] {
            opacity: 0;
            transform: translateY(26px);
            transition: opacity 0.85s var(--ease), transform 0.85s var(--ease);
            transition-delay: var(--d, 0ms);
        }
        [data-reveal].shown { opacity: 1; transform: none; }

        /* Respeita quem pediu menos movimento no sistema. */
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001ms !important;
            }
            [data-reveal] { opacity: 1; transform: none; }
            .board-3d { transform: rotateX(6deg) scale(1); }
        }
    </style>
    @endverbatim
</head>
<body>

    <!-- ================================================================== -->
    <!-- Navegacao                                                           -->
    <!-- ================================================================== -->
    <nav class="nav" id="nav">
        <div class="wrap nav-inner">
            <a href="#topo" class="brand">
                <img src="{{ asset('strasalogo.png') }}" alt="STRASA">
            </a>
            <div class="nav-links">
                <a href="#recursos">Recursos</a>
                <a href="#modulos">Módulos</a>
                <a href="#fluxo">Como funciona</a>
                <a href="mailto:contato@consultoriastr.com.br">Suporte</a>
            </div>
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                Acessar <span class="arrow">&rarr;</span>
            </a>
        </div>
    </nav>

    <!-- ================================================================== -->
    <!-- Hero                                                                -->
    <!-- ================================================================== -->
    <header class="hero" id="topo">
        <div class="hero-grid"></div>
        <div class="hero-glow" id="glow"></div>

        <div class="wrap hero-inner">
            <div class="eyebrow" data-reveal>
                <span class="dot"></span>
                Plataforma interna &middot; Equipe STRASA
            </div>

            <h1 class="display" data-reveal style="--d:80ms">
                Gestão inteligente<br><span class="shine">de projetos</span>
            </h1>

            <p class="lead" data-reveal style="--d:160ms">
                Do briefing à entrega, num lugar só. Quadros que a equipe realmente
                usa, arquivos que ninguém perde e o financeiro do projeto sempre à vista.
            </p>

            <div class="hero-cta" data-reveal style="--d:240ms">
                <a href="{{ route('login') }}" class="btn btn-primary">
                    Acessar o sistema <span class="arrow">&rarr;</span>
                </a>
                <a href="#recursos" class="btn btn-ghost">Ver o que tem dentro</a>
            </div>

            <!-- Mockup do quadro: inclinado em 3D, endireita conforme a pagina rola -->
            <div class="stage" data-reveal style="--d:320ms">
                <div class="board-3d" id="board">
                    <div class="board-bar">
                        <i></i><i></i><i></i>
                        <b>Campanha de lançamento &middot; Quadro</b>
                    </div>
                    <div class="cols">
                        <div class="col">
                            <h4>A fazer <em>3</em></h4>
                            <div class="kcard">
                                <span class="tag">Briefing</span>
                                <p>Levantar referências com o cliente</p>
                                <div class="meta"><span class="av"></span> 12 nov</div>
                            </div>
                            <div class="kcard">
                                <span class="tag">Copy</span>
                                <p>Roteiro dos 3 vídeos curtos</p>
                                <div class="meta"><span class="av"></span> 14 nov</div>
                            </div>
                            <div class="ghost"></div>
                        </div>
                        <div class="col">
                            <h4>Em andamento <em>2</em></h4>
                            <div class="kcard dragging">
                                <span class="tag">Design</span>
                                <p>Key visual — versão 2</p>
                                <div class="meta"><span class="av"></span> 4 anexos</div>
                            </div>
                            <div class="kcard">
                                <span class="tag">Tráfego</span>
                                <p>Estrutura de campanha</p>
                                <div class="meta"><span class="av"></span> 15 nov</div>
                            </div>
                        </div>
                        <div class="col">
                            <h4>Revisão <em>2</em></h4>
                            <div class="kcard">
                                <span class="tag">Vídeo</span>
                                <p>Corte final aprovado internamente</p>
                                <div class="meta"><span class="av"></span> 2 comentários</div>
                            </div>
                            <div class="kcard">
                                <span class="tag">Social</span>
                                <p>Calendário de posts</p>
                                <div class="meta"><span class="av"></span> 16 nov</div>
                            </div>
                        </div>
                        <div class="col">
                            <h4>Entregue <em>4</em></h4>
                            <div class="kcard">
                                <span class="tag">Site</span>
                                <p>Landing page publicada</p>
                                <div class="meta"><span class="av"></span> concluído</div>
                            </div>
                            <div class="kcard">
                                <span class="tag">Relatório</span>
                                <p>Fechamento de outubro</p>
                                <div class="meta"><span class="av"></span> concluído</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ================================================================== -->
    <!-- Faixa de capacidades                                                -->
    <!-- ================================================================== -->
    <div class="strip">
        <div class="marquee">
            <span>Kanban arrastar e soltar</span><span>Anexos sem limite</span><span>Google Drive</span>
            <span>Financeiro do projeto</span><span>Calendário por cliente</span><span>Comentários no card</span>
            <span>Checklists</span><span>Push no celular</span><span>Briefing diário</span>
            <span>Kanban arrastar e soltar</span><span>Anexos sem limite</span><span>Google Drive</span>
            <span>Financeiro do projeto</span><span>Calendário por cliente</span><span>Comentários no card</span>
            <span>Checklists</span><span>Push no celular</span><span>Briefing diário</span>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- Recursos (bento 3D)                                                 -->
    <!-- ================================================================== -->
    <section class="sec" id="recursos">
        <div class="wrap">
            <div class="sec-head center" data-reveal>
                <div class="kicker">Recursos</div>
                <h2>Tudo que o projeto precisa,<br>sem trocar de aba</h2>
                <p>Cada função nasceu de um problema real do dia a dia da equipe — não de uma lista de features.</p>
            </div>

            <div class="bento">
                <div class="card wide" data-tilt data-reveal>
                    <div class="glare"></div>
                    <div class="inner">
                        <div class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="10" y="4" width="5" height="10" rx="1.5"/><rect x="17" y="4" width="4" height="14" rx="1.5"/></svg></div>
                        <h3>Quadro Kanban de verdade</h3>
                        <p>Colunas que você cria e reordena, cartões que arrastam com o mouse ou o dedo e mudam de fase na hora. A posição salva sozinha — ninguém precisa clicar em "salvar".</p>
                    </div>
                </div>

                <div class="card" data-tilt data-reveal style="--d:70ms">
                    <div class="glare"></div>
                    <div class="inner">
                        <div class="ico"><svg viewBox="0 0 24 24"><path d="M21.4 11.05 12.25 20.2a5.5 5.5 0 0 1-7.78-7.78l9.19-9.19a3.67 3.67 0 0 1 5.19 5.19l-9.2 9.19a1.83 1.83 0 0 1-2.59-2.59l8.49-8.48"/></svg></div>
                        <h3>Anexos sem limite</h3>
                        <p>Qualquer tipo e qualquer tamanho, com barra de progresso enquanto sobe. Vídeo você assiste no próprio player, sem baixar.</p>
                    </div>
                </div>

                <div class="card" data-tilt data-reveal>
                    <div class="glare"></div>
                    <div class="inner">
                        <div class="ico"><svg viewBox="0 0 24 24"><path d="M12 2 4 15h5l3-5 3 5h5L12 2Z"/><path d="M4 15l-2 4h20l-2-4"/></svg></div>
                        <h3>Integração com Google Drive</h3>
                        <p>Os arquivos ficam guardados no Drive da agência e acessíveis direto da tarefa, com o mesmo controle de acesso de sempre.</p>
                    </div>
                </div>

                <div class="card" data-tilt data-reveal style="--d:70ms">
                    <div class="glare"></div>
                    <div class="inner">
                        <div class="ico"><svg viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                        <h3>Financeiro por projeto</h3>
                        <p>Cobranças, previsão de faturamento e baixa de pagamento com um clique. Dá para saber quanto o cliente já rendeu sem abrir planilha.</p>
                    </div>
                </div>

                <div class="card" data-tilt data-reveal style="--d:140ms">
                    <div class="glare"></div>
                    <div class="inner">
                        <div class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></svg></div>
                        <h3>Calendário de prazos</h3>
                        <p>A agenda do cliente e a do projeto na mesma tela — o que vence essa semana aparece antes de virar urgência.</p>
                    </div>
                </div>

                <div class="card wide" data-tilt data-reveal style="--d:70ms">
                    <div class="glare"></div>
                    <div class="inner">
                        <div class="ico"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20v-1.4a5.2 5.2 0 0 1 5.2-5.2h2.6a5.2 5.2 0 0 1 5.2 5.2V20"/><path d="M17 4.2a3.2 3.2 0 0 1 0 6.2"/><path d="M18.5 13.6a5.2 5.2 0 0 1 3 4.7V20"/></svg></div>
                        <h3>Equipe, convites e permissões</h3>
                        <p>Convide alguém por link, defina o que a pessoa enxerga e acompanhe a carga de cada um. Quem entra hoje já encontra tudo organizado.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================== -->
    <!-- Blocos alternados                                                   -->
    <!-- ================================================================== -->
    <section class="sec" id="modulos" style="padding-top:0">
        <div class="wrap">

            <div class="split" data-reveal>
                <div>
                    <div class="kicker">Arquivos</div>
                    <h3>O anexo certo, na tarefa certa</h3>
                    <p class="body">
                        Acabou o "me manda de novo aquele arquivo no WhatsApp". Tudo que
                        pertence à tarefa vive dentro dela — e continua lá seis meses depois.
                    </p>
                    <ul class="checks">
                        <li><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Qualquer formato, qualquer tamanho</li>
                        <li><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Progresso do upload na própria tela</li>
                        <li><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Vídeo roda no player, sem download</li>
                        <li><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Organização por pastas dentro da tarefa</li>
                    </ul>
                </div>
                <div class="visual" data-tilt>
                    <div class="file">
                        <div class="thumb">MP4</div>
                        <div class="info">
                            <b>keyvisual-final-v2.mp4</b>
                            <small>Enviando &middot; 248 MB</small>
                            <div class="bar"><i></i></div>
                        </div>
                    </div>
                    <div class="file">
                        <div class="thumb">PDF</div>
                        <div class="info"><b>briefing-cliente.pdf</b><small>2,4 MB &middot; há 2 dias</small></div>
                    </div>
                    <div class="file">
                        <div class="thumb">PSD</div>
                        <div class="info"><b>arte-editavel.psd</b><small>134 MB &middot; Google Drive</small></div>
                    </div>
                    <div class="file" style="margin-bottom:0">
                        <div class="thumb">XLS</div>
                        <div class="info"><b>midia-plano.xlsx</b><small>820 KB &middot; há 5 dias</small></div>
                    </div>
                </div>
            </div>

            <div class="split rev" data-reveal>
                <div>
                    <div class="kicker">Financeiro</div>
                    <h3>O dinheiro do projeto, sem planilha paralela</h3>
                    <p class="body">
                        Cobrança lançada é cobrança visível. A equipe entrega, a gestão
                        acompanha o faturamento previsto e o que já entrou — na mesma base.
                    </p>
                    <ul class="checks">
                        <li><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Cobranças por cliente e por projeto</li>
                        <li><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Baixa de pagamento em um clique</li>
                        <li><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg> Previsão de faturamento do mês</li>
                    </ul>
                </div>
                <div class="visual" data-tilt>
                    <div class="row">
                        <div class="who"><span class="av2">AL</span><div><b>Alpha Ltda</b><br><small>Retainer &middot; novembro</small></div></div>
                        <span class="pill ok">Pago</span>
                    </div>
                    <div class="row">
                        <div class="who"><span class="av2">BR</span><div><b>Bravo Comércio</b><br><small>Campanha de lançamento</small></div></div>
                        <span class="pill">Em aberto</span>
                    </div>
                    <div class="row">
                        <div class="who"><span class="av2">CE</span><div><b>Cedro Studio</b><br><small>Produção de vídeo</small></div></div>
                        <span class="pill ok">Pago</span>
                    </div>
                    <div class="row">
                        <div class="who"><span class="av2">DL</span><div><b>Delta Saúde</b><br><small>Social media</small></div></div>
                        <span class="pill">Previsto</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ================================================================== -->
    <!-- Como funciona                                                       -->
    <!-- ================================================================== -->
    <section class="sec" id="fluxo">
        <div class="wrap">
            <div class="sec-head center" data-reveal>
                <div class="kicker">Como funciona</div>
                <h2>Três passos e o projeto anda</h2>
            </div>
            <div class="steps">
                <div class="step" data-reveal>
                    <div class="n">01</div>
                    <h4>Cadastre o cliente</h4>
                    <p>Cada cliente tem seus projetos, sua agenda e seu histórico financeiro separados.</p>
                </div>
                <div class="step" data-reveal style="--d:90ms">
                    <div class="n">02</div>
                    <h4>Monte o quadro</h4>
                    <p>Crie as colunas do fluxo da equipe, abra as tarefas e distribua entre as pessoas.</p>
                </div>
                <div class="step" data-reveal style="--d:180ms">
                    <div class="n">03</div>
                    <h4>Trabalhe dentro dele</h4>
                    <p>Anexos, comentários, checklists e prazos ficam no cartão. O briefing diário chega por e-mail.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================== -->
    <!-- CTA final                                                           -->
    <!-- ================================================================== -->
    <section class="sec" style="padding-top:0">
        <div class="wrap">
            <div class="cta" data-reveal>
                <h2>Entre e comece por onde parou</h2>
                <p>Acesso exclusivo para a equipe. Instale no celular e receba as notificações dos seus projetos.</p>
                <div class="hero-cta">
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        Acessar o sistema <span class="arrow">&rarr;</span>
                    </a>
                    <a href="mailto:contato@consultoriastr.com.br" class="btn btn-ghost">Falar com o suporte</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================== -->
    <!-- Rodape                                                              -->
    <!-- ================================================================== -->
    <footer>
        <div class="wrap foot">
            <div class="brand">
                <img src="{{ asset('strasalogo.png') }}" alt="STRASA">
            </div>
            <div class="foot-links">
                <a href="{{ route('privacy') }}">Política de Privacidade</a>
                <a href="{{ route('terms') }}">Termos de Serviço</a>
                <a href="mailto:contato@consultoriastr.com.br">Suporte</a>
            </div>
            <small>&copy; {{ date('Y') }} STRASA &middot; Uso interno</small>
        </div>
    </footer>

    @verbatim
    <script>
    (function () {
        'use strict';

        var reduz = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var fino  = window.matchMedia('(pointer: fine)').matches;

        /* --- Nav encolhe ao rolar ------------------------------------- */
        var nav = document.getElementById('nav');
        function aoRolar() { nav.classList.toggle('is-stuck', window.scrollY > 24); }
        aoRolar();
        window.addEventListener('scroll', aoRolar, { passive: true });

        /* --- Entrada dos blocos no scroll ----------------------------- */
        var alvos = document.querySelectorAll('[data-reveal]');
        if ('IntersectionObserver' in window && !reduz) {
            var obs = new IntersectionObserver(function (entradas) {
                entradas.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('shown');
                        obs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
            alvos.forEach(function (el) { obs.observe(el); });
        } else {
            alvos.forEach(function (el) { el.classList.add('shown'); });
        }

        if (reduz) return;

        /* --- Quadro endireita conforme a pagina rola ------------------- */
        var board = document.getElementById('board');
        if (board && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (entradas) {
                entradas.forEach(function (e) {
                    board.classList.toggle('is-flat', e.intersectionRatio > 0.32);
                });
            }, { threshold: [0, 0.32, 0.6] }).observe(board);
        }

        /* --- Halo do hero segue o mouse ------------------------------- */
        var glow = document.getElementById('glow');
        if (glow && fino) {
            window.addEventListener('mousemove', function (ev) {
                if (window.scrollY > 700) return;
                var dx = (ev.clientX / window.innerWidth - 0.5) * 90;
                var dy = (ev.clientY / window.innerHeight) * 60;
                glow.style.transform = 'translate(calc(-50% + ' + dx + 'px), ' + dy + 'px)';
            }, { passive: true });
        }

        /* --- Inclinacao 3D dos cards conforme o cursor ---------------- */
        if (fino) {
            document.querySelectorAll('[data-tilt]').forEach(function (card) {
                var raf = null;

                card.addEventListener('mousemove', function (ev) {
                    if (raf) return;
                    raf = requestAnimationFrame(function () {
                        raf = null;
                        var r  = card.getBoundingClientRect();
                        var px = (ev.clientX - r.left) / r.width;
                        var py = (ev.clientY - r.top) / r.height;
                        var rx = (0.5 - py) * 9;
                        var ry = (px - 0.5) * 11;

                        card.style.transform =
                            'perspective(1000px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' +
                            ry.toFixed(2) + 'deg) translateY(-4px)';

                        card.style.setProperty('--mx', (px * 100).toFixed(1) + '%');
                        card.style.setProperty('--my', (py * 100).toFixed(1) + '%');
                    });
                });

                card.addEventListener('mouseleave', function () {
                    card.style.transition = 'transform 0.6s cubic-bezier(0.22, 1, 0.36, 1)';
                    card.style.transform  = '';
                    setTimeout(function () { card.style.transition = ''; }, 620);
                });
            });
        }
    })();
    </script>
    @endverbatim
</body>
</html>
