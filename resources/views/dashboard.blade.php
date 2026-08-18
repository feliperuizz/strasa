<x-app-layout title="Visão geral">
    <x-slot name="header">
        <h1 class="text-base font-semibold text-white">Visão geral</h1>
    </x-slot>

    <div class="p-4 sm:p-6 space-y-6">
        {{-- Cartões de métricas --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach([
                ['Clientes', $stats['clients'], '#6366f1'],
                ['Projetos', $stats['projects'], '#0ea5e9'],
                ['Tarefas', $stats['tasks'], '#eab308'],
                ['Publicados', $stats['published'], '#22c55e'],
            ] as [$label, $value, $color])
                <div class="rounded-xl border border-ink-600 bg-ink-800 p-4">
                    <div class="text-2xl font-bold text-white">{{ $value }}</div>
                    <div class="mt-1 flex items-center gap-2 text-sm text-slate-400">
                        <span class="h-2 w-2 rounded-full" style="background: {{ $color }}"></span>{{ $label }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Próximos posts --}}
            <section class="rounded-xl border border-ink-600 bg-ink-800">
                <header class="border-b border-ink-600 px-4 py-3 text-sm font-semibold text-white">Próximos posts</header>
                <ul class="divide-y divide-ink-700">
                    @forelse($upcoming as $task)
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-ink-700 text-xs text-slate-400">
                                {{ $task->publish_date->format('d/m') }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('tasks.show', $task) }}" class="block truncate text-sm text-slate-200 hover:text-brand-400">{{ $task->title }}</a>
                                <div class="truncate text-xs text-slate-500">{{ $task->client->name }} · {{ $task->project->name }}</div>
                            </div>

                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-slate-500">Nenhum post agendado.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Minhas tarefas --}}
            <section class="rounded-xl border border-ink-600 bg-ink-800">
                <header class="border-b border-ink-600 px-4 py-3 text-sm font-semibold text-white">Minhas tarefas</header>
                <ul class="divide-y divide-ink-700">
                    @forelse($myTasks as $task)
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="h-2 w-2 rounded-full" style="background: {{ $task->column->color }}"></span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('tasks.show', $task) }}" class="block truncate text-sm text-slate-200 hover:text-brand-400">{{ $task->title }}</a>
                                <div class="truncate text-xs text-slate-500">{{ $task->client->name }} · {{ $task->column->name }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-slate-500">Você não tem tarefas atribuídas.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
