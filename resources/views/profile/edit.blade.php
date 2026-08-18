<x-app-layout title="Meu Perfil">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white tracking-wide">Meu Perfil</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="bg-ink-800 rounded-xl border border-ink-600 p-6 space-y-6 shadow-xl">
            @csrf
            @method('PATCH')

            {{-- Avatar --}}
            <div class="flex items-center gap-6">
                <div class="relative group h-24 w-24">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-24 w-24 rounded-full object-cover ring-4 ring-ink-700">
                    @else
                        <span class="grid place-items-center rounded-full font-semibold text-white ring-4 ring-ink-700 h-24 w-24 text-2xl"
                              style="background: {{ $user->avatar_color ?? '#6366f1' }}">
                            {{ $user->initials() }}
                        </span>
                    @endif
                    <label class="absolute inset-0 flex cursor-pointer items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                        <span class="text-xs font-medium text-white">Alterar</span>
                        <input type="file" name="avatar" accept="image/*" class="hidden">
                    </label>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-white">Sua Foto</h3>
                    <p class="text-sm text-slate-400">Clique na imagem para enviar uma foto. JPG, PNG ou WEBP. Máx 5MB.</p>
                </div>
            </div>

            {{-- Cor do Avatar (se não tiver foto) --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Cor do Ícone</label>
                <input type="color" name="avatar_color" value="{{ old('avatar_color', $user->avatar_color ?? '#6366f1') }}" class="h-10 w-20 rounded border border-ink-600 bg-ink-900 p-1 cursor-pointer">
                <p class="text-xs text-slate-500 mt-1">Usada como fundo se você não tiver uma foto enviada.</p>
            </div>

            <hr class="border-ink-700">

            {{-- Nome --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Nome completo</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('name') <p class="text-rose-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                @error('email') <p class="text-rose-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <hr class="border-ink-700">

            {{-- Senha --}}
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Nova Senha</label>
                <input type="password" name="password" class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <p class="text-xs text-slate-500 mt-1">Deixe em branco para não alterar.</p>
                @error('password') <p class="text-rose-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Confirmar Senha</label>
                <input type="password" name="password_confirmation" class="w-full rounded-lg border border-ink-600 bg-ink-900/50 py-2 px-3 text-slate-200 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-ink-800 transition">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
