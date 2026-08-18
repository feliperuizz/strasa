<x-app-layout title="Editar cliente: {{ $client->name }}">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.index') }}" class="text-sm text-slate-400 hover:text-white">Clientes</a>
            <span class="text-slate-600">/</span>
            <a href="{{ route('clients.show', $client) }}" class="text-sm text-slate-400 hover:text-white">{{ $client->name }}</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-white">Editar</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-3xl">
        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <form method="POST" action="{{ route('clients.update', $client) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PATCH')
                
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Nome do cliente <span class="text-rose-400">*</span></label>
                        <input name="name" value="{{ old('name', $client->name) }}" required autofocus
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                        @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Segmento/Nicho</label>
                        <input name="segment" value="{{ old('segment', $client->segment) }}" placeholder="Ex: Advocacia, Odontologia..."
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
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
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-400 file:mr-4 file:rounded-md file:border-0 file:bg-ink-700 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-ink-600 focus:outline-none">
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
                                    @click="if(confirm('Tem certeza absoluta? ISSO EXCLUIRÁ TODOS OS PROJETOS, TAREFAS E DADOS DESSE CLIENTE! Não tem volta.')) { document.getElementById('delete-client-form').submit(); }"
                                    class="text-sm font-medium text-rose-400 hover:text-rose-300">
                                Excluir cliente
                            </button>
                        @endcan
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('clients.show', $client) }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:text-white">Cancelar</a>
                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">Salvar alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @can('delete', $client)
        <form id="delete-client-form" action="{{ route('clients.destroy', $client) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endcan
</x-app-layout>
