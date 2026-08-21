<x-app-layout title="Novo cliente">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Clientes</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-slate-200">Novo</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-3xl">
        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
            <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Nome do cliente <span class="text-rose-400">*</span></label>
                        <input name="name" value="{{ old('name') }}" required autofocus
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                        @error('name') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Segmento/Nicho</label>
                        <input name="segment" value="{{ old('segment') }}" placeholder="Ex: Advocacia, Odontologia..."
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                        @error('segment') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Logotipo (Imagem)</label>
                        <input type="file" name="logo" accept="image/*"
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-400 file:mr-4 file:rounded-md file:border-0 file:bg-ink-700 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-slate-200 hover:file:bg-ink-600 focus:outline-none">
                        @error('logo') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        <div data-upload-progress></div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-slate-300">Cor Principal</label>
                        <input type="color" name="color" value="{{ old('color', '#6366f1') }}" class="h-10 w-20 rounded border border-ink-600 bg-ink-900 p-1 cursor-pointer">
                        <p class="text-xs text-slate-500 mt-1">Usada para destacar o cliente.</p>
                        @error('color') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Personalização de Fundo (Cor Fixa / Gradiente) --}}
                @include('clients.partials.background-picker')

                <div class="pt-4 border-t border-ink-600 flex items-center justify-end gap-3">
                    <a href="{{ route('clients.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:text-slate-200">Cancelar</a>
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">Salvar cliente</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
