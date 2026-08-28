<x-app-layout title="Demandas">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-200 tracking-wide">Demandas da Equipe</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">O que cada pessoa tem para entregar, e quando.</p>
            </div>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 space-y-6">

        {{-- Atalhos de período --}}
        <div class="flex flex-wrap gap-2">
            @foreach($atalhos as $chave => $rotulo)
                @php
                    $ativo = ($filtros['atalho'] ?? null) === $chave;
                    $params = array_filter([
                        'atalho' => $chave,
                        'user' => $filtros['user'] ?? null,
                        'client' => $filtros['client'] ?? null,
                        'concluidas' => $filtros['concluidas'] ?? null,
                    ]);
                @endphp
                <a href="{{ route('demands.index', $params) }}"
                   class="rounded-lg border px-3.5 py-1.5 text-[13px] font-semibold transition
                          {{ $ativo
                             ? 'border-brand-500 bg-brand-500/15 text-brand-300'
                             : 'border-ink-600 bg-ink-800 text-slate-300 hover:border-ink-500 hover:text-slate-100' }}">
                    {{ $rotulo }}
                </a>
            @endforeach
        </div>

        {{-- Filtros --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-ink-600 bg-ink-800/60 p-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">De</label>
                <input type="date" name="de" value="{{ $modo === 'intervalo' ? $filtros['de'] : '' }}"
                       class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Até</label>
                <input type="date" name="ate" value="{{ $modo === 'intervalo' ? $filtros['ate'] : '' }}"
                       class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Colaborador</label>
                <select name="user" class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todos</option>
                    @foreach($equipe as $membro)
                        <option value="{{ $membro->id }}" @selected(($filtros['user'] ?? null) == $membro->id)>{{ $membro->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Cliente</label>
                <select name="client" class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(($filtros['client'] ?? null) == $cliente->id)>{{ $cliente->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 pb-2 cursor-pointer">
                <input type="checkbox" name="concluidas" value="1" @checked(! empty($filtros['concluidas']))
                       class="rounded border-ink-500 bg-ink-700 text-brand-500 focus:ring-brand-500">
                <span class="text-[13px] text-slate-300">Incluir concluídas</span>
            </label>

            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 transition">Aplicar</button>
            <a href="{{ route('demands.index') }}" class="px-2 py-2 text-sm text-slate-400 hover:text-slate-200">Limpar</a>
        </form>

        {{-- Resumo --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Demandas</div>
                <div class="text-3xl font-bold text-slate-100">{{ $resumo['total'] }}</div>
            </div>
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Atrasadas</div>
                <div class="text-3xl font-bold {{ $resumo['atrasadas'] > 0 ? 'text-rose-400' : 'text-slate-500' }}">{{ $resumo['atrasadas'] }}</div>
            </div>
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Concluídas</div>
                <div class="text-3xl font-bold text-emerald-400">{{ $resumo['concluidas'] }}</div>
            </div>
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Sem responsável</div>
                <div class="text-3xl font-bold {{ $resumo['sem_responsavel'] > 0 ? 'text-amber-400' : 'text-slate-500' }}">{{ $resumo['sem_responsavel'] }}</div>
            </div>
        </div>

        @if(empty($colaboradores) && $semResponsavel->isEmpty())
            <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 p-12 text-center">
                <div class="text-3xl mb-3">🎉</div>
                <h3 class="text-slate-200 font-medium mb-1">Nada nesse período</h3>
                <p class="text-sm text-slate-400">Ninguém tem demanda com data neste filtro.</p>
            </div>
        @else

            {{-- Um bloco por colaborador --}}
            <div class="space-y-4">
                @foreach($colaboradores as $dados)
                    @php $pessoa = $dados['pessoa']; @endphp

                    <div class="rounded-xl border border-ink-600 bg-ink-800 overflow-hidden"
                         x-data="{ aberto: true }">

                        <button @click="aberto = !aberto"
                                class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left hover:bg-ink-700/40 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <x-avatar :user="$pessoa" :size="9" />
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-100 truncate">{{ $pessoa->name }}</div>
                                    <div class="text-[11.5px] text-slate-500">
                                        {{ $dados['tarefas']->count() }} {{ $dados['tarefas']->count() === 1 ? 'demanda' : 'demandas' }}
                                        @if($dados['atrasadas'] > 0)
                                            · <span class="text-rose-400 font-semibold">{{ $dados['atrasadas'] }} atrasada{{ $dados['atrasadas'] > 1 ? 's' : '' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="text-slate-500 text-sm" x-text="aberto ? '▾' : '▸'"></span>
                        </button>

                        <div x-show="aberto" x-cloak class="border-t border-ink-700">
                            @foreach($dados['porDia'] as $dia => $tarefasDoDia)
                                @php
                                    $data = $dia === 'sem-data' ? null : \Carbon\Carbon::parse($dia);
                                    $atrasado = $data && $data->isBefore(now()->startOfDay());
                                @endphp

                                <div class="px-5 py-2 bg-ink-900/40 border-b border-ink-700/60">
                                    <span class="text-[11px] font-bold uppercase tracking-wider {{ $atrasado ? 'text-rose-400' : 'text-slate-500' }}">
                                        @if(! $data)
                                            Sem data definida
                                        @else
                                            {{ $data->translatedFormat('D, d \d\e F') }}
                                            @if($data->isToday()) · hoje
                                            @elseif($data->isTomorrow()) · amanhã
                                            @elseif($atrasado) · atrasada
                                            @endif
                                        @endif
                                    </span>
                                </div>

                                @foreach($tarefasDoDia as $tarefa)
                                    <div class="flex items-start gap-3 px-5 py-3 border-b border-ink-700/40 last:border-0 hover:bg-ink-700/20 transition">
                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                              style="background: {{ $tarefa->column?->color ?: '#64748b' }}"
                                              title="{{ $tarefa->column?->name }}"></span>

                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('projects.board', $tarefa->project_id) }}"
                                               class="text-[13.5px] font-medium text-slate-200 hover:text-brand-400 transition
                                                      {{ $tarefa->is_published ? 'line-through opacity-60' : '' }}">
                                                {{ $tarefa->title }}
                                            </a>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[11.5px] text-slate-500">
                                                @if($tarefa->client)
                                                    <span class="inline-flex items-center gap-1">
                                                        <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $tarefa->client->color ?: '#64748b' }}"></span>
                                                        {{ $tarefa->client->name }}
                                                    </span>
                                                @endif
                                                @if($tarefa->project)<span>{{ $tarefa->project->name }}</span>@endif
                                                @if($tarefa->column)<span>{{ $tarefa->column->name }}</span>@endif
                                                @if($tarefa->publish_time)
                                                    <span>{{ \Carbon\Carbon::parse($tarefa->publish_time)->format('H:i') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        @if($tarefa->is_published)
                                            <span class="shrink-0 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-400">Feito</span>
                                        @endif
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Tarefas sem ninguém atribuído --}}
                @if($semResponsavel->isNotEmpty())
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/[0.05] overflow-hidden" x-data="{ aberto: true }">
                        <button @click="aberto = !aberto"
                                class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left hover:bg-amber-500/[0.08] transition">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-amber-500/15 text-amber-400 text-sm font-bold">?</span>
                                <div>
                                    <div class="text-sm font-semibold text-amber-300">Sem responsável</div>
                                    <div class="text-[11.5px] text-amber-500/70">{{ $semResponsavel->count() }} demanda{{ $semResponsavel->count() > 1 ? 's' : '' }} sem ninguém atribuído</div>
                                </div>
                            </div>
                            <span class="text-amber-500/70 text-sm" x-text="aberto ? '▾' : '▸'"></span>
                        </button>

                        <div x-show="aberto" x-cloak class="border-t border-amber-500/20">
                            @foreach($semResponsavel as $tarefa)
                                <div class="flex items-start gap-3 px-5 py-3 border-b border-amber-500/10 last:border-0">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" style="background: {{ $tarefa->column?->color ?: '#64748b' }}"></span>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('projects.board', $tarefa->project_id) }}"
                                           class="text-[13.5px] font-medium text-slate-200 hover:text-brand-400 transition">{{ $tarefa->title }}</a>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-x-2.5 text-[11.5px] text-slate-500">
                                            @if($tarefa->client)<span>{{ $tarefa->client->name }}</span>@endif
                                            @if($tarefa->publish_date)<span>{{ $tarefa->publish_date->format('d/m/Y') }}</span>@endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
