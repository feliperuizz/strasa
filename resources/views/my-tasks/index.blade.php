<x-app-layout title="Minhas Tarefas">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-bold text-white tracking-wide">Minhas Tarefas</h1>
        </div>
    </x-slot>

    <div class="flex h-[calc(100vh-4rem)] flex-col">

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
                            <tr class="hover:bg-ink-700/50 cursor-pointer group">
                                <td class="px-4 py-3" @click.stop>
                                    <button type="button" onclick="window.completeTask(this, {{ $task->id }}, event)" class="mt-0.5 text-slate-500 hover:text-emerald-400 focus:outline-none transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-200">{{ $task->title }}</td>
                                <td class="px-4 py-3 text-slate-400">
                                    <a href="{{ route('projects.board', $task->project_id) }}" class="hover:text-white" @click.stop>
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
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Você não tem nenhuma tarefa atribuída a você no momento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>

    @endpush
</x-app-layout>
