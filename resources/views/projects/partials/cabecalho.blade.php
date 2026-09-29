{{--
Cabeçalho do projeto (Quadro e Lista). Era copiado nas duas telas e a
cópia da Lista ficou para trás: sem quebra de linha, no celular ela
passava por cima do avatar e empurrava a página para o lado.
$aba: "quadro" ou "lista".
--}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        {{-- No celular: cliente pequeno em cima, projeto em destaque embaixo.
             No computador: tudo numa linha, "Cliente / Projeto". --}}
        <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5">
            <div class="flex min-w-0 items-center gap-2">
                @if($project->client->logo_url)
                    <img src="{{ $project->client->logo_url }}" alt="{{ $project->client->name }}" class="h-5 w-5 shrink-0 rounded-md object-cover ring-1 ring-ink-600 sm:h-6 sm:w-6">
                @else
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-ink-600 text-[9px] font-bold text-white ring-1 ring-ink-600 sm:h-6 sm:w-6 sm:text-[10px]" style="background-color: {{ $project->client->color ?? '#64748b' }}">{{ substr($project->client->name, 0, 2) }}</span>
                @endif
                <a href="{{ route('clients.show', $project->client) }}" class="min-w-0 text-[13px] font-semibold leading-snug text-slate-400 transition hover:text-slate-200 sm:ml-1 sm:text-xl sm:font-bold sm:tracking-wide">{{ $project->client->name }}</a>
            </div>
            <span class="hidden text-slate-600 sm:inline">/</span>
            <div class="flex min-w-0 basis-full items-start gap-2 sm:basis-auto sm:items-center">
                <h1 class="min-w-0 text-base font-bold leading-snug text-slate-200 sm:text-xl sm:tracking-wide">{{ $project->name }}</h1>
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="mt-1 shrink-0 text-slate-500 hover:text-brand-400 sm:ml-1 sm:mt-0" title="Editar Projeto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </a>
                @endcan
            </div>
        </div>
        <div x-data="{ 
            isFavorite: {{ auth()->user()->favoriteProjects()->where('project_id', $project->id)->exists() ? 'true' : 'false' }},
            toggle() {
                fetch('{{ route('projects.favorite', $project) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => this.isFavorite = data.is_favorite);
            }
        }">
            <button @click="toggle()" :class="isFavorite ? 'text-amber-400' : 'text-slate-500 hover:text-brand-400'">
                <svg class="w-5 h-5" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
            </button>
        </div>

        {{-- Botão Minhas Notas --}}
        <div>
            <button @click="$dispatch('open-notes')" class="flex items-center gap-1.5 rounded-md border border-ink-600 bg-ink-800 px-2 py-1 text-xs font-medium text-slate-300 hover:bg-ink-700 hover:text-slate-200 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Minhas Notas
            </button>
        </div>

        {{-- Status do Projeto --}}
        <div x-data="{
            status: '{{ $project->status }}',
            open: false,
            colors: {
                on_track: 'bg-emerald-400/20 text-emerald-400 border-emerald-400/30',
                at_risk: 'bg-amber-400/20 text-amber-400 border-amber-400/30',
                off_track: 'bg-rose-400/20 text-rose-400 border-rose-400/30',
                '': 'border-ink-600 text-slate-400 hover:bg-ink-800'
            },
            labels: {
                on_track: 'No prazo',
                at_risk: 'Em risco',
                off_track: 'Atrasado',
                '': 'Definir status'
            },
            updateStatus(newStatus) {
                fetch('{{ route('projects.status', $project) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    this.status = data.status || '';
                    this.open = false;
                });
            }
        }" class="relative">
            <button @click="open = !open" :class="'text-xs rounded-full px-2 py-0.5 cursor-pointer border transition ' + colors[status]" x-text="labels[status]"></button>
            
            <div x-show="open" @click.outside="open = false" style="display: none;" class="absolute left-0 mt-2 w-36 rounded-lg border border-ink-600 bg-ink-800 py-1 shadow-xl z-50">
                <button @click="updateStatus('on_track')" class="block w-full px-4 py-1.5 text-left text-xs text-emerald-400 hover:bg-ink-700">No prazo</button>
                <button @click="updateStatus('at_risk')" class="block w-full px-4 py-1.5 text-left text-xs text-amber-400 hover:bg-ink-700">Em risco</button>
                <button @click="updateStatus('off_track')" class="block w-full px-4 py-1.5 text-left text-xs text-rose-400 hover:bg-ink-700">Atrasado</button>
                <button @click="updateStatus('')" class="block w-full px-4 py-1.5 text-left text-xs text-slate-400 hover:bg-ink-700 border-t border-ink-700 mt-1 pt-1.5">Limpar</button>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-4 mt-3 border-b border-ink-600 pb-0.5">
        @if($aba === 'quadro')
            <span class="text-sm font-semibold text-slate-200 border-b-2 border-white pb-2 cursor-pointer">Quadro</span>
        @else
            <a href="{{ route('projects.board', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Quadro</a>
        @endif
        @if($aba === 'lista')
            <span class="text-sm font-semibold text-slate-200 border-b-2 border-white pb-2 cursor-pointer">Lista</span>
        @else
            <a href="{{ route('projects.list', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Lista</a>
        @endif
        <a href="{{ route('projects.calendar', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Calendário</a>
        <span class="text-sm text-slate-400 hover:text-slate-200 pb-2 cursor-pointer">＋</span>
    </div>
