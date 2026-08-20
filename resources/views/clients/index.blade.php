<x-app-layout title="Clientes">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-base font-semibold text-slate-200">Clientes</h1>
            @can('create', \App\Models\Client::class)
                <a href="{{ route('clients.create') }}" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500">＋ Novo cliente</a>
            @endcan
        </div>
    </x-slot>

    <div class="p-4 sm:p-6">
        @if($clients->isEmpty())
            <div class="rounded-xl border border-dashed border-ink-600 p-10 text-center text-slate-400">
                Nenhum cliente cadastrado ainda.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($clients as $client)
                    <div class="rounded-xl border border-ink-600 bg-ink-800 p-4 {{ $client->isArchived() ? 'opacity-60' : '' }} relative overflow-hidden group hover:border-brand-500/60 transition shadow-sm">
                        @if($client->background_style || $client->color)
                            <div class="h-1.5 w-full absolute top-0 left-0 transition-opacity" style="{{ $client->background_style ?: ('background-color: ' . $client->color) }}"></div>
                        @endif

                        <div class="flex items-center gap-3 mt-0.5">
                            @if($client->logo_url)
                                <img src="{{ $client->logo_url }}" class="h-10 w-10 rounded-lg object-cover ring-1 ring-white/10" alt="">
                            @else
                                <span class="grid h-10 w-10 place-items-center rounded-lg text-sm font-bold text-slate-200 shadow-sm" style="{{ $client->background_style ?: ('background: ' . ($client->color ?? '#'.substr(md5($client->name),0,6))) }}">
                                    {{ \Illuminate\Support\Str::substr($client->name,0,1) }}
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('clients.show', $client) }}" class="block truncate font-medium text-slate-100 group-hover:text-brand-400 transition">{{ $client->name }}</a>
                                <div class="truncate text-xs text-slate-400">{{ $client->segment ?: 'Sem segmento' }}</div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-ink-700/60">
                            <span>{{ $client->projects_count }} projeto(s)</span>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('clients.calendar', $client) }}" class="hover:text-brand-400 transition">Calendário</a>
                                @can('update', $client)
                                    <a href="{{ route('clients.edit', $client) }}" class="hover:text-brand-400 transition">Editar</a>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
