<x-guest-layout title="Entrar">
    <h1 class="mb-1 text-xl font-semibold text-slate-200">Entrar</h1>
    <p class="mb-5 text-sm text-slate-400">Acesse o painel de conteúdo da sua agência.</p>

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-700/50 bg-rose-900/30 px-3 py-2 text-sm text-rose-300">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm text-slate-300">E-mail</label>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-300">Senha</label>
            <input name="password" type="password" required
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="remember" checked class="rounded border-ink-600 bg-ink-900"> Manter conectado
        </label>
        <button class="w-full rounded-lg bg-brand-600 py-2 text-sm font-semibold text-white hover:bg-brand-500">Entrar</button>
    </form>

</x-guest-layout>
