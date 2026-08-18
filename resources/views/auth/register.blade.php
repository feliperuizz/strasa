<x-guest-layout title="Criar conta">
    <h1 class="mb-1 text-xl font-semibold text-white">Cadastrar agência</h1>
    <p class="mb-5 text-sm text-slate-400">Crie sua empresa e o usuário administrador.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm text-slate-300">Nome da agência</label>
            <input name="company_name" value="{{ old('company_name') }}" required autofocus
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            @error('company_name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-300">Seu nome</label>
            <input name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm text-slate-300">E-mail</label>
            <input name="email" type="email" value="{{ old('email') }}" required
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            @error('email') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1 block text-sm text-slate-300">Senha</label>
                <input name="password" type="password" required
                       class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
                @error('password') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm text-slate-300">Confirmar</label>
                <input name="password_confirmation" type="password" required
                       class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            </div>
        </div>
        <button class="w-full rounded-lg bg-brand-600 py-2 text-sm font-semibold text-white hover:bg-brand-500">Criar conta</button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-400">
        Já tem conta? <a href="{{ route('login') }}" class="text-brand-400 hover:underline">Entrar</a>
    </p>
</x-guest-layout>
