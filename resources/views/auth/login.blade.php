<x-guest-layout title="Entrar">
    <h1>Bem-vindo de volta</h1>
    <p class="sub">Entre para acessar os quadros, aprovações e métricas da equipe.</p>

    @if($errors->any())
        <div class="errbox">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" class="input"
                   value="{{ old('email') }}" placeholder="voce@agencia.com.br"
                   autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" class="input"
                   placeholder="••••••••" autocomplete="current-password" required>
        </div>

        <label class="check">
            <input type="checkbox" name="remember" checked>
            Manter conectado neste aparelho
        </label>

        <button type="submit" class="btn-primary">Entrar no sistema &rarr;</button>
    </form>
</x-guest-layout>
