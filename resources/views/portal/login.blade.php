@extends('portal.layout', ['title' => 'Entrar', 'hideChrome' => true])

@section('styles')
    .gate { max-width: 420px; margin: 6vh auto 0; }
    .gate-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 18px;
        padding: 38px 34px 34px;
        box-shadow: 0 18px 50px -30px rgba(15, 20, 30, 0.28);
        text-align: center;
    }
    .gate-logo { margin-bottom: 26px; display: flex; justify-content: center; }
    .gate-logo img { height: 54px; width: auto; max-width: 200px; object-fit: contain; }
    .gate-logo .fallback {
        width: 56px; height: 56px; border-radius: 15px;
        background: var(--accent); color: #fff;
        display: grid; place-items: center; font-weight: 700; font-size: 20px;
    }
    .gate h1 { font-size: 22px; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 8px; }
    .gate .sub { color: var(--muted); font-size: 14.5px; margin-bottom: 28px; }
    .code-input {
        width: 100%; text-align: center;
        font-size: 21px; font-weight: 700; letter-spacing: 0.14em;
        text-transform: uppercase;
        padding: 15px 14px;
        border: 1px solid var(--line-strong); border-radius: 12px;
        background: var(--bg);
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .code-input::placeholder { letter-spacing: 0.1em; font-weight: 500; color: var(--dim); }
    .code-input:focus {
        outline: none; border-color: var(--accent);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 16%, transparent);
    }
    .gate-help { margin-top: 22px; font-size: 12.5px; color: var(--dim); line-height: 1.6; }
@endsection

@section('content')
    <div class="gate">
        <div class="gate-card">
            <div class="gate-logo">
                @if($client->logo_url)
                    <img src="{{ $client->logo_url }}" alt="{{ $client->name }}">
                @else
                    <span class="fallback">{{ mb_strtoupper(mb_substr($client->name, 0, 2)) }}</span>
                @endif
            </div>

            <h1>{{ $client->name }}</h1>
            <p class="sub">Digite o código de acesso que você recebeu para ver as peças aguardando aprovação.</p>

            @if($errors->any())
                <div class="errbox">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('portal.login.attempt', $portal->token) }}">
                @csrf
                <div class="field">
                    <input
                        type="text"
                        name="code"
                        class="code-input"
                        placeholder="STR-XXXX-XXXX"
                        autocomplete="one-time-code"
                        autocapitalize="characters"
                        spellcheck="false"
                        required
                        autofocus>
                </div>

                <button type="submit" class="btn btn-accent btn-block">Entrar no painel</button>
            </form>

            <p class="gate-help">
                Não tem o código ou ele parou de funcionar?<br>
                Fale com a nossa equipe que enviamos um novo.
            </p>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Formata o código enquanto digita, para o cliente não precisar acertar
    // os hífens (aceita colar "strxxxxxxxx" ou "STR-XXXX-XXXX").
    (function () {
        var campo = document.querySelector('.code-input');
        if (!campo) return;

        campo.addEventListener('input', function () {
            var bruto = campo.value.toUpperCase().replace(/[^A-Z0-9]/g, '');

            if (bruto.startsWith('STR')) {
                bruto = bruto.slice(3);
            }

            var partes = ['STR'];
            if (bruto.length) partes.push(bruto.slice(0, 4));
            if (bruto.length > 4) partes.push(bruto.slice(4, 8));

            campo.value = partes.join('-');
        });
    })();
</script>
@endsection
