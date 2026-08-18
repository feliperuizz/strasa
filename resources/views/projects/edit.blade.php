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

    <div x-data="{ showDeleteModal: false, confirmName: '' }">
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

                    <div class="pt-4 flex items-center justify-between border-t border-ink-600">
                        <div class="flex items-center">
                            @can('delete', $project)
                                <button type="button" 
                                        @click="showDeleteModal = true; confirmName = ''"
                                        class="text-sm font-medium text-rose-400 hover:text-rose-300">
                                    Excluir quadro
                                </button>
                            @endcan
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('projects.board', $project) }}" class="text-sm text-slate-300 hover:text-white">Cancelar</a>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">Salvar alterações</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @can('delete', $project)
            {{-- Modal de Exclusão (Dupla Confirmação) --}}
            <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div x-show="showDeleteModal" x-transition.opacity class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                
                <div x-show="showDeleteModal" 
                     x-transition.scale.95 
                     class="relative w-full max-w-md rounded-2xl border border-rose-900/50 bg-ink-800 p-6 shadow-2xl">
                    
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/10 text-rose-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>

                    <h3 class="text-lg font-bold text-white">Excluir Quadro Permanentemente</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Esta ação é <strong class="text-rose-400">irreversível</strong>. 
                        Isso excluirá o quadro <strong>{{ $project->name }}</strong>, além de todas as suas <strong>Colunas e Tarefas</strong> associadas.
                    </p>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-300">Para confirmar, digite o nome do quadro:</label>
                        <div class="mt-1 flex items-center gap-2 rounded-lg border border-ink-600 bg-ink-900 px-3 py-2">
                            <span class="text-slate-500 select-none">{{ $project->name }}</span>
                        </div>
                        <input type="text" x-model="confirmName" placeholder="Digite o nome aqui..."
                               class="mt-2 w-full rounded-lg border border-rose-900/50 bg-ink-900 px-3 py-2 text-sm text-white focus:border-rose-500 focus:ring-1 focus:ring-rose-500 focus:outline-none">
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showDeleteModal = false" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:bg-ink-700 hover:text-white">Cancelar</button>
                        <form action="{{ route('projects.destroy', $project) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    :disabled="confirmName !== '{{ $project->name }}'"
                                    :class="confirmName === '{{ $project->name }}' ? 'bg-rose-600 hover:bg-rose-500 text-white' : 'bg-rose-600/50 text-white/50 cursor-not-allowed'"
                                    class="rounded-lg px-4 py-2 text-sm font-medium transition">
                                Sim, excluir permanentemente
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</x-app-layout>
