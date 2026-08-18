<x-app-layout title="{{ $project->name }} · {{ $project->client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-white tracking-wide">{{ $project->client->name }}</h1>
            
            {{-- Estrela de Favorito --}}
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
            <a href="{{ route('projects.board', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Quadro</a>
            <span class="text-sm font-semibold text-white border-b-2 border-white pb-2 cursor-pointer">Lista</span>
            <a href="{{ route('projects.calendar', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Calendário</a>
            <span class="text-sm text-slate-400 hover:text-slate-200 pb-2 cursor-pointer">＋</span>
        </div>
    </x-slot>

    <div class="flex h-[calc(100vh-4rem)] flex-col">

        <div class="px-4 pt-4 pb-2 flex items-center gap-4">
            <button type="button" @click="$dispatch('open-task-modal', '{{ route('tasks.create', $project) }}')" class="inline-flex items-center gap-1 rounded bg-[#2a2b2d] px-3 py-1.5 text-sm font-medium text-slate-200 hover:bg-slate-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Adicionar tarefa
            </button>

            {{-- Filtro: Minhas Tarefas --}}
            <a href="{{ request()->fullUrlWithQuery(['assignee' => request('assignee') == auth()->id() ? null : auth()->id()]) }}" 
               class="inline-flex items-center gap-1 rounded px-3 py-1.5 text-sm font-medium transition border {{ request('assignee') == auth()->id() ? 'bg-brand-600/20 text-brand-400 border-brand-500/30' : 'bg-transparent text-slate-400 border-ink-600 hover:text-white hover:border-slate-500' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Minhas Tarefas
            </a>
        </div>

        <div class="flex-1 overflow-auto p-4 space-y-6">
            @foreach($columns as $column)
                <div>
                    <div class="flex items-center gap-2 mb-2 px-2">
                        <span class="text-sm font-semibold text-slate-300 uppercase tracking-wide">{{ $column->name }}</span>
                        <span class="text-xs text-slate-500 font-medium">{{ $tasksByColumn->get($column->id, collect())->count() }}</span>
                    </div>
                    
                    <div class="rounded-xl border border-ink-600/80 bg-ink-800/85 backdrop-blur-md overflow-x-auto shadow-sm">
                        <table class="w-full text-left text-sm text-slate-300 min-w-[600px]">
                            <thead class="border-b border-ink-600 bg-ink-900/50 text-xs text-slate-400">
                                <tr>
                                    <th class="px-4 py-2 font-medium w-8"></th>
                                    <th class="px-4 py-2 font-medium">Tarefa</th>
                                    <th class="px-4 py-2 font-medium">Responsável</th>
                                    <th class="px-4 py-2 font-medium">Data</th>
                                    <th class="px-4 py-2 font-medium">Tags</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-700">
                                @forelse($tasksByColumn->get($column->id, collect()) as $task)
                                    <tr class="hover:bg-ink-700/50 cursor-pointer group" 
                                        @click="$dispatch('open-task-modal', '{{ route('tasks.edit', $task) }}')"
                                        @contextmenu.prevent.stop="$dispatch('open-context-menu', { taskId: {{ $task->id }}, currentColumn: {{ $task->column_id }}, event: $event, url: '{{ route('tasks.destroy', $task) }}' })">
                                        <td class="px-4 py-3" @click.stop>
                                            <button type="button" onclick="window.completeTask(this, {{ $task->id }}, event)" class="mt-0.5 text-slate-500 hover:text-emerald-400 focus:outline-none transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-200">{{ $task->title }}</td>
                                        <td class="px-4 py-3">
                                            @if($task->assignee)
                                                <div class="flex items-center gap-2">
                                                    <x-avatar :user="$task->assignee" :size="5" />
                                                    <span class="text-xs">{{ $task->assignee->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-500">Sem responsável</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs {{ optional($task->publish_date)->isPast() && !$task->is_published ? 'text-rose-400' : 'text-slate-400' }}">
                                            {{ optional($task->publish_date)->format('d/m/Y') ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($task->tags->isNotEmpty())
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($task->tags as $tag)
                                                        <span class="rounded px-1.5 py-0.5 text-[10px] font-medium" style="background: {{ $tag->color }}22; color: {{ $tag->color }}">#{{ $tag->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-center text-xs text-slate-500">Nenhuma tarefa nesta coluna.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        // Context Menu do Card
        window.contextMenuData = {
            open: false,
            x: 0, y: 0,
            taskId: null,
            currentColumn: null,
            deleteUrl: '',
            columns: @json($columns->map(fn($c) => ['id' => $c->id, 'name' => $c->name])),
            
            show(e) {
                this.taskId = e.detail.taskId;
                this.currentColumn = e.detail.currentColumn;
                this.deleteUrl = e.detail.url;
                this.open = true;
                
                // Manter dentro da tela
                this.$nextTick(() => {
                    let w = this.$refs.menu.offsetWidth || 160;
                    let h = this.$refs.menu.offsetHeight || 200;
                    this.x = Math.min(e.detail.event.clientX, window.innerWidth - w - 10);
                    this.y = Math.min(e.detail.event.clientY, window.innerHeight - h - 10);
                });
            },
            
            moveTask(columnId) {
                if (columnId == this.currentColumn) {
                    this.open = false;
                    return;
                }
                
                fetch(`{{ url('/tasks') }}/${this.taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ column_id: columnId })
                }).then(() => {
                    if (window.saveScrollPositions) window.saveScrollPositions();
                    window.location.reload();
                });
            },

            deleteTask() {
                if(confirm('Tem certeza absoluta que deseja excluir este card? Esta ação não pode ser desfeita!')) {
                    fetch(this.deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    }).then(() => {
                        if (window.saveScrollPositions) window.saveScrollPositions();
                        window.location.reload();
                    });
                }
                this.open = false;
            }
        };
    </script>

    {{-- Context Menu Element --}}
    <div x-data="contextMenuData" 
         @open-context-menu.window="show($event)"
         x-show="open" 
         @click.outside="open = false"
         @contextmenu.prevent="open = false"
         class="fixed z-50 rounded-lg border border-ink-600 bg-ink-800 shadow-xl py-1"
         x-ref="menu"
         x-cloak
         :style="`left: ${x}px; top: ${y}px; min-width: 160px;`">
        
        <div class="px-3 py-1.5 text-xs font-semibold uppercase text-slate-500">Mover para:</div>
        <template x-for="col in columns" :key="col.id">
            <button @click="moveTask(col.id)"
                    class="block w-full px-4 py-1.5 text-left text-sm text-slate-300 hover:bg-ink-700 hover:text-white"
                    :class="{'opacity-50 cursor-not-allowed': col.id == currentColumn}">
                <span x-text="col.name"></span>
            </button>
        </template>
        
        <div class="my-1 border-t border-ink-700"></div>
        <button @click="deleteTask" class="flex w-full items-center gap-2 px-4 py-1.5 text-left text-sm text-rose-400 hover:bg-ink-700">
            Excluir card
        </button>
    </div>
    @endpush
</x-app-layout>
