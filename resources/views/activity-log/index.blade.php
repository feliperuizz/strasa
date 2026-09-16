<x-app-layout title="Log de atividades">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-200 tracking-wide">Log de atividades</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">O que cada pessoa fez hoje, ontem e anteontem. Clique na tarefa para abrir o card.</p>
            </div>
            <span class="self-start rounded-full bg-ink-700 px-2.5 py-1 text-[12px] font-semibold text-slate-300" data-recarga-suave="log-total">
                {{ $total }} {{ $total === 1 ? 'ação' : 'ações' }}
            </span>
        </div>
    </x-slot>

    @php
        $rotuloDoDia = function (\Illuminate\Support\Carbon $dia) {
            return match (true) {
                $dia->isToday() => 'Hoje',
                $dia->isYesterday() => 'Ontem',
                default => 'Anteontem',
            };
        };
    @endphp

    <div class="p-4 sm:p-6 space-y-6">

        {{-- Filtros: dia e pessoa. Mudou, recarrega via GET. --}}
        <form method="GET" action="{{ route('activity-log.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('activity-log.index', array_filter(['usuario' => $usuarioEscolhido])) }}"
                   class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $diaEscolhido ? 'bg-ink-800 text-slate-400 hover:bg-ink-700 hover:text-slate-200' : 'bg-brand-600 text-white' }}">
                    Todos os 3 dias
                </a>
                @foreach($dias as $dia)
                    <a href="{{ route('activity-log.index', array_filter(['dia' => $dia->toDateString(), 'usuario' => $usuarioEscolhido])) }}"
                       class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $diaEscolhido && $diaEscolhido->isSameDay($dia) ? 'bg-brand-600 text-white' : 'bg-ink-800 text-slate-400 hover:bg-ink-700 hover:text-slate-200' }}">
                        {{ $rotuloDoDia($dia) }} <span class="opacity-70">{{ $dia->format('d/m') }}</span>
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                @if($diaEscolhido)
                    <input type="hidden" name="dia" value="{{ $diaEscolhido->toDateString() }}">
                @endif
                <select name="usuario" onchange="this.form.submit()"
                        class="rounded-lg border border-ink-600 bg-ink-800 px-3 py-1.5 text-xs text-slate-200 focus:border-brand-500 focus:outline-none">
                    <option value="">Toda a equipe</option>
                    @foreach($equipe as $membro)
                        <option value="{{ $membro->id }}" @selected((string) $usuarioEscolhido === (string) $membro->id)>
                            {{ $membro->name }}{{ $membro->isDeactivated() ? ' (desativado)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        {{-- data-recarga-suave: fechar um card aberto daqui atualiza so este miolo --}}
        <div class="space-y-4" data-recarga-suave="log">
            @if($grupos->isEmpty())
                <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 p-12 text-center">
                    <div class="text-3xl mb-3">🕰️</div>
                    <h3 class="text-slate-200 font-medium mb-1">Nada por aqui</h3>
                    <p class="text-sm text-slate-400">Nenhuma atividade registrada com esses filtros.</p>
                </div>
            @endif

            @foreach($grupos as $grupo)
                @php $pessoa = $grupo['pessoa']; @endphp

                <div class="rounded-xl border border-ink-600 bg-ink-800 overflow-hidden"
                     x-data="grupoRecolhivel('{{ $grupo['chave'] }}')">

                    {{-- Cabeçalho da pessoa --}}
                    <button @click="alternar()"
                            class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left hover:bg-ink-700/40 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($pessoa)
                                <x-avatar :user="$pessoa" :size="9" />
                            @else
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-amber-500/15 text-amber-300 ring-2 ring-ink-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </span>
                            @endif
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-100 truncate">{{ $pessoa?->name ?? 'Clientes (painel de aprovação)' }}</span>
                                    @if($pessoa?->isDeactivated())
                                        <span class="rounded-full border border-rose-500/30 bg-rose-500/10 px-1.5 py-0.5 text-[9.5px] font-semibold uppercase tracking-wide text-rose-300">Desativado</span>
                                    @endif
                                </div>
                                <div class="text-[11.5px] text-slate-500">
                                    {{ $grupo['total'] }} {{ $grupo['total'] === 1 ? 'ação' : 'ações' }}
                                    @foreach($grupo['resumo'] as $item)
                                        · <span class="text-slate-400">{{ $item }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <span class="text-slate-500 text-sm" x-text="aberto ? '▾' : '▸'"></span>
                    </button>

                    {{-- Dias e linha do tempo --}}
                    <div x-show="aberto" x-cloak class="border-t border-ink-700">
                        @foreach($grupo['porDia'] as $data => $lista)
                            @php $dia = \Illuminate\Support\Carbon::parse($data); @endphp

                            <div class="flex items-center gap-2 bg-ink-900/40 px-5 py-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <span class="{{ $dia->isToday() ? 'text-brand-300' : '' }}">{{ $rotuloDoDia($dia) }}</span>
                                <span class="font-normal normal-case tracking-normal text-slate-600">· {{ $dia->translatedFormat('l, d/m') }}</span>
                                <span class="ml-auto font-normal normal-case tracking-normal">{{ $lista->count() }}</span>
                            </div>

                            <ul class="divide-y divide-ink-700/60">
                                @foreach($lista as $atividade)
                                    @php $tarefa = $atividade->task; @endphp
                                    <li class="flex items-start gap-3 px-5 py-2.5 hover:bg-ink-700/30 transition">
                                        <span class="w-10 shrink-0 pt-0.5 text-[11px] tabular-nums text-slate-500">{{ $atividade->created_at->format('H:i') }}</span>
                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" style="background: {{ $atividade->color() }}" title="{{ $atividade->typeLabel() }}"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[13px] leading-snug text-slate-200">
                                                {{ $atividade->description }}
                                                @if($tarefa)
                                                    <span class="text-slate-500">em</span>
                                                    <a href="{{ route('tasks.show', $tarefa) }}"
                                                       @click.prevent="$dispatch('open-task-modal', '{{ route('tasks.show', $tarefa) }}')"
                                                       class="font-medium text-brand-300 hover:text-brand-200 hover:underline">{{ $tarefa->title ?: 'Sem título' }}</a>
                                                @else
                                                    <span class="text-slate-500 italic">(tarefa excluída)</span>
                                                @endif
                                            </div>
                                            @if($tarefa && $tarefa->project)
                                                <a href="{{ route('projects.board', $tarefa->project_id) }}"
                                                   class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-slate-500 hover:text-slate-300 transition">
                                                    @if($tarefa->project->client)
                                                        <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $tarefa->project->client->color ?? '#64748b' }}"></span>
                                                        {{ $tarefa->project->client->name }} ·
                                                    @endif
                                                    {{ $tarefa->project->name }}
                                                    @if($tarefa->column)
                                                        <span class="text-slate-600">· {{ $tarefa->column->name }}</span>
                                                    @endif
                                                    <svg class="h-3 w-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
