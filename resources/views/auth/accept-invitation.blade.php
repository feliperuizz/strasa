<x-guest-layout title="Aceitar convite">
    <h1 class="mb-1 text-xl font-semibold text-white">Você foi convidado</h1>
    <p class="mb-5 text-sm text-slate-400">
        Entre para <span class="font-medium text-slate-200">{{ $invitation->company->name }}</span>
        como <span class="font-medium text-slate-200">{{ \App\Models\User::ROLES[$invitation->role] ?? $invitation->role }}</span>.
        <br>E-mail: {{ $invitation->email }}
    </p>

    <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}" class="space-y-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm text-slate-300">Seu nome</label>
            <input name="name" value="{{ old('name', $invitation->name) }}" required
                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
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
        <button class="w-full rounded-lg bg-brand-600 py-2 text-sm font-semibold text-white hover:bg-brand-500">Entrar na agência</button>
    </form>
</x-guest-layout>
