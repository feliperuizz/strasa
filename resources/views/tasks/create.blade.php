<x-app-layout title="Nova Tarefa">
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('projects.board', $project) }}" class="text-sm text-slate-400 hover:text-white">{{ $project->name }}</a>
            <span class="text-slate-600">/</span>
            <h1 class="text-base font-semibold text-white">Nova Tarefa</h1>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 max-w-4xl mx-auto">
        <form method="POST" action="{{ route('tasks.store', $project) }}" class="flex flex-col lg:flex-row gap-6">
            @csrf
            
            <div class="flex-1 space-y-5">
                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <div class="space-y-4">
                        <div>
                            <input name="title" value="{{ old('title') }}" required autofocus placeholder="Título da tarefa..."
                                   class="w-full bg-transparent px-0 py-2 text-xl font-semibold text-white placeholder-slate-500 border-0 border-b border-transparent focus:border-brand-500 focus:ring-0">
                            @error('title') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-300">Descrição</label>
                            <textarea name="description" rows="8" placeholder="O que precisa ser feito..."
                                      class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">{{ old('description') }}</textarea>
                            @error('description') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-80 space-y-5 shrink-0">
                <div class="rounded-xl border border-ink-600 bg-ink-800 p-4 space-y-4">
                    
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-300">Coluna</label>
                        <select name="column_id" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                            @foreach($project->columns()->orderBy('position')->get() as $col)
                                <option value="{{ $col->id }}" {{ (int) old('column_id', request('column')) === $col->id ? 'selected' : '' }}>{{ $col->name }}</option>
                            @endforeach
                        </select>
                        @error('column_id') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-300">Data de publicação</label>
                        <input type="date" name="publish_date" value="{{ old('publish_date') }}"
                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none [color-scheme:dark]">
                        @error('publish_date') <p class="mt-1 text-xs text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-300">Tipo de Conteúdo</label>
                        <select name="content_type" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\Task::CONTENT_TYPES as $key => $label)
                                <option value="{{ $key }}" {{ old('content_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-300">Responsável</label>
                        <select name="assignee_id" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                            <option value="">Sem responsável</option>
                            @foreach(auth()->user()->company->users as $u)
                                <option value="{{ $u->id }}" {{ (int) old('assignee_id') === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('projects.board', $project) }}" class="flex-1 rounded-lg border border-ink-600 bg-ink-800 py-2 text-center text-sm font-medium text-slate-300 hover:bg-ink-700">Cancelar</a>
                    <button type="submit" class="flex-1 rounded-lg bg-brand-600 py-2 text-center text-sm font-medium text-white hover:bg-brand-500">Salvar Tarefa</button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
