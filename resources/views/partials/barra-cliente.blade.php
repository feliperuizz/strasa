{{-- Um cliente na barra lateral, solto ou dentro de uma pasta. Arrastável
     (window.barraLateral): o clique só abre/fecha se não foi um arraste. --}}
@php
    $ativo = request()->is('clients/'.$client->id.'*') || $client->projects->contains('id', request()->route('project')?->id);
@endphp
<div x-data="{ open: {{ $ativo ? 'true' : 'false' }} }"
     data-client-id="{{ $client->id }}"
     class="sidebar-client-item">
    <button type="button" @click="window.__barraArrastou || (open = !open)"
            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-slate-300 hover:bg-ink-700">
        <span class="text-slate-500" x-text="open ? '▾' : '▸'"></span>
        @if($client->logo_url)
            <img src="{{ $client->logo_url }}" class="h-5 w-5 rounded object-cover" alt="{{ $client->name }}">
        @else
            <span class="grid h-5 w-5 place-items-center rounded text-[10px] font-bold text-slate-200 shadow-sm" style="{{ $client->background_style ?: ('background: ' . ($client->color ?? '#'.substr(md5($client->name),0,6))) }}">
                {{ \Illuminate\Support\Str::substr($client->name,0,1) }}
            </span>
        @endif
        <span class="truncate flex-1">{{ $client->name }}</span>
    </button>
    <div x-show="open" x-cloak class="ml-7 space-y-0.5 border-l border-ink-600 pl-2">
        @foreach($client->projects as $project)
            <a href="{{ route('projects.board', $project) }}"
               class="block truncate rounded px-2 py-1 text-[13px] {{ (int) optional(request()->route('project'))->id === $project->id ? 'bg-ink-600 text-slate-200' : 'text-slate-400 hover:text-slate-200 hover:bg-ink-700' }}">
                {{ $project->name }}
            </a>
        @endforeach
        <a href="{{ route('clients.calendar', $client) }}" class="block rounded px-2 py-1 text-[12px] {{ request()->routeIs('clients.calendar') && (int) request()->route('client')?->id === $client->id ? 'text-brand-400' : 'text-slate-500 hover:text-brand-400' }}">▤ Calendário</a>
        <a href="{{ route('clients.metrics', $client) }}" class="block rounded px-2 py-1 text-[12px] {{ request()->routeIs('clients.metrics') && (int) request()->route('client')?->id === $client->id ? 'text-brand-400' : 'text-slate-500 hover:text-brand-400' }}">◗ Métricas</a>
        <a href="{{ route('clients.portal', $client) }}" class="block rounded px-2 py-1 text-[12px] {{ request()->routeIs('clients.portal') && (int) request()->route('client')?->id === $client->id ? 'text-brand-400' : 'text-slate-500 hover:text-brand-400' }}">✓ Aprovação</a>
        @can('create', \App\Models\Project::class)
            <a href="{{ route('projects.create', $client) }}" class="block rounded px-2 py-1 text-[12px] text-slate-500 hover:text-brand-400">＋ Novo projeto</a>
        @endcan
    </div>
</div>
