<x-app-layout title="Minhas Tarefas">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2" data-recarga-suave="minhas-tarefas-cabecalho">
            <div>
                <h1 class="text-xl font-bold text-slate-200 tracking-wide">Minhas Tarefas</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
                    O que ainda falta entregar. Tarefas concluídas saem da lista.
                </p>
            </div>
            @if($tasks->isNotEmpty())
                <span class="self-start rounded-full bg-ink-700 px-2.5 py-1 text-[12px] font-semibold text-slate-300">
                    {{ $tasks->count() }} {{ $tasks->count() === 1 ? 'pendente' : 'pendentes' }}
                </span>
            @endif
        </div>
    </x-slot>

    {{-- data-recarga-suave: fechar um card ou concluir pela bolinha atualiza
         so este miolo, sem recarregar a pagina (ver recargaSuave no layout). --}}
    <div class="flex h-full flex-col" data-recarga-suave="minhas-tarefas">

        <div class="flex-1 overflow-auto p-4 max-w-6xl mx-auto w-full">
            <div class="rounded-xl border border-ink-600 bg-ink-800 overflow-hidden">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="border-b border-ink-600 bg-ink-900/50 text-xs text-slate-400">
                        <tr>
                            <th class="px-4 py-2 font-medium w-8"></th>
                            <th class="px-4 py-2 font-medium">Tarefa</th>
                            <th class="px-4 py-2 font-medium">Projeto</th>
                            <th class="px-4 py-2 font-medium">Coluna</th>
                            <th class="px-4 py-2 font-medium">Data</th>
                            <th class="px-4 py-2 font-medium">Tags</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700">
                        @forelse($tasks as $task)
                            {{-- A linha inteira abre a tarefa no slideover. As celulas que
                                 tem acao propria (concluir, link do projeto) usam @click.stop
                                 para nao disparar isto junto. --}}
                            <tr class="hover:bg-ink-700/50 cursor-pointer group"
                                @click="$dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')">
                                <td class="px-4 py-3" @click.stop>
                                    <button type="button" onclick="window.completeTask(this, {{ $task->id }}, event)" class="mt-0.5 text-slate-500 hover:text-emerald-400 focus:outline-none transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-200">
                                    {{-- href real para ctrl+clique abrir em outra aba. Clique normal
                                         nao navega: deixa subir ate a linha, que abre o slideover. --}}
                                    <a href="{{ route('tasks.show', $task) }}"
                                       class="hover:text-brand-400 transition"
                                       @click="if ($event.ctrlKey || $event.metaKey) { $event.stopPropagation(); } else { $event.preventDefault(); }">
                                        {{ $task->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-400">
                                    <a href="{{ route('projects.board', $task->project_id) }}" class="hover:text-slate-200" @click.stop>
                                        {{ optional($task->project->client)->name }} - {{ optional($task->project)->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-400">
                                    <span class="rounded bg-ink-900 px-2 py-1 text-[10px] text-slate-300 border border-ink-600">{{ optional($task->column)->name }}</span>
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
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                    <span class="block text-2xl mb-2">🎉</span>
                                    Nada pendente por aqui.<br>
                                    <span class="text-[12.5px]">As tarefas que você concluiu continuam no quadro do projeto.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush
</x-app-layout>
