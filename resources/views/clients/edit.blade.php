<x-app-layout title="Editar cliente: {{ $client->name }}">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Clientes</a>
            <span class="text-slate-600">/</span>
            <a href="{{ route('clients.show', $client) }}" class="text-sm text-slate-400 hover:text-slate-200">{{ $client->name }}</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-slate-200">Editar</h1>
        </div>
    </x-slot>

    <div x-data="{ showDeleteModal: false, confirmName: '' }">
        <div class="p-4 sm:p-6 max-w-3xl">
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <form method="POST" action="{{ route('clients.update', $client) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PATCH')
                    
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm text-slate-300">Nome do cliente <span class="text-rose-400">*</span></label>
                            <input name="name" value="{{ old('name', $client->name) }}" required autofocus
                                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                            @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-slate-300">Segmento/Nicho</label>
                            <input name="segment" value="{{ old('segment', $client->segment) }}" placeholder="Ex: Advocacia, Odontologia..."
                                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                            @error('segment') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>



                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm text-slate-300">Logotipo (Imagem)</label>
                            @if($client->logo_url)
                                <div class="mb-3 flex items-center gap-3">
                                    <img src="{{ $client->logo_url }}" class="h-12 w-12 rounded-lg object-cover" alt="Logo atual">
                                    <span class="text-xs text-slate-500">Logo atual</span>
                                </div>
                            @endif
                            <input type="file" name="logo" accept="image/*"
                                   class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-400 file:mr-4 file:rounded-md file:border-0 file:bg-ink-700 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-slate-200 hover:file:bg-ink-600 focus:outline-none">
                            <p class="mt-1 text-xs text-slate-500">Envie uma nova imagem para substituir a atual.</p>
                            @error('logo') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm text-slate-300">Cor Principal</label>
                            <input type="color" name="color" value="{{ old('color', $client->color ?? '#6366f1') }}" class="h-10 w-20 rounded border border-ink-600 bg-ink-900 p-1 cursor-pointer">
                            <p class="text-xs text-slate-500 mt-1">Usada para destacar o cliente.</p>
                            @error('color') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Personalização de Fundo (Cor Fixa / Gradiente) --}}
                    @include('clients.partials.background-picker', ['client' => $client])

                    <div class="pt-4 border-t border-ink-600 flex items-center justify-between">
                        <div class="flex items-center">
                            @can('delete', $client)
                                <button type="button" 
                                        @click="showDeleteModal = true; confirmName = ''"
                                        class="text-sm font-medium text-rose-400 hover:text-rose-300">
                                    Excluir cliente
                                </button>
                            @endcan
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('clients.show', $client) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:text-slate-200">Cancelar</a>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">Salvar alterações</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @can('delete', $client)
            {{-- Modal de Exclusão (Dupla Confirmação) --}}
            <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div x-show="showDeleteModal" x-transition.opacity class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                
                <div x-show="showDeleteModal" 
                     x-transition.scale.95 
                     class="relative w-full max-w-md rounded-2xl border border-rose-900/50 bg-ink-800 p-6 shadow-2xl">
                    
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/10 text-rose-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>

                    <h3 class="text-lg font-bold text-slate-200">Excluir Cliente Permanentemente</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Esta ação é <strong class="text-rose-400">irreversível</strong>. 
                        Isso excluirá o cliente <strong>{{ $client->name }}</strong>, além de todos os seus <strong>Quadros e Tarefas</strong> associados.
                    </p>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-300">Para confirmar, digite o nome do cliente:</label>
                        <div class="mt-1 flex items-center gap-2 rounded-lg border border-ink-600 bg-ink-900 px-3 py-2">
                            <span class="text-slate-500 select-none">{{ $client->name }}</span>
                        </div>
                        <input type="text" x-model="confirmName" placeholder="Digite o nome aqui..."
                               class="mt-2 w-full rounded-lg border border-rose-900/50 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 focus:outline-none">
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showDeleteModal = false" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:bg-ink-700 hover:text-slate-200">Cancelar</button>
                        <form action="{{ route('clients.destroy', $client) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    :disabled="confirmName !== '{{ $client->name }}'"
                                    :class="confirmName === '{{ $client->name }}' ? 'bg-rose-600 hover:bg-rose-500 text-white' : 'bg-rose-600/50 text-white/50 cursor-not-allowed'"
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
