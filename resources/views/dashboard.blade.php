<x-app-layout title="Início">
    <x-slot name="header">
        @if($isAdmin)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-wide">Painel de Gestão & Produtividade</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-0.5">Acompanhe a evolução da equipe, entregas e métricas gerais da agência.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-brand-500/10 text-brand-400 border border-brand-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-400 animate-pulse"></span>
                        Modo Administrador
                    </span>
                </div>
            </div>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h1 class="text-xl font-bold text-white tracking-wide">Olá, {{ auth()->user()->name }}! 👋</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-0.5">Aqui está o seu resumo pessoal de tarefas, entregas e produtividade.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Espaço Pessoal
                    </span>
                </div>
            </div>
        @endif
    </x-slot>

    <div class="p-4 sm:p-6 space-y-6">

        {{-- Painel de Frase do Dia (BorderBeam) --}}
        <x-border-beam-panel class="mb-2" :idleSpeed="30" :hoverSpeed="200" :thickness="2">
            <div class="flex items-center gap-4 text-white">
                <div class="h-10 w-10 shrink-0 rounded-full bg-brand-500/20 text-brand-400 grid place-items-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-0.5">Mensagem do Dia</h3>
                    <p class="text-sm sm:text-base font-medium italic text-slate-200">"{{ $phraseContent }}"</p>
                </div>
            </div>
        </x-border-beam-panel>

        @if($isAdmin)
            {{-- ========================================================================= --}}
            {{--                               ADMIN DASHBOARD                             --}}
            {{-- ========================================================================= --}}

            {{-- 1. Cartões de Métricas Globais --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                {{-- Total de Cards --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Total de Demandas</div>
                        <div class="text-3xl font-bold text-amber-400">{{ $stats['tasks'] }}</div>
                        <div class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                            <span class="text-slate-300 font-medium">{{ $stats['pending'] }}</span> pendentes no time
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-amber-900/20" style="background-color: #eab30822"></div>
                </div>

                {{-- Concluídos / Publicados --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Publicados / Entregues</div>
                        <div class="text-3xl font-bold text-emerald-400">{{ $stats['published'] }}</div>
                        <div class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                            <span class="text-emerald-400 font-semibold">{{ $stats['completion_rate'] }}%</span> de conclusão global
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-emerald-900/20" style="background-color: #22c55e22"></div>
                </div>

                {{-- Atrasados --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Em Atraso</div>
                        <div class="text-3xl font-bold {{ $stats['late'] > 0 ? 'text-rose-400' : 'text-slate-300' }}">{{ $stats['late'] }}</div>
                        <div class="text-xs mt-2 flex items-center gap-1">
                            @if($stats['late'] > 0)
                                <span class="text-rose-400 font-medium">Requer atenção do time</span>
                            @else
                                <span class="text-emerald-400 font-medium">Nenhum atraso no time 🎉</span>
                            @endif
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-rose-900/20" style="background-color: #ef444422"></div>
                </div>

                {{-- Projetos & Clientes --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Projetos & Clientes</div>
                        <div class="text-3xl font-bold text-sky-400">{{ $stats['projects'] }} <span class="text-sm font-normal text-slate-400">proj.</span></div>
                        <div class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                            <span class="text-slate-300 font-medium">{{ $stats['clients'] }}</span> clientes ativos
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-sky-900/20" style="background-color: #0ea5e922"></div>
                </div>
            </div>

            {{-- 2. Acompanhamento & Evolução da Equipe --}}
            <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm overflow-hidden">
                <header class="border-b border-ink-600 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold text-white">Acompanhamento e Evolução da Equipe</h2>
                            <span class="bg-brand-500/20 text-brand-400 text-xs font-bold px-2 py-0.5 rounded-full">{{ $teamMembers->count() }} colaboradores</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Progresso de tarefas, volume de entregas e status de cada colaborador em tempo real.</p>
                    </div>
                    <a href="{{ route('team.index') }}" class="text-xs font-medium text-brand-400 hover:text-brand-300 flex items-center gap-1">
                        Gerenciar Equipe →
                    </a>
                </header>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300 min-w-[700px]">
                        <thead class="border-b border-ink-600 bg-ink-900/40 text-xs uppercase text-slate-400 tracking-wider">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Colaborador</th>
                                <th class="px-4 py-3 font-semibold text-center">Progresso & Evolução</th>
                                <th class="px-4 py-3 font-semibold text-center">Concluídas</th>
                                <th class="px-4 py-3 font-semibold text-center">Pendentes</th>
                                <th class="px-4 py-3 font-semibold text-center">Atrasos</th>
                                <th class="px-5 py-3 font-semibold">Última Demanda Ativa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700/60">
                            @forelse($teamMembers as $member)
                                <tr class="hover:bg-ink-800/60 transition">
                                    {{-- Membro --}}
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :user="$member" :size="9" />
                                            <div>
                                                <div class="font-medium text-white flex items-center gap-2">
                                                    {{ $member->name }}
                                                    @if($member->isAdmin())
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-brand-500/20 text-brand-300 font-semibold border border-brand-500/30">Admin</span>
                                                    @else
                                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-ink-700 text-slate-400 border border-ink-600 font-medium">Colaborador</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-slate-400 mt-0.5">{{ $member->email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Barra de Progresso --}}
                                    <td class="px-4 py-3.5 min-w-[160px]">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium {{ $member->progress_percent >= 100 && $member->tasks_total > 0 ? 'text-emerald-400' : 'text-slate-300' }}">
                                                {{ $member->progress_percent }}%
                                            </span>
                                            <span class="text-[11px] text-slate-400">
                                                {{ $member->tasks_completed }}/{{ $member->tasks_total }}
                                            </span>
                                        </div>
                                        <div class="h-2 w-full rounded-full bg-ink-900 overflow-hidden border border-ink-700">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $member->tasks_late > 0 ? 'bg-gradient-to-r from-amber-500 to-rose-500' : ($member->progress_percent == 100 && $member->tasks_total > 0 ? 'bg-emerald-500' : 'bg-gradient-to-r from-brand-500 to-emerald-500') }}"
                                                 style="width: {{ $member->progress_percent }}%"></div>
                                        </div>
                                    </td>

                                    {{-- Concluídas --}}
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            {{ $member->tasks_completed }}
                                        </span>
                                    </td>

                                    {{-- Pendentes --}}
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                            {{ $member->tasks_pending }}
                                        </span>
                                    </td>

                                    {{-- Atrasos --}}
                                    <td class="px-4 py-3.5 text-center">
                                        @if($member->tasks_late > 0)
                                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 animate-pulse">
                                                {{ $member->tasks_late }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs text-slate-400 bg-ink-900 border border-ink-700">
                                                0
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Última Tarefa --}}
                                    <td class="px-5 py-3.5">
                                        @if($member->latest_task)
                                            <button @click="$dispatch('open-task-modal', '{{ route('tasks.show', $member->latest_task) }}')"
                                                    class="text-left group/item max-w-[220px] block truncate">
                                                <div class="text-xs font-medium text-slate-200 group-hover/item:text-brand-400 truncate transition">
                                                    {{ $member->latest_task->title }}
                                                </div>
                                                <div class="text-[11px] text-slate-400 truncate mt-0.5">
                                                    {{ $member->latest_task->client->name }} · {{ $member->latest_task->project->name }}
                                                </div>
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400 italic">Sem tarefas recentes</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400 italic">
                                        Nenhum membro encontrado na empresa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- 3. Gráficos Comparativos da Agência e Equipe --}}
            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Gráfico 1: Produtividade Semanal / Quinzenal --}}
                <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm flex flex-col justify-between">
                    <header class="mb-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Produtividade Geral da Agência</h2>
                            <span class="text-xs text-emerald-400 font-medium bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">Últimos 14 dias</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Volume de cards concluídos por dia em toda a operação.</p>
                    </header>
                    <div class="relative h-64 w-full">
                        <canvas id="productivityChart"></canvas>
                    </div>
                </section>

                {{-- Gráfico 2: Desempenho e Distribuição da Equipe --}}
                <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm flex flex-col justify-between">
                    <header class="mb-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Distribuição & Ritmo da Equipe</h2>
                            <span class="text-xs text-brand-400 font-medium bg-brand-500/10 px-2 py-0.5 rounded border border-brand-500/20">Por Colaborador</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Comparativo de tarefas concluídas vs pendentes por membro.</p>
                    </header>
                    <div class="relative h-64 w-full">
                        <canvas id="teamPerformanceChart"></canvas>
                    </div>
                </section>
            </div>

            {{-- 4. Alertas de Atraso e Próximos Posts da Agência --}}
            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Cards Atrasados --}}
                <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                    <header class="border-b border-ink-600 px-5 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="h-2.5 w-2.5 rounded-full bg-rose-500 {{ $lateTasks->count() > 0 ? 'animate-ping' : '' }}"></div>
                            <h2 class="text-sm font-semibold text-white">Cards em Atraso (Requerem Atenção)</h2>
                        </div>
                        <span class="bg-rose-500/20 text-rose-400 text-xs font-bold px-2 py-0.5 rounded-full border border-rose-500/30">{{ $lateTasks->count() }}</span>
                    </header>
                    <ul class="divide-y divide-ink-700/50">
                        @forelse($lateTasks as $task)
                            <li class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-ink-800/50 transition">
                                <div class="min-w-0 flex-1">
                                    <button @click="$dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')" class="text-left group block truncate">
                                        <span class="truncate text-sm font-medium text-slate-200 group-hover:text-brand-400 transition">{{ $task->title }}</span>
                                        <div class="truncate text-xs text-slate-400 mt-0.5">
                                            {{ $task->client->name }} · {{ $task->project->name }}
                                        </div>
                                    </button>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs font-medium text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20">
                                        {{ $task->publish_date->format('d/m/Y') }}
                                    </span>
                                    @if($task->assignee)
                                        <div class="flex items-center gap-1.5" title="Responsável: {{ $task->assignee->name }}">
                                            <x-avatar :user="$task->assignee" :size="6" />
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-slate-400 italic">
                                Excelente! Não há cards atrasados na agência 🎉
                            </li>
                        @endforelse
                    </ul>
                </section>

                {{-- Próximas Entregas da Agência --}}
                <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                    <header class="border-b border-ink-600 px-5 py-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-white">Próximos Posts & Prazos da Agência</h2>
                        <span class="bg-brand-500/20 text-brand-400 text-xs font-bold px-2 py-0.5 rounded-full">{{ $upcoming->count() }}</span>
                    </header>
                    <ul class="divide-y divide-ink-700/50">
                        @forelse($upcoming as $task)
                            <li class="flex items-center gap-4 px-5 py-3.5 hover:bg-ink-800/50 transition">
                                <div class="flex flex-col items-center justify-center h-11 w-11 shrink-0 rounded-lg bg-ink-700/50 border border-ink-600">
                                    <span class="text-xs font-bold text-slate-200">{{ $task->publish_date->format('d') }}</span>
                                    <span class="text-[9px] uppercase tracking-wider text-slate-400">{{ $task->publish_date->translatedFormat('M') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <button @click="$dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')" class="text-left group block truncate w-full">
                                        <span class="truncate text-sm font-medium text-slate-200 group-hover:text-brand-400 transition">{{ $task->title }}</span>
                                        <div class="truncate text-xs text-slate-400 mt-0.5">
                                            {{ $task->client->name }} · {{ $task->project->name }}
                                        </div>
                                    </button>
                                </div>
                                @if($task->assignee)
                                    <div class="shrink-0 flex items-center gap-1.5" title="Responsável: {{ $task->assignee->name }}">
                                        <x-avatar :user="$task->assignee" :size="7" />
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-sm text-slate-400 italic">
                                Nenhum post agendado para os próximos dias.
                            </li>
                        @endforelse
                    </ul>
                </section>
            </div>

        @else
            {{-- ========================================================================= --}}
            {{--                         COLLABORATOR / MEMBER DASHBOARD                   --}}
            {{-- ========================================================================= --}}

            {{-- 1. Cartões de Métricas Pessoais --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                {{-- Minhas Tarefas Pendentes --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Minhas Pendências</div>
                        <div class="text-3xl font-bold text-brand-400">{{ $myStats['pending'] }}</div>
                        <div class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                            <span class="text-slate-300 font-medium">{{ $myStats['total'] }}</span> tarefas atribuídas a você
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-brand-900/20" style="background-color: #6366f122"></div>
                </div>

                {{-- Minhas Concluídas --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Minhas Entregas</div>
                        <div class="text-3xl font-bold text-emerald-400">{{ $myStats['completed'] }}</div>
                        <div class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                            <span class="text-emerald-400 font-semibold">{{ $myStats['completion_rate'] }}%</span> de taxa de conclusão
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-emerald-900/20" style="background-color: #22c55e22"></div>
                </div>

                {{-- Meus Atrasos --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Meus Atrasos</div>
                        <div class="text-3xl font-bold {{ $myStats['late'] > 0 ? 'text-rose-400' : 'text-slate-300' }}">{{ $myStats['late'] }}</div>
                        <div class="text-xs mt-2 flex items-center gap-1">
                            @if($myStats['late'] > 0)
                                <span class="text-rose-400 font-medium">Entregas pendentes com prazo vencido</span>
                            @else
                                <span class="text-emerald-400 font-medium">Tudo em dia! Bom trabalho 🎯</span>
                            @endif
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-rose-900/20" style="background-color: #ef444422"></div>
                </div>

                {{-- Meus Projetos Ativos --}}
                <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Meus Projetos</div>
                        <div class="text-3xl font-bold text-sky-400">{{ $myStats['projects_count'] }}</div>
                        <div class="text-xs text-slate-400 mt-2 flex items-center gap-1">
                            <span class="text-slate-300 font-medium">Projetos com demandas suas</span>
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-sky-900/20" style="background-color: #0ea5e922"></div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                {{-- Coluna Principal: Minha Fila de Trabalho & Produtividade --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Fila de Tarefas Pendentes --}}
                    <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                        <header class="border-b border-ink-600 px-5 py-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-white">Minhas Tarefas Prioritárias</h2>
                                <p class="text-xs text-slate-400 mt-0.5">Tarefas atribuídas a você ordenadas por urgência e prazo.</p>
                            </div>
                            <a href="{{ route('my-tasks') }}" class="text-xs font-medium text-brand-400 hover:text-brand-300 flex items-center gap-1">
                                Ver todas ({{ $myStats['pending'] }}) →
                            </a>
                        </header>
                        <ul class="divide-y divide-ink-700/60">
                            @forelse($myPendingTasks as $task)
                                @php
                                    $isLate = $task->publish_date && $task->publish_date->isPast() && !$task->publish_date->isToday();
                                    $isToday = $task->publish_date && $task->publish_date->isToday();
                                @endphp
                                <li class="flex items-center gap-3.5 px-5 py-3.5 hover:bg-ink-800/60 transition group">
                                    {{-- Indicador de Coluna --}}
                                    <span class="h-3 w-3 rounded-full shrink-0 ring-2 ring-ink-800" style="background: {{ $task->column->color ?? '#6366f1' }}" title="Coluna: {{ $task->column->name ?? 'Sem coluna' }}"></span>

                                    {{-- Título e Contexto --}}
                                    <div class="min-w-0 flex-1">
                                        <button @click="$dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')" class="text-left w-full block">
                                            <div class="text-sm font-medium text-slate-200 group-hover:text-brand-400 transition truncate">
                                                {{ $task->title }}
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5 truncate">
                                                <span>{{ $task->client->name }}</span>
                                                <span>·</span>
                                                <span class="text-slate-400">{{ $task->project->name }}</span>
                                                <span>·</span>
                                                <span class="text-slate-300">{{ $task->column->name }}</span>
                                            </div>
                                        </button>
                                    </div>

                                    {{-- Badge de Prazo / Status --}}
                                    <div class="shrink-0 flex items-center gap-2">
                                        @if($isLate)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                                Atrasado ({{ $task->publish_date->format('d/m') }})
                                            </span>
                                        @elseif($isToday)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">
                                                Hoje
                                            </span>
                                        @elseif($task->publish_date)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-ink-700 text-slate-300 border border-ink-600">
                                                {{ $task->publish_date->format('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="px-5 py-12 text-center text-sm text-slate-400 italic">
                                    <div class="text-2xl mb-2">🎉</div>
                                    Você não possui nenhuma tarefa pendente no momento!
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    {{-- Meu Gráfico de Produtividade Pessoal --}}
                    <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm">
                        <header class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-white">Minha Produtividade Pessoal</h2>
                                <p class="text-xs text-slate-400">Cards finalizados por você nos últimos 14 dias</p>
                            </div>
                            <span class="text-xs font-medium text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">Seu Ritmo</span>
                        </header>
                        <div class="relative h-56 w-full">
                            <canvas id="memberProductivityChart"></canvas>
                        </div>
                    </section>
                </div>

                {{-- Coluna Lateral: Meus Projetos e Próximos Posts --}}
                <div class="space-y-6 lg:col-span-1">
                    {{-- Meus Projetos Ativos --}}
                    <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                        <header class="border-b border-ink-600 px-5 py-4 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-white">Meus Projetos Ativos</h2>
                            <span class="bg-brand-500/20 text-brand-400 text-xs font-bold px-2 py-0.5 rounded-full">{{ $myProjects->count() }}</span>
                        </header>
                        <ul class="divide-y divide-ink-700/50">
                            @forelse($myProjects as $project)
                                <li class="p-4 hover:bg-ink-800/50 transition">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <a href="{{ route('projects.board', $project) }}" class="font-medium text-sm text-slate-200 hover:text-brand-400 transition truncate max-w-[180px]">
                                            {{ $project->name }}
                                        </a>
                                        <span class="text-xs text-slate-400 font-medium">
                                            {{ $project->my_pending }} pendentes
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-400 mb-2 truncate">
                                        {{ $project->client->name }}
                                    </div>
                                    {{-- Barra de Progresso no Projeto --}}
                                    <div class="h-1.5 w-full rounded-full bg-ink-900 overflow-hidden border border-ink-700">
                                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $project->progress_percent }}%"></div>
                                    </div>
                                </li>
                            @empty
                                <li class="px-5 py-8 text-center text-sm text-slate-400 italic">
                                    Nenhum projeto com tarefas suas ainda.
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    {{-- Meus Próximos Posts Agendados --}}
                    <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                        <header class="border-b border-ink-600 px-5 py-4">
                            <h2 class="text-sm font-semibold text-white">Minhas Próximas Publicações</h2>
                        </header>
                        <ul class="divide-y divide-ink-700/50">
                            @forelse($myUpcomingTasks as $task)
                                <li class="flex items-center gap-3.5 px-5 py-3 hover:bg-ink-800/50 transition">
                                    <div class="flex flex-col items-center justify-center h-10 w-10 shrink-0 rounded-lg bg-ink-700/60 border border-ink-600">
                                        <span class="text-xs font-bold text-slate-200">{{ $task->publish_date->format('d') }}</span>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400">{{ $task->publish_date->translatedFormat('M') }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <button @click="$dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')" class="text-left w-full block truncate">
                                            <span class="text-sm font-medium text-slate-200 hover:text-brand-400 transition truncate block">{{ $task->title }}</span>
                                            <div class="text-xs text-slate-400 truncate mt-0.5">{{ $task->client->name }}</div>
                                        </button>
                                    </div>
                                </li>
                            @empty
                                <li class="px-5 py-6 text-center text-xs text-slate-400 italic">
                                    Nenhuma publicação agendada.
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    {{-- Minhas Entregas Recentes --}}
                    @if($myRecentlyCompleted->count() > 0)
                        <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm">
                            <header class="border-b border-ink-600 px-5 py-4">
                                <h2 class="text-sm font-semibold text-emerald-400 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Entregues Recentemente
                                </h2>
                            </header>
                            <ul class="divide-y divide-ink-700/50">
                                @foreach($myRecentlyCompleted as $task)
                                    <li class="px-5 py-3 hover:bg-ink-800/50 transition">
                                        <button @click="$dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')" class="text-left w-full block truncate">
                                            <div class="text-xs font-medium text-slate-300 truncate line-through opacity-80">{{ $task->title }}</div>
                                            <div class="text-[11px] text-slate-400 truncate mt-0.5">{{ $task->client->name }} · {{ $task->project->name }}</div>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            </div>
        @endif

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($isAdmin)
                // 1. Gráfico de Produtividade Global da Agência
                const ctxProd = document.getElementById('productivityChart');
                if (ctxProd) {
                    const ctx = ctxProd.getContext('2d');
                    const chartData = @json($chartData);
                    
                    let gradient = ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.45)');
                    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Cards Finalizados',
                                data: chartData.data,
                                borderColor: '#10b981',
                                backgroundColor: gradient,
                                borderWidth: 2.5,
                                pointBackgroundColor: '#1e1e1e',
                                pointBorderColor: '#10b981',
                                pointBorderWidth: 2,
                                pointRadius: 3.5,
                                pointHoverRadius: 5.5,
                                fill: true,
                                tension: 0.35
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
                                    ticks: { precision: 0, color: '#64748b', font: { size: 11 } },
                                    grid: { color: '#343b4a44', drawBorder: false }
                                },
                                x: {
                                    ticks: { color: '#64748b', font: { size: 11 } },
                                    grid: { display: false, drawBorder: false }
                                }
                            }
                        }
                    });
                }

                // 2. Gráfico de Desempenho e Distribuição da Equipe
                const ctxTeam = document.getElementById('teamPerformanceChart');
                if (ctxTeam) {
                    const teamData = @json($teamChartData);
                    new Chart(ctxTeam.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: teamData.labels,
                            datasets: [
                                {
                                    label: 'Concluídas',
                                    data: teamData.completed,
                                    backgroundColor: '#10b981',
                                    borderRadius: 4,
                                    barPercentage: 0.65,
                                    categoryPercentage: 0.75
                                },
                                {
                                    label: 'Pendentes',
                                    data: teamData.pending,
                                    backgroundColor: '#6366f1',
                                    borderRadius: 4,
                                    barPercentage: 0.65,
                                    categoryPercentage: 0.75
                                },
                                {
                                    label: 'Em Atraso',
                                    data: teamData.late,
                                    backgroundColor: '#ef4444',
                                    borderRadius: 4,
                                    barPercentage: 0.65,
                                    categoryPercentage: 0.75
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        boxWidth: 10,
                                        boxHeight: 10,
                                        usePointStyle: true,
                                        color: '#94a3b8',
                                        font: { size: 11 }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#1e1e1e',
                                    titleColor: '#94a3b8',
                                    bodyColor: '#fff',
                                    borderColor: '#343b4a',
                                    borderWidth: 1,
                                    padding: 10
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, color: '#64748b', font: { size: 11 } },
                                    grid: { color: '#343b4a44', drawBorder: false }
                                },
                                x: {
                                    ticks: { color: '#94a3b8', font: { size: 11 } },
                                    grid: { display: false, drawBorder: false }
                                }
                            }
                        }
                    });
                }

            @else
                // 3. Gráfico de Produtividade Pessoal do Colaborador
                const ctxMember = document.getElementById('memberProductivityChart');
                if (ctxMember) {
                    const ctx = ctxMember.getContext('2d');
                    const chartData = @json($chartData);
                    
                    let gradient = ctx.createLinearGradient(0, 0, 0, 220);
                    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.45)');
                    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Minhas Entregas',
                                data: chartData.data,
                                borderColor: '#818cf8',
                                backgroundColor: gradient,
                                borderWidth: 2.5,
                                pointBackgroundColor: '#1e1e1e',
                                pointBorderColor: '#818cf8',
                                pointBorderWidth: 2,
                                pointRadius: 3.5,
                                pointHoverRadius: 5.5,
                                fill: true,
                                tension: 0.35
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
                                    ticks: { precision: 0, color: '#64748b', font: { size: 11 } },
                                    grid: { color: '#343b4a44', drawBorder: false }
                                },
                                x: {
                                    ticks: { color: '#64748b', font: { size: 11 } },
                                    grid: { display: false, drawBorder: false }
                                }
                            }
                        }
                    });
                }
            @endif
        });
    </script>
    @endpush
</x-app-layout>
