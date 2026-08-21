<x-app-layout title="Editar Tarefa">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('tasks.show', $task) }}" class="text-sm text-slate-400 hover:text-slate-200">{{ $task->title }}</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-slate-200">Editar Tarefa</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-4xl mx-auto">
        <form method="POST" action="{{ route('tasks.update', $task) }}" class="flex flex-col lg:flex-row gap-6">
            @csrf @method('PATCH')
            
            <div class="flex-1 space-y-5">
                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <div class="space-y-4">
                        <div>
                            <input name="title" value="{{ old('title', $task->title) }}" required autofocus
                                   class="w-full bg-transparent px-0 py-2 text-xl font-semibold text-slate-200 placeholder-slate-500 border-0 border-b border-transparent focus:border-brand-500 focus:ring-0">
                            @error('title') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

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
                                    });
                                }
                            }" 
                            x-init="initQuill()">
                            <label class="mb-1 block text-sm font-medium text-slate-300">Descrição</label>
                            <input type="hidden" name="description" x-ref="hiddenInput" value="{{ old('description', $task->description) }}">
                            <div x-ref="editor" class="w-full rounded-lg bg-ink-900 text-slate-200 border border-ink-600">{!! old('description', $task->description) !!}</div>
                            @error('description') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-80 space-y-5 shrink-0">
                <div class="rounded-xl border border-ink-600 bg-ink-800 p-4 space-y-4">
                    
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-300">Coluna</label>
                        <select name="column_id" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                            @foreach($task->project->columns()->orderBy('position')->get() as $col)
                                <option value="{{ $col->id }}" {{ (int) old('column_id', $task->column_id) === $col->id ? 'selected' : '' }}>{{ $col->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-300">Data de publicação</label>
                        <input type="date" name="publish_date" value="{{ old('publish_date', $task->publish_date?->toDateString()) }}"
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none [color-scheme:dark]">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-300">Tipo de Conteúdo</label>
                        <select name="content_type" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none">
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\Task::CONTENT_TYPES as $key => $label)
                                <option value="{{ $key }}" {{ old('content_type', $task->content_type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Responsáveis</label>
                        <select name="assignees[]" multiple class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-slate-200 focus:border-brand-500 focus:outline-none h-32">
                            @foreach(auth()->user()->company->users as $u)
                                <option value="{{ $u->id }}" {{ $task->assignees->contains($u->id) ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Segure Ctrl (ou Cmd no Mac) para selecionar vários.</p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" class="rounded border-ink-600 bg-ink-900 text-emerald-500 focus:ring-emerald-500"
                                {{ old('is_published', $task->is_published) ? 'checked' : '' }}>
                            Marcar como Publicado
                        </label>
                    </div>

                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('tasks.show', $task) }}" class="flex-1 rounded-lg border border-ink-600 bg-ink-800 py-2 text-center text-sm font-medium text-slate-300 hover:bg-ink-700">Cancelar</a>
                    <button type="submit" class="flex-1 rounded-lg bg-brand-600 py-2 text-center text-sm font-medium text-white hover:bg-brand-500">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
