@php
    $client = $portal->client;
@endphp

@extends('portal.layout', ['title' => 'Acesso indisponível', 'hideChrome' => true])

@section('styles')
    .gate { max-width: 420px; margin: 8vh auto 0; }
    .gate-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 18px;
        padding: 40px 34px;
        box-shadow: 0 18px 50px -30px rgba(15, 20, 30, 0.28);
        text-align: center;
    }
    .gate-card .icon { font-size: 34px; margin-bottom: 16px; }
    .gate-card h1 { font-size: 20px; font-weight: 800; letter-spacing: -0.025em; margin-bottom: 10px; }
    .gate-card p { color: var(--muted); font-size: 14.5px; line-height: 1.65; }
@endsection

@section('content')
    <div class="gate">
        <div class="gate-card">
            <div class="icon">🔒</div>
            <h1>Este acesso não está mais ativo</h1>
            <p>
                O link de aprovação de <strong>{{ $client->name }}</strong> foi desativado.
                Fale com a nossa equipe para receber um novo link e código.
            </p>
        </div>
    </div>
@endsection
