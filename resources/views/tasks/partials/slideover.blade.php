<div x-data="taskForm(
        '{{ $task->exists ? route('tasks.update', $task) : route('tasks.store', $project) }}',
        '{{ $task->exists ? 'PATCH' : 'POST' }}',
        {{ $task->exists ? 'true' : 'false' }}
    )" 
    class="flex h-full flex-col bg-ink-900 text-slate-200">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-ink-800 px-6 py-4">
        <div class="flex items-center gap-3">
            @if($task->exists)
                <button type="button" @click="completeTaskAndClose({{ $task->id }})" class="group flex h-6 w-6 items-center justify-center rounded-full border border-slate-500 hover:border-emerald-400 hover:bg-emerald-900/30" title="Concluir tarefa">
                    <svg class="h-4 w-4 text-transparent group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </button>
            @endif
            <span class="text-sm text-slate-400">{{ $project->name }}</span>
        </div>
        <div class="flex items-center gap-4">
            <span x-show="saving" class="text-xs text-slate-400 transition" style="display: none;">Salvando...</span>
            <span x-show="saved" class="text-xs text-emerald-400 transition" style="display: none;">Salvo</span>
            @if($task->exists && auth()->user()->can('delete', $task))
                <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Tem certeza absoluta que deseja excluir este card? Esta ação não pode ser desfeita.')" class="inline-block">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded p-1 text-slate-400 hover:bg-rose-900/30 hover:text-rose-400" title="Excluir card">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </form>
            @endif

            <button @click="closeModal()" class="rounded p-1 text-slate-400 hover:bg-ink-800 hover:text-slate-200" title="Fechar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    {{-- Body --}}
    <div class="flex-1 overflow-y-auto px-6 py-4">
        <form id="task-auto-form" @submit.prevent @change="save()">
            @csrf
            
            {{-- Campos Ocultos Obrigatórios --}}
            <input type="hidden" name="column_id" value="{{ $task->column_id ?? request('column_id') }}">
            <input type="hidden" name="has_assignees" value="1">

            <div class="space-y-6">
                {{-- Título --}}
                <div>
                    <input type="text" name="title" value="{{ $task->title }}" placeholder="Escreva o nome da tarefa"
                           @input="updateTitle($event)"
                           class="w-full border-0 bg-transparent p-0 text-2xl font-bold text-slate-200 focus:ring-0 placeholder:text-slate-500">
                </div>

                {{-- Metadados rápidos --}}
                <div class="grid grid-cols-2 gap-4">
                    <div x-data="{ open: false }">
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Responsáveis</label>
                        <div class="flex flex-wrap gap-1 mb-2">
                            @if(isset($task->assignees) && $task->assignees->isNotEmpty())
                                @foreach($task->assignees as $assignee)
                                    <div class="inline-flex items-center gap-1 rounded-full bg-ink-800 pr-2 pl-1 py-1 border border-ink-700" title="{{ $assignee->name }}">
                                        <x-avatar :user="$assignee" size="4" />
                                        <span class="text-[11px] text-slate-300">{{ explode(' ', $assignee->name)[0] }}</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-xs text-slate-500 italic">Sem responsáveis</span>
                            @endif
                        </div>
                        
                        <div class="relative">
                            <button type="button" @click="open = !open" class="text-xs text-brand-400 hover:text-brand-300 transition font-medium">＋ Adicionar / Remover</button>
                            
                            <div x-show="open" @click.outside="open = false" style="display: none;" class="absolute left-0 mt-2 w-56 rounded-lg border border-ink-700 bg-ink-900 p-2 shadow-xl z-50 max-h-60 overflow-y-auto">
                                @foreach($members as $m)
                                    <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-ink-800 rounded cursor-pointer transition">
                                        <input type="checkbox" name="assignees[]" value="{{ $m->id }}" class="rounded border-ink-600 bg-ink-800 text-brand-500 focus:ring-brand-500"
                                            {{ (isset($task->assignees) && $task->assignees->contains($m->id)) ? 'checked' : '' }}>
                                        <x-avatar :user="$m" size="6" />
                                        <span class="text-sm text-slate-200">{{ $m->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Publicação</label>
                        <div class="flex items-center gap-2">
                            <input type="date" name="publish_date" value="{{ optional($task->publish_date)->format('Y-m-d') }}" @change="save()"
                                   class="w-full rounded border-0 bg-ink-800 py-1.5 pl-3 text-sm text-slate-200 focus:ring-1 focus:ring-brand-500 [color-scheme:dark]">
                            <input type="time" name="publish_time" value="{{ $task->publish_time ? \Carbon\Carbon::parse($task->publish_time)->format('H:i') : '' }}" @change="save()"
                                   class="w-24 rounded border-0 bg-ink-800 py-1.5 px-2 text-sm text-slate-200 focus:ring-1 focus:ring-brand-500 [color-scheme:dark]" title="Horário">
                        </div>
                    </div>
                </div>

                {{-- Tags / Flags --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Flags / Tags</label>
                    <div class="flex flex-wrap items-center gap-2 mb-2" id="tags-container">
                        @foreach($task->tags as $tag)
                            <div class="inline-flex items-center gap-1 rounded bg-ink-800 px-2 py-1 text-xs text-slate-300 border border-ink-600">
                                <span class="w-2 h-2 rounded-full" style="background: {{ $tag->color }}"></span>
                                {{ $tag->name }}
                                <input type="hidden" name="tags[]" value="{{ $tag->name }}|{{ $tag->color }}">
                                <button type="button" onclick="this.parentElement.remove(); document.getElementById('task-auto-form').dispatchEvent(new Event('change', {bubbles: true}))" class="text-slate-500 hover:text-rose-400 ml-1">&times;</button>
                            </div>
                        @endforeach
                    </div>
                    
                    <div x-data="seletorDeFlags(@js($allTags ?? []), @js(\App\Models\Tag::CORES))" class="relative">
                        <button type="button" @click="abrir = !abrir" class="text-xs text-brand-400 hover:text-brand-300 transition font-medium">＋ Adicionar flag</button>

                        <div x-show="abrir" @click.outside="abrir = false" style="display: none;"
                             class="absolute left-0 mt-2 w-64 rounded-lg border border-ink-800 bg-ink-900 p-3 shadow-xl z-50">

                            {{-- Flags disponíveis: as da empresa mais as padrão --}}
                            <div class="mb-3">
                                <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Disponíveis</p>
                                <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto">
                                    <template x-for="flag in disponiveis" :key="flag.name">
                                        <span class="group inline-flex items-center rounded transition hover:brightness-125"
                                              :style="'background:' + flag.color + '22'">
                                            <button type="button" @click="aplicar(flag)"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium"
                                                    :style="'color:' + flag.color">
                                                <span class="h-1.5 w-1.5 rounded-full" :style="'background:' + flag.color"></span>
                                                <span x-text="flag.name"></span>
                                            </button>
                                            {{-- Sugestão ainda não existe no banco: não há o que excluir. --}}
                                            <button type="button" x-show="flag.id" @click.stop="excluir(flag)"
                                                    title="Excluir esta flag de todos os cards"
                                                    class="px-1.5 py-1 text-[11px] leading-none text-slate-500 opacity-0 transition hover:text-rose-400 group-hover:opacity-100">&times;</button>
                                        </span>
                                    </template>
                                    <p x-show="!disponiveis.length" class="text-[11.5px] text-slate-500">Todas já estão neste card.</p>
                                </div>
                            </div>

                            {{-- Criar uma nova --}}
                            <div class="border-t border-ink-800 pt-3">
                                <p class="mb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Nova flag</p>

                                <input type="text" x-model="nome" placeholder="Nome da flag..." maxlength="40"
                                       @keydown.enter.prevent="criar()"
                                       class="w-full rounded bg-ink-800 px-2 py-1.5 text-xs border border-ink-800 focus:border-brand-500 focus:ring-0 mb-2 text-slate-200">

                                <div class="flex flex-wrap gap-1.5 mb-3">
                                    <template x-for="cor in cores" :key="cor">
                                        <button type="button" @click="corEscolhida = cor"
                                                :class="corEscolhida === cor ? 'ring-2 ring-white scale-110' : ''"
                                                class="w-5 h-5 rounded-full transition-transform" :style="'background: ' + cor"></button>
                                    </template>
                                </div>

                                <button type="button" @click="criar()"
                                        class="w-full rounded bg-brand-600 py-1.5 text-xs font-semibold text-white hover:bg-brand-500">Adicionar</button>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Descrição --}}
                <div x-data="{
                        quill: null,
                        initQuill() {
                            this.quill = new Quill($refs.editor, {
                                theme: 'snow',
                                placeholder: 'O que é essa tarefa?',
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
                                $refs.hiddenInput.value = this.quill.root.innerHTML;
                                $refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                            });
                        }
                    }" 
                    x-init="initQuill()">
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Descrição</label>
                    <input type="hidden" name="description" x-ref="hiddenInput" value="{{ $task->description }}">
                    <div x-ref="editor">{!! $task->description !!}</div>
                </div>
            </div>
        </form>

        @if($task->exists)
        <hr class="my-6 border-ink-800">

        {{-- Aprovação do cliente -------------------------------------------
             Aparece só quando o cliente tem painel criado. O estado vem do
             registro de submissão mais recente da tarefa. --}}
        @php $aprovacao = $task->currentApproval(); @endphp
        @if($task->client?->portal)
            <div x-data="{ enviando: false, estado: '{{ $aprovacao?->status ?? '' }}' }" class="mb-6">
                <div class="flex items-center justify-between gap-3 rounded-lg border border-ink-700 bg-ink-800/60 px-4 py-3">
                    <div class="min-w-0">
                        <div class="text-[13px] font-semibold text-slate-200">Painel de aprovação</div>

                        <div class="mt-0.5 text-[11.5px] text-slate-400">
                            @if($aprovacao?->isPending())
                                Aguardando {{ $task->client->name }} desde {{ $aprovacao->submitted_at?->diffForHumans() }}
                            @elseif($aprovacao?->isApproved())
                                <span class="text-emerald-400 font-medium">Aprovado</span>
                                por {{ $aprovacao->reviewer_name }} · {{ $aprovacao->responded_at?->format('d/m H:i') }}
                            @elseif($aprovacao?->isRejected())
                                <span class="text-rose-400 font-medium">Ajuste pedido</span>
                                por {{ $aprovacao->reviewer_name }} · {{ $aprovacao->responded_at?->format('d/m H:i') }}
                            @else
                                Ainda não enviado para {{ $task->client->name }}.
                            @endif
                        </div>
                    </div>

                    <div class="shrink-0">
                        @if($aprovacao?->isPending())
                            <button type="button" x-bind:disabled="enviando"
                                    @click="enviando = true; cancelarAprovacao('{{ route('approvals.cancel', $task) }}')"
                                    class="rounded-lg border border-ink-600 px-3 py-1.5 text-[12px] font-semibold text-slate-300 hover:bg-ink-700 transition disabled:opacity-50">
                                Retirar do painel
                            </button>
                        @else
                            <button type="button" x-bind:disabled="enviando"
                                    @click="enviando = true; enviarAprovacao('{{ route('approvals.submit', $task) }}')"
                                    class="rounded-lg bg-brand-600 px-3 py-1.5 text-[12px] font-semibold text-white hover:bg-brand-500 transition disabled:opacity-50">
                                <span x-text="enviando ? 'Enviando…' : '{{ $aprovacao ? 'Reenviar para aprovação' : 'Enviar para aprovação' }}'"></span>
                            </button>
                        @endif
                    </div>
                </div>

                @if($aprovacao?->isRejected() && filled($aprovacao->feedback))
                    <div class="mt-2 rounded-lg border border-rose-500/25 bg-rose-500/[0.06] px-3 py-2">
                        <div class="text-[10.5px] font-bold uppercase tracking-wider text-rose-400 mb-0.5">O que o cliente pediu</div>
                        <p class="text-[13px] text-slate-300 whitespace-pre-line">{{ $aprovacao->feedback }}</p>
                    </div>
                @endif
            </div>

            <hr class="my-6 border-ink-800">
        @endif

        {{-- Anexos --}}
        {{-- Anexos e Pastas --}}
        <div x-data="{ creatingFolder: false, newFolderName: '' }">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-slate-200">Anexos</h3>
                <button type="button" @click="creatingFolder = !creatingFolder" class="text-xs text-brand-400 hover:text-brand-300 font-medium transition">＋ Nova Pasta</button>
            </div>
            
            <div x-show="creatingFolder" style="display: none;" class="mb-4 rounded-lg bg-ink-800 p-3 border border-ink-600">
                <form @submit.prevent="createFolder($event); creatingFolder = false; newFolderName = ''" action="{{ route('folders.store', $task) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="name" x-model="newFolderName" placeholder="Nome da pasta..." required class="flex-1 rounded border-0 bg-ink-900 px-3 py-1.5 text-sm text-slate-200 focus:ring-1 focus:ring-brand-500">
                    <button type="submit" class="rounded bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-500">Criar</button>
                    <button type="button" @click="creatingFolder = false" class="text-xs text-slate-400 hover:text-slate-200">Cancelar</button>
                </form>
            </div>

            <div id="attachments-container" x-init="initTaskViewer($el)" class="space-y-6">
                {{-- Renderizar Pastas --}}
                @foreach($task->folders as $folder)
                    <div class="rounded-lg border border-ink-800 bg-ink-900/50 overflow-hidden mb-3" x-data="{ open: false, editing: false, folderName: '{{ $folder->name }}' }">
                        <div class="flex items-center justify-between bg-ink-800 px-3 py-2 cursor-pointer hover:bg-[#343538] transition" @click="if(!editing) open = !open">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-brand-400 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                                <span x-show="!editing" class="font-medium text-sm text-slate-200">{{ $folder->name }} <span class="text-xs text-slate-500 ml-1">({{ $folder->attachments->count() }})</span></span>
                                <form x-show="editing" style="display:none;" @submit.prevent="renameFolder('{{ route('folders.update', $folder) }}', $event, folderName); editing = false" class="flex items-center gap-2">
                                    <input type="text" x-model="folderName" class="h-6 rounded border-0 bg-ink-900 px-2 text-xs text-slate-200 focus:ring-1 focus:ring-brand-500" @click.stop>
                                    <button type="submit" class="text-xs text-brand-400 font-medium" @click.stop>Salvar</button>
                                </form>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click.stop="editing = !editing" class="text-xs text-slate-500 hover:text-slate-200">Editar</button>
                                <button type="button" @click.stop="deleteFolder('{{ route('folders.destroy', $folder) }}')" class="text-xs text-rose-500 hover:text-rose-400">Excluir</button>
                            </div>
                        </div>
                        
                        <div x-show="open" style="display: none;" class="p-3 border-t border-ink-800">
                            <form method="POST" @submit.prevent="uploadAttachment($event)" action="{{ route('attachments.store', $task) }}" class="mb-3 flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="folder_id" value="{{ $folder->id }}">
                                <input type="file" name="files[]" multiple required class="text-[11px] text-slate-400 file:mr-2 file:rounded file:border-0 file:bg-ink-800 file:px-2 file:py-1 file:text-[11px] file:text-slate-200 hover:file:bg-slate-600">
                                <button type="submit" class="rounded bg-ink-800 px-2 py-1 text-[11px] font-medium text-slate-200 hover:bg-slate-600" :disabled="uploading">Enviar</button>
                            </form>
                            
                            <div class="grid grid-cols-2 gap-3">
                                @foreach($folder->attachments as $att)
                                    @include('tasks.partials.attachment-card', ['att' => $att])
                                @endforeach
                            </div>
                            @if($folder->attachments->isEmpty())
                                <p class="text-xs text-slate-500 italic">Pasta vazia</p>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Arquivos Soltos (Raiz) --}}
                @php $rootAttachments = $task->attachments->whereNull('folder_id'); @endphp
                <div>
                    @if($task->folders->isNotEmpty())
                        <h4 class="text-xs font-semibold text-slate-500 uppercase mb-2">Arquivos Soltos</h4>
                    @endif
                    <form method="POST" @submit.prevent="uploadAttachment($event)" action="{{ route('attachments.store', $task) }}" class="mb-3 flex items-center gap-2">
                        @csrf
                        <input type="file" name="files[]" multiple required class="text-xs text-slate-400 file:mr-2 file:rounded file:border-0 file:bg-ink-800 file:px-3 file:py-1.5 file:text-xs file:text-slate-200 hover:file:bg-slate-600">
                        <button type="submit" class="rounded bg-ink-800 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-600" :disabled="uploading">
                            <span x-show="!uploading">Enviar</span>
                            <span x-show="uploading">Enviando...</span>
                        </button>
                    </form>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach($rootAttachments as $att)
                            @include('tasks.partials.attachment-card', ['att' => $att])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-6 border-ink-800">


        {{-- Checklist --}}
        {{-- Estilo Trello: linha limpa com prazo e foto do responsavel a direita,
             texto que vira editor ao clicar, "adicionar" que so pergunta o nome.
             Responsavel e prazo entram depois, pela propria linha. A logica
             (checklistDoCard) mora no layout porque este partial chega por AJAX. --}}
        @php
            $membrosDoChecklist = $members->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'initials' => $m->initials(),
                'color' => $m->avatar_color ?? '#6366f1',
                'avatar_url' => $m->avatar_url,
            ])->values();
        @endphp
        <div class="mb-6" x-data="checklistDoCard(@js([
            'items' => $task->items ?? [],
            'membros' => $membrosDoChecklist,
            'urlCriar' => route('items.store', $task),
            'urlItens' => url('/items'),
            'hoje' => now()->format('Y-m-d'),
        ]))">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-slate-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Checklist
                </h3>
                <span class="text-xs font-semibold text-slate-400" x-text="progress + '%'"></span>
            </div>

            <div class="h-1.5 w-full bg-ink-800 rounded-full mb-3 overflow-hidden" x-show="items.length > 0">
                <div class="h-full rounded-full transition-all duration-500" :class="progress === 100 ? 'bg-emerald-500' : 'bg-brand-500'" :style="'width: ' + progress + '%'"></div>
            </div>

            <div class="space-y-0.5">
                <template x-for="item in items" :key="item.id">
                    <div class="group relative -mx-2 rounded-lg px-2 py-1 transition"
                         :class="editandoId === item.id ? 'bg-ink-800/70' : 'hover:bg-ink-800/50'">
                        <div class="flex items-start gap-2.5">
                            <input type="checkbox" :checked="item.is_completed" @change="alternar(item)"
                                   class="mt-1.5 h-4 w-4 shrink-0 cursor-pointer rounded border-ink-500 bg-ink-900 text-brand-500 focus:ring-brand-500 focus:ring-offset-ink-900">

                            {{-- Leitura --}}
                            <template x-if="editandoId !== item.id">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <span class="min-w-0 flex-1 cursor-text break-words py-1 text-sm leading-snug"
                                          :class="item.is_completed ? 'text-slate-500 line-through' : 'text-slate-200'"
                                          x-text="item.description" @click="editar(item)"></span>

                                    <div class="flex shrink-0 items-center gap-1">
                                        {{-- Prazo --}}
                                        <button type="button" data-abre-popover @click.stop="abrirPopover('data', item)"
                                                class="inline-flex h-6 items-center gap-1 rounded-full text-[11px] font-medium transition"
                                                :class="item.due_date
                                                    ? (item.is_completed ? 'bg-ink-700/60 px-2 text-slate-500'
                                                        : (atrasado(item) ? 'bg-rose-500/15 px-2 text-rose-300'
                                                        : (paraHoje(item) ? 'bg-amber-500/15 px-2 text-amber-300' : 'bg-ink-700 px-2 text-slate-300')))
                                                    : 'w-6 justify-center text-slate-500 opacity-0 hover:bg-ink-700 hover:text-slate-200 group-hover:opacity-100'"
                                                :title="item.due_date ? 'Prazo: ' + dataCurta(item.due_date) : 'Definir prazo'">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-show="item.due_date" x-text="dataCurta(item.due_date)"></span>
                                        </button>

                                        {{-- Responsavel --}}
                                        <button type="button" data-abre-popover @click.stop="abrirPopover('membro', item)"
                                                class="grid h-6 w-6 place-items-center rounded-full transition"
                                                :class="item.assignee_id ? '' : 'text-slate-500 opacity-0 hover:bg-ink-700 hover:text-slate-200 group-hover:opacity-100'"
                                                :title="membro(item.assignee_id) ? membro(item.assignee_id).name : 'Definir responsável'">
                                            <template x-if="membro(item.assignee_id) && membro(item.assignee_id).avatar_url">
                                                <img :src="membro(item.assignee_id).avatar_url" :alt="membro(item.assignee_id).name" class="h-6 w-6 rounded-full object-cover ring-2 ring-ink-800">
                                            </template>
                                            <template x-if="membro(item.assignee_id) && !membro(item.assignee_id).avatar_url">
                                                <span class="grid h-6 w-6 place-items-center rounded-full text-[10px] font-semibold text-slate-100 ring-2 ring-ink-800"
                                                      :style="'background:' + membro(item.assignee_id).color" x-text="membro(item.assignee_id).initials"></span>
                                            </template>
                                            <template x-if="!membro(item.assignee_id)">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                            </template>
                                        </button>

                                        {{-- Menu --}}
                                        <button type="button" data-abre-popover @click.stop="abrirPopover('menu', item)"
                                                class="grid h-6 w-6 place-items-center rounded-full text-slate-500 transition hover:bg-ink-700 hover:text-slate-200"
                                                :class="popoverAberto('menu', item) ? 'bg-ink-700 text-slate-200' : 'opacity-0 group-hover:opacity-100'" title="Mais opções">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Edicao --}}
                            <template x-if="editandoId === item.id">
                                <div class="min-w-0 flex-1">
                                    <textarea :data-edicao="item.id" x-model="textoEdicao" rows="2"
                                              @keydown.enter.prevent="salvarEdicao(item)" @keydown.escape.prevent="cancelarEdicao()"
                                              class="w-full resize-none rounded-lg border border-brand-500 bg-ink-900 px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <button type="button" @click="salvarEdicao(item)" class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-500">Salvar</button>
                                        <button type="button" @click="cancelarEdicao()" class="rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-400 hover:bg-ink-700 hover:text-slate-200">Cancelar</button>
                                        <span class="flex-1"></span>
                                        <button type="button" data-abre-popover @click.stop="abrirPopover('membro', item)"
                                                class="inline-flex h-7 max-w-[180px] items-center gap-1.5 rounded-md px-2 text-xs text-slate-300 transition hover:bg-ink-700 hover:text-slate-100">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                            <span class="truncate" x-text="membro(item.assignee_id) ? membro(item.assignee_id).name : 'Responsável'"></span>
                                        </button>
                                        <button type="button" data-abre-popover @click.stop="abrirPopover('data', item)"
                                                class="inline-flex h-7 items-center gap-1.5 rounded-md px-2 text-xs text-slate-300 transition hover:bg-ink-700 hover:text-slate-100">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span x-text="item.due_date ? dataCurta(item.due_date) : 'Prazo'"></span>
                                        </button>
                                        <button type="button" data-abre-popover @click.stop="abrirPopover('menu', item)"
                                                class="grid h-7 w-7 place-items-center rounded-md text-slate-400 transition hover:bg-ink-700 hover:text-slate-100" title="Mais opções">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Popover: responsavel --}}
                        <template x-if="popoverAberto('membro', item)">
                            <div class="absolute right-2 top-full z-30 mt-1 w-60 rounded-xl border border-ink-600 bg-ink-800 p-2 shadow-2xl" @click.outside="cliqueFora($event)">
                                <input type="text" x-model="filtroMembro" data-foco-popover placeholder="Buscar pessoa…" @keydown.escape.prevent="fecharPopover()"
                                       class="mb-1.5 w-full rounded-lg border border-ink-700 bg-ink-900 px-2.5 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:border-brand-500 focus:outline-none">
                                <div class="max-h-56 space-y-0.5 overflow-y-auto">
                                    <template x-for="m in membrosFiltrados" :key="m.id">
                                        <button type="button" @click="definirMembro(item, m.id)"
                                                class="flex w-full items-center gap-2.5 rounded-lg px-2 py-1.5 text-left transition hover:bg-ink-700"
                                                :class="item.assignee_id === m.id ? 'bg-ink-700/70' : ''">
                                            <template x-if="m.avatar_url">
                                                <img :src="m.avatar_url" :alt="m.name" class="h-7 w-7 shrink-0 rounded-full object-cover ring-2 ring-ink-800">
                                            </template>
                                            <template x-if="!m.avatar_url">
                                                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-[11px] font-semibold text-slate-100 ring-2 ring-ink-800" :style="'background:' + m.color" x-text="m.initials"></span>
                                            </template>
                                            <span class="min-w-0 flex-1 truncate text-sm text-slate-200" x-text="m.name"></span>
                                            <svg x-show="item.assignee_id === m.id" class="h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                    <p x-show="membrosFiltrados.length === 0" class="px-2 py-2 text-xs text-slate-500">Ninguém com esse nome.</p>
                                </div>
                                <button x-show="item.assignee_id" type="button" @click="definirMembro(item, null)"
                                        class="mt-1.5 w-full rounded-lg border border-ink-700 px-2 py-1.5 text-xs text-slate-400 transition hover:bg-ink-700 hover:text-slate-200">Remover responsável</button>
                            </div>
                        </template>

                        {{-- Popover: prazo --}}
                        <template x-if="popoverAberto('data', item)">
                            <div class="absolute right-2 top-full z-30 mt-1 w-60 rounded-xl border border-ink-600 bg-ink-800 p-3 shadow-2xl" @click.outside="cliqueFora($event)">
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Prazo do item</label>
                                <input type="date" data-foco-popover :value="item.due_date || ''" @change="definirData(item, $event.target.value)" @keydown.escape.prevent="fecharPopover()"
                                       class="w-full rounded-lg border border-ink-700 bg-ink-900 px-2.5 py-1.5 text-sm text-slate-200 focus:border-brand-500 focus:outline-none [color-scheme:dark]">
                                <div class="mt-2 flex items-center gap-1.5">
                                    <button type="button" @click="definirData(item, hoje)" class="rounded-full bg-ink-700 px-2.5 py-1 text-[11px] font-medium text-slate-300 transition hover:bg-ink-600 hover:text-slate-100">Hoje</button>
                                    <button type="button" @click="definirData(item, amanha)" class="rounded-full bg-ink-700 px-2.5 py-1 text-[11px] font-medium text-slate-300 transition hover:bg-ink-600 hover:text-slate-100">Amanhã</button>
                                    <span class="flex-1"></span>
                                    <button x-show="item.due_date" type="button" @click="definirData(item, null)" class="text-[11px] font-medium text-rose-400 transition hover:text-rose-300">Remover</button>
                                </div>
                            </div>
                        </template>

                        {{-- Popover: menu --}}
                        <template x-if="popoverAberto('menu', item)">
                            <div class="absolute right-2 top-full z-30 mt-1 w-44 rounded-xl border border-ink-600 bg-ink-800 p-1 shadow-2xl" @click.outside="cliqueFora($event)">
                                <button type="button" @click="editar(item)" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-sm text-slate-200 transition hover:bg-ink-700">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Editar texto
                                </button>
                                <button type="button" @click="excluir(item)" class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-sm text-rose-400 transition hover:bg-rose-500/10">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Excluir item
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Compositor: so pede o nome; responsavel e prazo vem depois, pela linha --}}
            <div class="mt-2 pl-[26px]">
                <template x-if="!compondo">
                    <button type="button" @click="abrirCompositor()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-ink-800 px-3 py-1.5 text-xs font-medium text-slate-300 transition hover:bg-ink-700 hover:text-slate-100">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
                        Adicionar um item
                    </button>
                </template>
                <template x-if="compondo">
                    <div @click.outside="if (!novoTexto.trim()) fecharCompositor()">
                        <textarea data-novo-item x-model="novoTexto" rows="2" placeholder="Adicionar um item…"
                                  @keydown.enter.prevent="adicionar()" @keydown.escape.prevent="fecharCompositor()"
                                  class="w-full resize-none rounded-lg border border-brand-500 bg-ink-900 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                        <div class="mt-1.5 flex items-center gap-1.5">
                            <button type="button" @click="adicionar()" :disabled="salvando || !novoTexto.trim()"
                                    class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-500 disabled:cursor-not-allowed disabled:opacity-50">Salvar</button>
                            <button type="button" @click="fecharCompositor()" class="rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-400 transition hover:bg-ink-700 hover:text-slate-200">Cancelar</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <hr class="my-6 border-ink-800">

        {{-- Comentários (Simples) --}}
        <div>
            <h3 class="mb-4 font-semibold text-slate-200">Comentários</h3>
            
            <form @submit.prevent="postComment($event)" action="{{ route('comments.store', $task) }}" class="flex gap-3 mb-6">
                @csrf
                <div class="flex-1">
                    <textarea name="body" required rows="2" placeholder="Fazer uma pergunta ou atualização..."
                              class="w-full rounded-lg border border-ink-800 bg-ink-800 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none"></textarea>
                    <div class="mt-2 text-right">
                        <button type="submit" class="rounded bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-brand-500" :disabled="commenting">Comentar</button>
                    </div>
                </div>
            </form>

            <div class="space-y-4" id="comments-container">
                @foreach($task->comments->sortByDesc('created_at') as $comment)
                    <div class="flex gap-3 @if($comment->is_from_client) rounded-lg border border-amber-500/25 bg-amber-500/[0.06] p-3 @endif"
                         x-data="{ editing: false, body: {{ json_encode($comment->body) }}, visivel: {{ $comment->visible_to_client ? 'true' : 'false' }} }">
                        <div class="h-8 w-8 shrink-0 rounded-full flex items-center justify-center font-bold text-xs
                                    @if($comment->is_from_client) bg-amber-500/20 text-amber-300 @else bg-ink-800 @endif">
                            {{ mb_substr($comment->authorName(), 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-slate-200">{{ $comment->authorName() }}</span>
                                    @if($comment->is_from_client)
                                        <span class="rounded-full bg-amber-500/15 px-1.5 py-0.5 text-[9.5px] font-bold uppercase tracking-wide text-amber-400">Cliente</span>
                                    @elseif($comment->visible_to_client)
                                        <span class="rounded-full bg-emerald-500/15 px-1.5 py-0.5 text-[9.5px] font-bold uppercase tracking-wide text-emerald-400">Visível ao cliente</span>
                                    @endif
                                    <span class="text-[10px] text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                @if(! $comment->is_from_client && ($comment->user_id === auth()->id() || auth()->user()->isManager()))
                                    <div class="flex items-center gap-2">
                                        {{-- Deixa este comentário aparecer no painel do cliente. --}}
                                        <button type="button"
                                                @click="toggleCommentVisibility('{{ route('comments.visibility', $comment) }}', $el)"
                                                class="text-xs text-slate-500 hover:text-emerald-400"
                                                x-text="visivel ? 'Ocultar do cliente' : 'Responder ao cliente'"></button>
                                        @if($comment->user_id === auth()->id())
                                            <button type="button" @click="editing = !editing" class="text-xs text-slate-500 hover:text-brand-400">Editar</button>
                                        @endif
                                        <button type="button" @click="deleteComment('{{ route('comments.destroy', $comment) }}', $el.closest('.flex.gap-3'))" class="text-xs text-slate-500 hover:text-rose-400">Excluir</button>
                                    </div>
                                @endif
                            </div>
                            
                            <div x-show="!editing" class="mt-1 text-sm text-slate-300 whitespace-pre-wrap" x-text="body"></div>
                            
                            <div x-show="editing" style="display: none;" class="mt-2">
                                <form @submit.prevent="updateComment('{{ route('comments.update', $comment) }}', $event, body); editing = false" class="flex flex-col gap-2">
                                    <textarea x-model="body" required rows="2" class="w-full rounded-lg border border-ink-800 bg-ink-800 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none"></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="editing = false; body = {{ json_encode($comment->body) }}" class="text-xs text-slate-400 hover:text-slate-200">Cancelar</button>
                                        <button type="submit" class="rounded bg-brand-600 px-3 py-1 text-xs font-semibold text-white hover:bg-brand-500">Salvar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <hr class="my-6 border-ink-800">

        {{-- Histórico do card --}}
        @include('tasks.partials.activity', ['task' => $task])

        @else
        <div class="mt-6 flex items-center justify-center rounded-lg border border-dashed border-ink-800 p-8">
            <p class="text-center text-sm text-slate-500">A tarefa será salva automaticamente ao digitar o título. Os anexos serão liberados após o salvamento.</p>
        </div>
        @endif
    </div>
</div>


