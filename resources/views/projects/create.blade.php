<x-app-layout title="Novo Projeto" :client="$client">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.show', $client) }}" class="text-sm text-slate-400 hover:text-slate-200">{{ $client->name }}</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-slate-200">Novo Projeto</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-2xl">
        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <form method="POST" action="{{ route('projects.store', $client) }}" class="space-y-4">
                @csrf
                
                <div>
                    <label class="mb-1 block text-sm text-slate-300">Nome do projeto <span class="text-rose-400">*</span></label>
                    <input name="name" value="{{ old('name') }}" required autofocus placeholder="Ex: Rede Social 2026"
                           class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-300">Descrição (Opcional)</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-ink-600">
                    <a href="{{ route('clients.show', $client) }}" class="text-sm text-slate-300 hover:text-slate-200">Cancelar</a>
                    <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">Criar projeto</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
