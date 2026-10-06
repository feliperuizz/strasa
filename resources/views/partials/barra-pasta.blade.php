{{-- Uma pasta de clientes na barra lateral. Cada usuário tem as suas
     (window.barraLateral guarda no usuário). Abre sozinha se o cliente da
     página atual estiver dentro. --}}
@php
    $temAtivo = $pasta['clientes']->contains(fn ($c) => request()->is('clients/'.$c->id.'*') || $c->projects->contains('id', request()->route('project')?->id));
@endphp
<div class="sidebar-folder" data-pasta="{{ $pasta['id'] }}" data-nome="{{ $pasta['nome'] }}"
     x-data="{ aberta: {{ ($pasta['aberta'] || $temAtivo) ? 'true' : 'false' }} }"
     x-init="$watch('aberta', () => window.barraLateral && window.barraLateral.salvar())">
    <div class="group flex items-center rounded-md hover:bg-ink-700">
        <button type="button" @click="window.__barraArrastou || (aberta = !aberta)"
                class="flex min-w-0 flex-1 items-center gap-2 px-2 py-1.5 text-left text-sm text-slate-300">
            <span class="text-slate-500" x-text="aberta ? '▾' : '▸'">▸</span>
            <svg class="h-4 w-4 shrink-0 text-amber-300/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <span class="truncate flex-1 font-medium" data-nome-pasta>{{ $pasta['nome'] }}</span>
            <span class="text-[11px] text-slate-500" data-conta-pasta>{{ $pasta['clientes']->count() }}</span>
        </button>
        <div class="relative pr-1" x-data="{ menu: false }">
            <button type="button" @click.stop="menu = !menu" title="Opções da pasta"
                    class="rounded px-1.5 py-0.5 text-slate-500 opacity-0 transition hover:bg-ink-600 hover:text-slate-200 focus:opacity-100 group-hover:opacity-100">⋯</button>
            <div x-show="menu" x-cloak @click.outside="menu = false"
                 class="absolute right-0 top-7 z-30 w-36 rounded-lg border border-ink-600 bg-ink-800 py-1 text-[13px] shadow-xl">
                <button type="button" @click="menu = false; window.barraLateral.renomear($el.closest('.sidebar-folder'))"
                        class="block w-full px-3 py-1.5 text-left text-slate-300 hover:bg-ink-700">Renomear</button>
                <button type="button" @click="menu = false; window.barraLateral.excluir($el.closest('.sidebar-folder'))"
                        class="block w-full px-3 py-1.5 text-left text-rose-300 hover:bg-rose-500/10">Excluir pasta</button>
            </div>
        </div>
    </div>
    <div x-show="aberta" x-cloak class="sidebar-folder-list ml-4 space-y-0.5 border-l border-ink-600 pl-1" data-lista-pasta>
        @foreach($pasta['clientes'] as $client)
            @include('partials.barra-cliente', ['client' => $client])
        @endforeach
    </div>
</div>
