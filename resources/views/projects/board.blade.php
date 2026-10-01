<x-app-layout title="{{ $project->name }} · {{ $project->client->name }}" :client="$client">
    <x-slot name="header">
        @include('projects.partials.cabecalho', ['aba' => 'quadro'])
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

    {{-- Fora do @push: o painel precisa existir no corpo da pagina antes do
         script que o procura. Dentro da pilha de scripts, o <div> saia depois
         do JS e a funcao de abrir nunca chegava a ser definida. --}}
    @include('projects.partials.flag-popover')

    @push('scripts')

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

                            // Persiste no servidor (keepalive lá dentro evita o cancelamento
                            // se a página navegar logo em seguida). Coluna que exige motivo,
                            // como "Rejeitado", abre a caixinha pedindo o motivo.
                            window.moverCardNoServidor(taskId, {
                                column_id: newColumnId,
                                ordered_ids: order
                            })
                            .catch(err => {
                                // Falhou no servidor (ou a pessoa desistiu de informar o
                                // motivo): devolve o card para a coluna de origem, mantendo
                                // quadro e banco iguais.
                                const ref = fromList.children[evt.oldIndex] || null;
                                fromList.insertBefore(itemEl, ref);
                                updateColumnCounts();

                                if (err && err.message === '__cancelado__') {
                                    return;
                                }
                                if (err && err.message === '__sessao__') {
                                    window.sessaoExpirou(419);
                                    return;
                                }

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
                
                const taskId = this.taskId;
                this.open = false;

                window.moverCardNoServidor(taskId, { column_id: columnId }).then(() => {
                    // Busca o card ja na coluna nova, sem recarregar a pagina.
                    window.atualizarCardDoQuadro(taskId);
                }).catch(err => {
                    if (err && err.message === '__cancelado__') { return; }
                    if (err && err.message === '__sessao__') { window.sessaoExpirou(419); return; }
                    alert('Não foi possível mover o card.' + String.fromCharCode(10, 10) + (err && err.message ? err.message : err));
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
                    }).then(res => {
                        if (!res.ok) { throw new Error('HTTP ' + res.status); }
                        const card = document.querySelector(`.task-card[data-id="${this.taskId}"]`);
                        if (card) { card.remove(); }
                        window.dispatchEvent(new CustomEvent('kanban-recontar'));
                    }).catch(() => {
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
