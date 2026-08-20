<x-app-layout title="{{ $project->name }} · {{ $project->client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                @if($project->client->logo_url)
                    <img src="{{ $project->client->logo_url }}" alt="{{ $project->client->name }}" class="h-6 w-6 rounded-md object-cover ring-1 ring-ink-600">
                @else
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-ink-600 text-[10px] font-bold text-white ring-1 ring-ink-600" style="background-color: {{ $project->client->color ?? '#64748b' }}">{{ substr($project->client->name, 0, 2) }}</span>
                @endif
                <a href="{{ route('clients.show', $project->client) }}" class="text-xl font-bold text-slate-400 hover:text-slate-200 tracking-wide transition ml-1">{{ $project->client->name }}</a>
                <span class="text-slate-600">/</span>
                <h1 class="text-xl font-bold text-slate-200 tracking-wide">{{ $project->name }}</h1>
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="ml-2 text-slate-500 hover:text-brand-400" title="Editar Projeto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </a>
                @endcan
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
            <a href="{{ route('projects.board', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Quadro</a>
            <span class="text-sm font-semibold text-slate-200 border-b-2 border-white pb-2 cursor-pointer">Lista</span>
            <a href="{{ route('projects.calendar', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Calendário</a>
            <span class="text-sm text-slate-400 hover:text-slate-200 pb-2 cursor-pointer">＋</span>
        </div>
    </x-slot>

    <div class="flex h-[calc(100vh-4rem)] flex-col">

        <div class="px-4 pt-4 pb-2 flex items-center gap-4">
            <button type="button" @click="$dispatch('open-task-modal', '{{ route('tasks.create', $project) }}')" class="inline-flex items-center gap-1 rounded bg-ink-800 px-3 py-1.5 text-sm font-medium text-slate-200 hover:bg-slate-700 transition">
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
                    class="block w-full px-4 py-1.5 text-left text-sm text-slate-300 hover:bg-ink-700 hover:text-slate-200"
                    :class="{'opacity-50 cursor-not-allowed': col.id == currentColumn}">
                <span x-text="col.name"></span>
            </button>
        </template>
        
        <div class="my-1 border-t border-ink-700"></div>
        <button @click="deleteTask" class="flex w-full items-center gap-2 px-4 py-1.5 text-left text-sm text-rose-400 hover:bg-ink-700">
            Excluir card
        </button>
    {{-- Notes Slide-over --}}
    <div x-data="{
             open: false,
             content: '',
             saveTimeout: null,
             quill: null,
             saveStatus: '',
             initNotes() {
                 this.content = this.$refs.hiddenNotesInput.value;
                 this.quill = new Quill(this.$refs.notesEditor, {
                     theme: 'snow',
                     placeholder: 'Digite suas notas aqui...',
                     modules: {
                         toolbar: [
                             ['bold', 'italic', 'underline', 'strike'],
                             [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                             ['link'],
                             ['clean']
                         ]
                     }
                 });
                 this.quill.on('text-change', () => {
                     this.content = this.quill.root.innerHTML;
                     this.saveStatus = 'Salvando...';
                     clearTimeout(this.saveTimeout);
                     this.saveTimeout = setTimeout(() => { this.saveNotes(); }, 1000);
                 });
             },
             async saveNotes() {
                 try {
                     const res = await fetch('{{ route('projects.notes.store', $project) }}', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                             'Accept': 'application/json'
                         },
                         body: JSON.stringify({ content: this.content })
                     });
                     if (res.ok) {
                         this.saveStatus = 'Salvo';
                         setTimeout(() => { if(this.saveStatus === 'Salvo') this.saveStatus = ''; }, 2000);
                     } else {
                         this.saveStatus = 'Erro ao salvar';
                     }
                 } catch (e) {
                     this.saveStatus = 'Erro de conexão';
                 }
             }
         }"
         @open-notes.window="open = true"
         x-init="initNotes()"
         x-show="open" 
         class="relative z-40" x-cloak>
         
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    <div x-show="open"
                         x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full"
                         class="pointer-events-auto w-screen max-w-md">
                        
                        <div class="flex h-full flex-col bg-ink-900 shadow-2xl border-l border-ink-600">
                            {{-- Header --}}
                            <div class="flex items-center justify-between border-b border-ink-700 px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-bold text-slate-200">Minhas Notas</h2>
                                    <span class="text-xs text-brand-400 font-medium" x-show="saveStatus" x-text="saveStatus"></span>
                                </div>
                                <button @click="open = false" class="rounded p-1 text-slate-400 hover:bg-ink-700 hover:text-slate-200" title="Fechar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            {{-- Body --}}
                            <div class="flex-1 overflow-y-auto flex flex-col p-6 h-full">
                                <div class="mb-4">
                                    <p class="text-sm text-slate-400">Estas notas são privadas. Apenas você pode vê-las neste quadro.</p>
                                </div>
                                <input type="hidden" x-ref="hiddenNotesInput" value="{{ $myNote?->content ?? '' }}">
                                <div class="flex-1 border border-ink-600 rounded flex flex-col min-h-0 bg-ink-900 text-slate-200">
                                    <div x-ref="notesEditor" class="flex-1 h-full overflow-y-auto">{!! $myNote?->content ?? '' !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endpush
</x-app-layout>
