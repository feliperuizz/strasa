<x-app-layout title="Visão geral">
    <x-slot name="header">
        <h1 class="text-xl font-bold text-white tracking-wide">Resumo de Produtividade</h1>
        <p class="text-sm text-slate-400 mt-1">Acompanhe o ritmo de entregas do seu time.</p>
    </x-slot>

    <div class="p-4 sm:p-6 space-y-6">
        {{-- Cartões de métricas --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach([
                ['Atrasados', $stats['late'], '#ef4444', 'text-rose-400', 'bg-rose-900/20'],
                ['Projetos', $stats['projects'], '#0ea5e9', 'text-sky-400', 'bg-sky-900/20'],
                ['Total de Cards', $stats['tasks'], '#eab308', 'text-amber-400', 'bg-amber-900/20'],
                ['Publicados', $stats['published'], '#22c55e', 'text-emerald-400', 'bg-emerald-900/20'],
            ] as [$label, $value, $color, $textColor, $bgColor])
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-2">{{ $label }}</div>
                        <div class="text-3xl font-bold {{ $textColor }}">{{ $value }}</div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 {{ $bgColor }}" style="background-color: {{ $color }}33"></div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Gráfico de Produtividade --}}
            <section class="lg:col-span-2 rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm">
                <header class="mb-4">
                    <h2 class="text-base font-semibold text-white">Produtividade Semanal</h2>
                    <p class="text-xs text-slate-400">Cards finalizados por dia</p>
                </header>
                <div class="relative h-64 w-full">
                    <canvas id="productivityChart"></canvas>
                </div>
            </section>

            <div class="space-y-6 lg:col-span-1">
                {{-- Minhas tarefas --}}
                <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                    <header class="border-b border-ink-600 px-5 py-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-white">Minhas Tarefas Pendentes</h2>
                        <span class="bg-brand-500/20 text-brand-400 text-xs font-bold px-2 py-0.5 rounded-full">{{ $myTasks->count() }}</span>
                    </header>
                    <ul class="divide-y divide-ink-700/50">
                        @forelse($myTasks as $task)
                            <li class="flex items-center gap-3 px-5 py-3.5 hover:bg-ink-800/50 transition">
                                <span class="h-2.5 w-2.5 rounded-full ring-2 ring-ink-800" style="background: {{ $task->column->color }}"></span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('tasks.show', $task) }}" class="block truncate text-sm font-medium text-slate-200 hover:text-brand-400 transition">{{ $task->title }}</a>
                                    <div class="truncate text-xs text-slate-500 mt-0.5">{{ $task->client->name }} · {{ $task->project->name }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-slate-500 italic">Tudo limpo por aqui! 🎉</li>
                        @endforelse
                    </ul>
                </section>
                
                {{-- Próximos posts --}}
                <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                    <header class="border-b border-ink-600 px-5 py-4">
                        <h2 class="text-sm font-semibold text-white">Próximos Posts</h2>
                    </header>
                    <ul class="divide-y divide-ink-700/50">
                        @forelse($upcoming as $task)
                            <li class="flex items-center gap-4 px-5 py-3.5 hover:bg-ink-800/50 transition">
                                <div class="flex flex-col items-center justify-center h-11 w-11 shrink-0 rounded-lg bg-ink-700/50 border border-ink-600">
                                    <span class="text-xs font-bold text-slate-300">{{ $task->publish_date->format('d') }}</span>
                                    <span class="text-[9px] uppercase tracking-wider text-slate-500">{{ $task->publish_date->format('M') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('tasks.show', $task) }}" class="block truncate text-sm font-medium text-slate-200 hover:text-brand-400 transition">{{ $task->title }}</a>
                                    <div class="truncate text-xs text-slate-500 mt-0.5">{{ $task->client->name }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-slate-500 italic">Nenhum post agendado.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('productivityChart').getContext('2d');
            const chartData = @json($chartData);
            
            // Gradiente
            let gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(16, 185, 129, 0.5)'); // emerald-500
            gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Cards Concluídos',
                        data: chartData.data,
                        borderColor: '#10b981', // emerald-500
                        backgroundColor: gradient,
                        borderWidth: 3,
                        pointBackgroundColor: '#2a2b2d',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4 // Curva suave
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e1e1e',
                            titleColor: '#94a3b8',
                            bodyColor: '#fff',
                            borderColor: '#343b4a',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#64748b' },
                            grid: { color: '#343b4a', drawBorder: false }
                        },
                        x: {
                            ticks: { color: '#64748b' },
                            grid: { display: false, drawBorder: false }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
