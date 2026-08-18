<x-app-layout title="{{ $client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($client->logo_url)
                    <img src="{{ $client->logo_url }}" class="h-8 w-8 rounded object-cover ring-1 ring-white/10" alt="">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded text-xs font-bold text-white shadow-sm" style="{{ $client->background_style ?: ('background: ' . ($client->color ?? '#'.substr(md5($client->name),0,6))) }}">{{ \Illuminate\Support\Str::substr($client->name,0,1) }}</span>
                @endif
                <h1 class="text-base font-semibold text-white">{{ $client->name }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('clients.calendar', $client) }}" class="rounded-lg bg-ink-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-ink-600">Calendário</a>
                @can('update', $client)
                    <a href="{{ route('clients.edit', $client) }}" class="rounded-lg border border-ink-600 px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-ink-700 hover:text-white">Editar</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-medium text-white">Projetos</h2>
            @can('create', \App\Models\Project::class)
                <a href="{{ route('projects.create', $client) }}" class="rounded bg-brand-600 px-2 py-1 text-xs font-medium text-white hover:bg-brand-500">＋ Novo projeto</a>
            @endcan
        </div>

        @if($client->projects->isEmpty())
            <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 backdrop-blur-md p-10 text-center text-slate-400">
                Este cliente ainda não tem projetos.<br>
                @can('create', \App\Models\Project::class)
                    <a href="{{ route('projects.create', $client) }}" class="mt-2 inline-block text-brand-400 hover:underline">Criar o primeiro projeto</a>
                @endcan
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($client->projects as $project)
                    <a href="{{ route('projects.board', $project) }}" class="group block rounded-xl border border-ink-600/80 bg-ink-800/85 backdrop-blur-md p-4 transition hover:border-brand-500/80 hover:bg-ink-800 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium text-slate-100 group-hover:text-brand-400 transition">{{ $project->name }}</h3>
                            <span class="text-xs text-slate-400">{{ $project->tasks_count }} tarefas</span>
                        </div>
                        <div class="mt-2 text-sm text-slate-400 line-clamp-2">
                            {{ $project->description ?: 'Sem descrição' }}
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
