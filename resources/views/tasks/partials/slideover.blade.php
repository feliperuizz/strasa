<div x-data="taskForm(
        '{{ $task->exists ? route('tasks.update', $task) : route('tasks.store', $project) }}',
        '{{ $task->exists ? 'PATCH' : 'POST' }}',
        {{ $task->exists ? 'true' : 'false' }}
    )" 
    class="flex h-full flex-col bg-[#1e1e1e] text-slate-200">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-[#2a2b2d] px-6 py-4">
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

            <button @click="closeModal()" class="rounded p-1 text-slate-400 hover:bg-[#2a2b2d] hover:text-white" title="Fechar">
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

            <div class="space-y-6">
                {{-- Título --}}
                <div>
                    <input type="text" name="title" value="{{ $task->title }}" placeholder="Escreva o nome da tarefa"
                           @input="updateTitle($event)"
                           class="w-full border-0 bg-transparent p-0 text-2xl font-bold text-white focus:ring-0 placeholder:text-slate-500">
                </div>

                {{-- Metadados rápidos --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Responsável</label>
                        <select name="assignee_id" @change="save()" class="w-full rounded border-0 bg-[#2a2b2d] py-1.5 pl-3 text-sm text-white focus:ring-1 focus:ring-brand-500">
                            <option value="">Sem responsável</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" {{ $task->assignee_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Data de Publicação</label>
                        <input type="date" name="publish_date" value="{{ optional($task->publish_date)->format('Y-m-d') }}" @change="save()"
                               class="w-full rounded border-0 bg-[#2a2b2d] py-1.5 pl-3 text-sm text-white focus:ring-1 focus:ring-brand-500 [color-scheme:dark]">
                    </div>
                </div>

                {{-- Tags / Flags --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Flags / Tags</label>
                    <div class="flex flex-wrap items-center gap-2 mb-2" id="tags-container">
                        @foreach($task->tags as $tag)
                            <div class="inline-flex items-center gap-1 rounded bg-[#2a2b2d] px-2 py-1 text-xs text-slate-300 border border-ink-600">
                                <span class="w-2 h-2 rounded-full" style="background: {{ $tag->color }}"></span>
                                {{ $tag->name }}
                                <input type="hidden" name="tags[]" value="{{ $tag->name }}|{{ $tag->color }}">
                                <button type="button" onclick="this.parentElement.remove(); document.getElementById('task-auto-form').dispatchEvent(new Event('change', {bubbles: true}))" class="text-slate-500 hover:text-rose-400 ml-1">&times;</button>
                            </div>
                        @endforeach
                    </div>
                    
                    <div x-data="{ open: false, tagText: '', tagColor: '#ef4444' }" class="relative">
                        <button type="button" @click="open = !open" class="text-xs text-brand-400 hover:text-brand-300 transition font-medium">＋ Adicionar flag</button>
                        
                        <div x-show="open" @click.outside="open = false" style="display: none;" class="absolute left-0 mt-2 w-56 rounded-lg border border-[#2a2b2d] bg-[#1e1e1e] p-3 shadow-xl z-50">
                            <input type="text" x-model="tagText" placeholder="Nome da flag..." @keydown.enter.prevent="$refs.addBtn.click()" class="w-full rounded bg-[#2a2b2d] px-2 py-1.5 text-xs border border-[#2a2b2d] focus:border-brand-500 focus:ring-0 mb-3 text-white">
                            
                            <div class="flex flex-wrap gap-1.5 mb-3">
                                <template x-for="color in ['#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#a855f7', '#ec4899', '#94a3b8']">
                                    <button type="button" @click="tagColor = color" :class="tagColor === color ? 'ring-2 ring-white scale-110' : ''" class="w-5 h-5 rounded-full transition-transform" :style="'background: ' + color"></button>
                                </template>
                            </div>
                            
                            <button type="button" x-ref="addBtn" @click="
                                if(tagText.trim() === '') return;
                                const div = document.createElement('div');
                                div.className = 'inline-flex items-center gap-1 rounded bg-[#2a2b2d] px-2 py-1 text-xs text-slate-300 border border-ink-600';
                                div.innerHTML = `
                                    <span class=\'w-2 h-2 rounded-full\' style=\'background: ${tagColor}\'></span>
                                    ${tagText}
                                    <input type=\'hidden\' name=\'tags[]\' value=\'${tagText}|${tagColor}\'>
                                    <button type=\'button\' onclick=\'this.parentElement.remove(); document.getElementById(\`task-auto-form\`).dispatchEvent(new Event(\`change\`, {bubbles: true}))\' class=\'text-slate-500 hover:text-rose-400 ml-1\'>&times;</button>
                                `;
                                document.getElementById('tags-container').appendChild(div);
                                open = false;
                                tagText = '';
                                document.getElementById('task-auto-form').dispatchEvent(new Event('change', {bubbles: true}));
                            " class="w-full rounded bg-brand-600 py-1.5 text-xs font-semibold text-white hover:bg-brand-500">Adicionar</button>
                        </div>
                    </div>
                </div>

                {{-- Descrição --}}
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 mb-2">Descrição</label>
                    <textarea name="description" rows="4" placeholder="O que é essa tarefa?" @change="save()"
                              class="w-full rounded border border-[#2a2b2d] bg-[#2a2b2d] p-3 text-sm text-white focus:border-brand-500 focus:ring-0">{{ $task->description }}</textarea>
                </div>
            </div>
        </form>

        @if($task->exists)
        <hr class="my-6 border-[#2a2b2d]">

        {{-- Comentários (Simples) --}}
        <div>
            <h3 class="mb-4 font-semibold text-white">Comentários</h3>
            
            <form @submit.prevent="postComment($event)" action="{{ route('comments.store', $task) }}" class="flex gap-3 mb-6">
                @csrf
                <div class="flex-1">
                    <textarea name="body" required rows="2" placeholder="Fazer uma pergunta ou atualização..."
                              class="w-full rounded-lg border border-[#2a2b2d] bg-[#2a2b2d] px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none"></textarea>
                    <div class="mt-2 text-right">
                        <button type="submit" class="rounded bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-brand-500" :disabled="commenting">Comentar</button>
                    </div>
                </div>
            </form>

            <div class="space-y-4" id="comments-container">
                @foreach($task->comments->sortByDesc('created_at') as $comment)
                    <div class="flex gap-3" x-data="{ editing: false, body: {{ json_encode($comment->body) }} }">
                        <div class="h-8 w-8 shrink-0 rounded-full bg-[#2a2b2d] flex items-center justify-center font-bold text-xs">{{ substr($comment->user->name, 0, 1) }}</div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-slate-200">{{ $comment->user->name }}</span>
                                    <span class="text-[10px] text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                @if($comment->user_id === auth()->id() || auth()->user()->isManager())
                                    <div class="flex items-center gap-2">
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
                                    <textarea x-model="body" required rows="2" class="w-full rounded-lg border border-[#2a2b2d] bg-[#2a2b2d] px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none"></textarea>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="editing = false; body = {{ json_encode($comment->body) }}" class="text-xs text-slate-400 hover:text-white">Cancelar</button>
                                        <button type="submit" class="rounded bg-brand-600 px-3 py-1 text-xs font-semibold text-white hover:bg-brand-500">Salvar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <hr class="my-6 border-[#2a2b2d]">

        {{-- Anexos --}}
        <div>
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-white">Anexos</h3>
            </div>
            
            {{-- Form AJAX de upload --}}
            <form @submit.prevent="uploadAttachment($event)" action="{{ route('attachments.store', $task) }}" class="mb-4 flex items-center gap-2">
                @csrf
                <input type="file" name="files[]" multiple required class="text-sm text-slate-400 file:mr-2 file:rounded file:border-0 file:bg-[#2a2b2d] file:px-3 file:py-1.5 file:text-xs file:text-white hover:file:bg-slate-600">
                <button type="submit" class="rounded bg-[#2a2b2d] px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-600" :disabled="uploading">
                    <span x-show="!uploading">Enviar</span>
                    <span x-show="uploading">Enviando...</span>
                </button>
            </form>

            <div class="grid grid-cols-2 gap-3" id="attachments-container" x-init="initTaskViewer($el)">
                @foreach($task->attachments as $att)
                    <div class="group relative flex items-center gap-3 rounded-lg border border-[#2a2b2d] bg-[#2a2b2d] p-2">
                        @if($att->is_image)
                            <img src="{{ Storage::disk($att->disk)->url($att->path) }}" data-url="{{ Storage::disk($att->disk)->url($att->path) }}" data-download-url="{{ route('attachments.download', $att) }}" class="h-10 w-10 rounded object-cover viewer-image cursor-pointer" alt="{{ $att->original_name }}">
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded bg-[#1e1e1e]">
                                <span class="text-[10px] font-bold text-slate-500">{{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION)) }}</span>
                            </div>
                        @endif
                        <div class="flex-1 overflow-hidden">
                            @if($att->is_image)
                                <span class="cursor-pointer truncate text-xs font-medium text-slate-200 hover:text-white hover:underline block" onclick="this.closest('.group').querySelector('img').click()">{{ $att->original_name }}</span>
                            @else
                                <a href="{{ Storage::disk($att->disk)->url($att->path) }}" target="_blank" class="truncate text-xs font-medium text-slate-200 hover:text-white hover:underline block">{{ $att->original_name }}</a>
                            @endif
                        </div>
                        <button type="button" @click="deleteAttachment('{{ route('attachments.destroy', $att) }}', $event.target.closest('.group'))" class="absolute -right-2 -top-2 hidden rounded-full bg-rose-600 p-1 text-white hover:bg-rose-500 group-hover:block" title="Excluir">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="mt-6 flex items-center justify-center rounded-lg border border-dashed border-[#2a2b2d] p-8">
            <p class="text-center text-sm text-slate-500">A tarefa será salva automaticamente ao digitar o título. Os anexos serão liberados após o salvamento.</p>
        </div>
        @endif
    </div>
</div>


