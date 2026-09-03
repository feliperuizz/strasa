<x-app-layout title="{{ $project->name }} · {{ $project->client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <div class="flex flex-wrap items-center gap-2">
                @if($project->client->logo_url)
                    <img src="{{ $project->client->logo_url }}" alt="{{ $project->client->name }}" class="h-6 w-6 rounded-md object-cover ring-1 ring-ink-600">
                @else
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-ink-600 text-[10px] font-bold text-white ring-1 ring-ink-600" style="background-color: {{ $project->client->color ?? '#64748b' }}">{{ substr($project->client->name, 0, 2) }}</span>
                @endif
                <a href="{{ route('clients.show', $project->client) }}" class="text-base sm:text-xl font-bold text-slate-400 hover:text-slate-200 tracking-wide transition ml-1">{{ $project->client->name }}</a>
                <span class="text-slate-600">/</span>
                <h1 class="text-base sm:text-xl font-bold text-slate-200 tracking-wide">{{ $project->name }}</h1>
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
            <span class="text-sm font-semibold text-slate-200 border-b-2 border-white pb-2 cursor-pointer">Quadro</span>
            <a href="{{ route('projects.list', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Lista</a>
            <a href="{{ route('projects.calendar', $project) }}" class="text-sm text-slate-400 hover:text-slate-200 pb-2">Calendário</a>
            <span class="text-sm text-slate-400 hover:text-slate-200 pb-2 cursor-pointer">＋</span>
        </div>
    </x-slot>

    <div class="flex h-full flex-col" id="kanban-wrapper">

        {{-- Toolbar do Quadro (Add task etc) --}}
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

        {{-- Scroll horizontal para colunas --}}
        <div class="flex-1 overflow-x-auto overflow-y-hidden p-4" id="kanban-scroll-container">
            <div class="flex h-full items-stretch gap-4" id="kanban-board">
                @foreach($columns as $column)
                    <x-kanban-column :column="$column" :tasks="$tasksByColumn->get($column->id, collect())" :project="$project" />
                @endforeach

                {{-- Add new column button --}}
                @can('update', $project)
                <div class="w-72 shrink-0">
                    <form method="POST" action="{{ route('columns.store', $project) }}" class="flex items-center gap-2 rounded-xl bg-ink-800/40 p-2">
                        @csrf
                        <input type="text" name="name" placeholder="Nova coluna..." required class="w-full rounded bg-transparent px-2 py-1 text-sm text-slate-200 placeholder-slate-500 focus:outline-none">
                        <input type="color" name="color" value="#64748b" class="h-6 w-6 rounded border-0 bg-transparent p-0">
                        <button type="submit" class="rounded bg-ink-600 px-2 py-1 text-xs text-slate-300 hover:bg-ink-500 hover:text-slate-200">Add</button>
                    </form>
                </div>
                @endcan
            </div>
        </div>
    </div>

    @push('scripts')
    @include('projects.partials.flag-popover')

    <script>
        document.addEventListener('alpine:init', () => {
            // Recalcula o número exibido no cabeçalho de cada coluna a partir dos cards
            // realmente presentes no DOM (robusto a mudanças de layout).
            function updateColumnCounts() {
                document.querySelectorAll('[data-column]').forEach(col => {
                    const list = col.querySelector('.kanban-list');
                    const counter = col.querySelector('.column-count');
                    if (list && counter) {
                        counter.innerText = list.children.length;
                    }
                });
            }

            // Concluir uma tarefa move o card por fora do drag-and-drop; o
            // botao avisa por aqui para os contadores nao ficarem defasados.
            window.addEventListener('kanban-recontar', updateColumnCounts);

            // Drag to scroll no Kanban (clique no espaço vazio, incluindo no topo)
            const scrollContainer = document.getElementById('kanban-scroll-container');
            const wrapper = document.getElementById('kanban-wrapper');
            let isDown = false;
            let startX;
            let scrollLeft;

            wrapper.addEventListener('mousedown', (e) => {
                // Evita conflito com clique nos cards, botões, links ou drag de colunas
                if (e.target.closest('.kanban-list') || e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('.column-drag-handle')) {
                    return;
                }
                isDown = true;
                wrapper.style.cursor = 'grabbing';
                startX = e.pageX - scrollContainer.offsetLeft;
                scrollLeft = scrollContainer.scrollLeft;
            });

            wrapper.addEventListener('mouseleave', () => {
                isDown = false;
                wrapper.style.cursor = '';
            });

            wrapper.addEventListener('mouseup', () => {
                isDown = false;
                wrapper.style.cursor = '';
            });

            wrapper.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - scrollContainer.offsetLeft;
                const walk = (x - startX) * 1.5; // Velocidade do arraste
                scrollContainer.scrollLeft = scrollLeft - walk;
            });

            // Setup Sortable for drag and drop
            const lists = document.querySelectorAll('.kanban-list');
            lists.forEach(list => {
                new Sortable(list, {
                    group: 'shared',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    delay: 150,
                    delayOnTouchOnly: true,
                    fallbackTolerance: 5,
                    // Sinaliza que um arraste está em andamento para o card NÃO abrir o
                    // modal por engano com o clique sintético que segue o mouseup.
                    onStart: function () {
                        window.__kanbanDragging = true;
                    },
                    onEnd: function (evt) {
                        // Mantém a flag até depois do clique sintético (que dispara logo após o onEnd).
                        setTimeout(() => { window.__kanbanDragging = false; }, 0);

                        // IMPORTANTE: todo este corpo está protegido. Uma exceção aqui dentro
                        // interrompe a limpeza interna do SortableJS e trava os cards.
                        try {
                            const itemEl = evt.item;
                            const fromList = evt.from;
                            const toList = evt.to;

                            // Nada mudou (mesma coluna e mesma posição): não envia request.
                            if (fromList === toList && evt.oldIndex === evt.newIndex) {
                                return;
                            }

                            const taskId = itemEl.dataset.id;

                            // Resolve a coluna destino de forma resiliente: usa o data-column-id da
                            // lista, e se faltar (arquivo desatualizado) cai pro data-column da coluna
                            // pai. Se ainda assim não achar, aborta SEM mandar request quebrada.
                            const colEl = toList.closest('[data-column]');
                            const newColumnId = parseInt(toList.dataset.columnId || (colEl && colEl.dataset.column), 10);
                            if (!Number.isInteger(newColumnId)) {
                                const ref = fromList.children[evt.oldIndex] || null;
                                fromList.insertBefore(itemEl, ref);
                                updateColumnCounts();
                                alert('Não foi possível identificar a coluna de destino. Recarregue a página com Ctrl+F5.');
                                return;
                            }

                            // Nova ordem dos cards na coluna destino (somente ids válidos).
                            const order = Array.from(toList.children)
                                .map(c => parseInt(c.dataset.id, 10))
                                .filter(id => Number.isInteger(id));

                            // Atualiza os contadores imediatamente (otimista).
                            updateColumnCounts();

                            // Persiste no servidor. keepalive evita o cancelamento da request
                            // caso a página navegue/recarregue logo em seguida.
                            fetch(`{{ url('/tasks') }}/${taskId}/move`, {
                                method: 'POST',
                                keepalive: true,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    column_id: newColumnId,
                                    ordered_ids: order
                                })
                            })
                            .then(async res => {
                                if (!res.ok) {
                                    let detail = '';
                                    try { const j = await res.json(); detail = j.message || JSON.stringify(j.errors || j); }
                                    catch (e) { detail = (await res.text().catch(() => '')).slice(0, 400); }
                                    throw new Error('HTTP ' + res.status + (detail ? ' — ' + detail : ''));
                                }
                                return res.json();
                            })
                            .then(data => {
                                if (!data || data.ok === false) {
                                    throw new Error((data && data.message) || 'Falha ao mover');
                                }
                            })
                            .catch(err => {
                                // Falhou no servidor: devolve o card para a coluna de origem
                                // (mantém board e banco consistentes) e avisa o usuário.
                                const ref = fromList.children[evt.oldIndex] || null;
                                fromList.insertBefore(itemEl, ref);
                                updateColumnCounts();
                                console.error('Erro ao mover card:', err);
                                alert('Não foi possível mover o card.\n\nDetalhe técnico: ' + (err && err.message ? err.message : err));
                            });
                        } catch (e) {
                            console.error('Erro inesperado no drag-and-drop:', e);
                        }
                    },
                });
            });

            // Opcional: Sortable para colunas
            const board = document.getElementById('kanban-board');
            new Sortable(board, {
                animation: 150,
                handle: '.column-drag-handle', // header
                draggable: '[data-column]',
                onEnd: function(evt) {
                    const columns = Array.from(board.querySelectorAll('[data-column]')).map(c => c.dataset.column);
                    fetch(`{{ route('columns.reorder', $project) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ columns })
                    });
                }
            });
        });


        // Criar tarefa rapidamente via AJAX e abrir o modal
        window.quickCreateTask = function(projectId, columnId) {
            const list = document.querySelector(`.kanban-list[data-column-id="${columnId}"]`);
            
            fetch(`{{ url('/projects') }}/${projectId}/tasks`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: '',
                    column_id: columnId
                })
            })
            .then(async res => {
                if (!res.ok) {
                    let detail = '';
                    try { const j = await res.json(); detail = j.message || JSON.stringify(j.errors || j); }
                    catch (e) { detail = (await res.text().catch(() => '')).slice(0, 400); }
                    throw new Error('HTTP ' + res.status + (detail ? ' — ' + detail : ''));
                }
                return res.json();
            })
            .then(data => {
                if(data.task && data.html) {
                    // Inserir HTML do card no final da lista
                    list.insertAdjacentHTML('beforeend', data.html);

                    // Atualizar contador da coluna
                    const header = document.querySelector(`[data-column="${columnId}"]`).querySelector('.column-count');
                    if(header) header.innerText = parseInt(header.innerText) + 1;

                    // Abrir o modal de edição
                    const editUrl = `{{ url('/tasks') }}/${data.task.id}/edit`;
                    window.dispatchEvent(new CustomEvent('open-task-modal', { detail: editUrl }));
                } else {
                    throw new Error('Resposta inesperada do servidor');
                }
            })
            .catch(err => alert('Erro ao criar tarefa.\n\nDetalhe técnico: ' + (err && err.message ? err.message : err)));
        };

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
    </div>

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
