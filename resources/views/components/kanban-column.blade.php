@props(['column', 'tasks', 'project'])

<div class="flex w-[280px] shrink-0 flex-col bg-ink-800/80 backdrop-blur-md rounded-xl p-2 h-full border border-white/5 shadow-md" data-column="{{ $column->id }}">
    {{-- Cabeçalho da coluna --}}
    <div class="column-drag-handle cursor-grab group flex items-center justify-between px-1 py-1 mb-2" x-data="{ menu:false }">
        <div class="flex items-center gap-2">
            <span class="text-[13px] font-semibold text-slate-300 uppercase tracking-wide">{{ $column->name }}</span>
            <span class="column-count text-[13px] text-slate-500 font-medium">{{ $tasks->count() }}</span>
        </div>
        <div class="relative flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="window.quickCreateTask({{ $project->id }}, {{ $column->id }})"
               class="rounded p-1 text-slate-500 hover:bg-ink-700 hover:text-white" title="Novo card">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>
            @can('update', $project)
                <button @click="menu=!menu" class="rounded p-1 text-slate-500 hover:bg-ink-700 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>
                </button>
                <div x-show="menu" x-cloak @click.outside="menu=false"
                     class="absolute right-0 top-8 z-20 w-52 rounded-lg border border-ink-600 bg-ink-800 p-3 shadow-xl">
                    <form method="POST" action="{{ route('columns.update', $column) }}" class="space-y-2">
                        @csrf @method('PATCH')
                        <input name="name" value="{{ $column->name }}" class="w-full rounded bg-ink-900 px-2 py-1 text-sm border border-ink-600" />
                        <input name="color" type="color" value="{{ $column->color }}" class="h-7 w-full rounded bg-ink-900 border border-ink-600" />
                        
                        <label class="flex items-center gap-2 mt-2">
                            <input type="hidden" name="is_publish_column" value="0">
                            <input type="checkbox" name="is_publish_column" value="1" {{ $column->is_publish_column ? 'checked' : '' }} class="rounded border-ink-600 bg-ink-900 text-brand-500 focus:ring-brand-500 focus:ring-offset-ink-800">
                            <span class="text-[11px] leading-tight text-slate-400">Notificar Postagens desta coluna hoje</span>
                        </label>

                        <button class="w-full rounded bg-brand-600 py-1 text-xs font-medium text-white hover:bg-brand-500 mt-2">Salvar</button>
                    </form>
                    <form method="POST" action="{{ route('columns.destroy', $column) }}"
                          onsubmit="return confirm('Remover esta coluna?')" class="mt-2">
                        @csrf @method('DELETE')
                        <button class="w-full rounded border border-rose-700/50 py-1 text-xs text-rose-400 hover:bg-rose-900/30">Remover coluna</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

    {{-- Lista de cards (alvo do drag & drop) --}}
    <div class="kanban-list flex-1 space-y-2.5 overflow-y-auto pb-1" data-column-id="{{ $column->id }}">
        @foreach($tasks as $task)
            <x-task-card :task="$task" />
        @endforeach
    </div>

    {{-- Botão Adicionar Tarefa embaixo --}}
    <div class="mt-2">
        <button type="button" @click="window.quickCreateTask({{ $project->id }}, {{ $column->id }})" class="flex w-full items-center gap-2 text-[13px] text-slate-400 hover:text-slate-200 transition py-1 px-1">
            <span class="text-lg leading-none mb-0.5">+</span> Adicionar uma tarefa
        </button>
    </div>
</div>
