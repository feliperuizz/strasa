<x-app-layout title="Editar Projeto" :client="$project->client" :project="$project">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.show', $project->client) }}" class="text-sm text-slate-400 hover:text-white">{{ $project->client->name }}</a>
            <span class="text-slate-600">/</span>
            <a href="{{ route('projects.board', $project) }}" class="text-sm text-slate-400 hover:text-white">{{ $project->name }}</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-white">Editar Projeto</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-2xl">
        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                
                <div>
                    <label class="mb-1 block text-sm text-slate-300">Nome do projeto <span class="text-rose-400">*</span></label>
                    <input name="name" value="{{ old('name', $project->name) }}" required autofocus placeholder="Ex: Rede Social 2026"
                           class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm text-slate-300">Descrição (Opcional)</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">{{ old('description', $project->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-ink-600">
                    <a href="{{ route('projects.board', $project) }}" class="text-sm text-slate-300 hover:text-white">Cancelar</a>
                    <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
