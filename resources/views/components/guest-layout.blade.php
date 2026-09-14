@props(['title' => null, 'subtitle' => null])

<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050505">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v={{ config('app.icon_version') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ config('app.icon_version') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- CSS compilado (Vite): as telas de convite e cadastro ainda usam classes
         Tailwind por dentro. As variáveis reproduzem a paleta do app. --}}
    @vite(['resources/css/app.css'])

    @verbatim
    <style>
        :root {
            --ink-900: 15 17 21;
            --ink-800: 22 26 33;
            --ink-700: 29 34 43;
            --ink-600: 39 45 57;
            --ink-500: 107 107 107;
            --text-primary: 226 232 240;
            --text-secondary: 203 213 225;
            --text-tertiary: 148 163 184;

            /* Mesma paleta da landing: preto e branco, sem cor de destaque. */
            --bg:        #050505;
            --surface:   #111113;
            --line:      rgba(255, 255, 255, 0.09);
            --line-str:  rgba(255, 255, 255, 0.18);
            --text:      #fafafa;
            --muted:     #a3a3a8;
            --dim:       #6d6d74;
            --ease:      cubic-bezier(0.22, 1, 0.36, 1);
        }

        html, body { height: 100%; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow-x: hidden;
        }

        /* Grão sutil, igual ao da landing — tira o aspecto chapado do preto. */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 50;
            opacity: 0.035;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* Grade em perspectiva e halo no topo. */
        .gate-bg {
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
            background-size: 62px 62px;
            mask-image: radial-gradient(ellipse 80% 55% at 50% 0%, #000 15%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse 80% 55% at 50% 0%, #000 15%, transparent 70%);
        }
        .gate-glow {
            position: fixed;
            width: 900px; height: 900px;
            left: 50%; top: -420px;
            transform: translateX(-50%);
            background: radial-gradient(circle, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.03) 32%, transparent 62%);
            pointer-events: none;
        }

        /* ------------------------------------------------------------ */
        /* Cartão                                                        */
        /* ------------------------------------------------------------ */
        .gate {
            position: relative;
            z-index: 1;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 20px;
        }

        .gate-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            margin-bottom: 30px;
            opacity: 0;
            transform: translateY(10px);
            animation: entra 0.7s var(--ease) forwards;
        }
        .gate-brand img {
            height: 34px;
            width: auto;
            filter: brightness(0) invert(1);
            opacity: 0.95;
        }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 9px;
            padding: 6px 14px 6px 10px;
            border: 1px solid var(--line);
            border-radius: 100px;
            font-size: 12px; font-weight: 500; color: var(--muted);
            background: rgba(255,255,255,0.03);
        }
        .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #fff;
            animation: pulse 2.6s infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(255,255,255,0.55); }
            70%  { box-shadow: 0 0 0 9px rgba(255,255,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
        }

        .gate-card {
            width: 100%;
            max-width: 420px;
            background: linear-gradient(160deg, var(--surface) 0%, #0a0a0b 100%);
            border: 1px solid var(--line-str);
            border-radius: 20px;
            padding: 34px 32px 30px;
            box-shadow:
                0 40px 90px -40px rgba(0,0,0,0.95),
                0 0 80px -30px rgba(255,255,255,0.08);
            opacity: 0;
            transform: translateY(14px);
            animation: entra 0.7s var(--ease) 0.08s forwards;
        }
        @keyframes entra { to { opacity: 1; transform: none; } }

        .gate-card h1 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        .gate-card .sub {
            margin: 0 0 26px;
            font-size: 14.5px;
            color: var(--muted);
            line-height: 1.55;
        }

        /* ------------------------------------------------------------ */
        /* Formulário                                                    */
        /* ------------------------------------------------------------ */
        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--muted);
        }
        .input {
            width: 100%;
            box-sizing: border-box;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--line-str);
            border-radius: 11px;
            padding: 12px 14px;
            font: inherit;
            font-size: 15px;
            color: var(--text);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .input::placeholder { color: var(--dim); }
        .input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.55);
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.08);
        }

        .check {
            display: flex; align-items: center; gap: 9px;
            font-size: 13.5px; color: var(--muted);
            cursor: pointer;
            margin: 2px 0 22px;
        }
        .check input {
            width: 16px; height: 16px;
            accent-color: #fff;
            cursor: pointer;
        }

        .btn-primary {
            width: 100%;
            padding: 13px 20px;
            border: 0;
            border-radius: 100px;
            background: #fff;
            color: #000;
            font: inherit;
            font-size: 14.5px;
            font-weight: 700;
            letter-spacing: -0.01em;
            cursor: pointer;
            transition: transform 0.3s var(--ease), box-shadow 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px -8px rgba(255,255,255,0.45);
        }
        .btn-primary:active { transform: translateY(0); }

        .errbox {
            margin: 0 0 18px;
            padding: 11px 14px;
            border: 1px solid rgba(244, 63, 94, 0.35);
            background: rgba(244, 63, 94, 0.08);
            border-radius: 11px;
            font-size: 13.5px;
            color: #fda4af;
        }

        .gate-foot {
            margin-top: 26px;
            display: flex; gap: 18px; flex-wrap: wrap; justify-content: center;
            font-size: 12.5px; color: var(--dim);
            opacity: 0;
            animation: entra 0.7s var(--ease) 0.2s forwards;
        }
        .gate-foot a { color: inherit; text-decoration: none; transition: color 0.25s; }
        .gate-foot a:hover { color: var(--text); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001ms !important;
                transition-duration: 0.001ms !important;
            }
        }
    </style>
    @endverbatim
</head>
<body>
    <div class="gate-bg"></div>
    <div class="gate-glow"></div>

    <div class="gate">
        <a class="gate-brand" href="{{ url('/') }}">
            <img src="{{ asset('strasalogo.png') }}" alt="STRASA">
            <span class="eyebrow"><span class="dot"></span> Plataforma interna &middot; Equipe STRASA</span>
        </a>

        <div class="gate-card">
            {{ $slot }}
        </div>

        <div class="gate-foot">
            <a href="{{ url('/') }}">Início</a>
            <a href="{{ route('privacy') }}">Privacidade</a>
            <a href="{{ route('terms') }}">Termos</a>
            <a href="mailto:contato@consultoriastr.com.br">Suporte</a>
        </div>
    </div>
</body>
</html>
