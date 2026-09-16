{{--
    Histórico do card: quem mexeu, o que fez e quando.

    As linhas já eram gravadas em task_activities desde o início, mas nenhuma
    tela mostrava — então uma pessoa podia anexar arquivo, fechar o checklist e
    passar a tarefa adiante sem que ninguém de fora percebesse.

    Espera $task com a relação activities.user carregada.
--}}
@php
    $atividades = $task->activities;
    $visiveisPorPadrao = 6;
@endphp

<div x-data="{ aberto: true, tudo: false }" class="task-activity">
    <button type="button" @click="aberto = !aberto"
            class="group flex w-full items-center justify-between rounded-lg px-1 py-1 text-left transition hover:bg-ink-800/60">
        <h3 class="flex items-center gap-2 font-semibold text-slate-200">
            <svg class="h-4 w-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Atividade
            @if($atividades->isNotEmpty())
                <span class="rounded-full bg-ink-800 px-2 py-0.5 text-[10px] font-bold text-slate-400">{{ $atividades->count() }}</span>
            @endif
        </h3>

        <svg class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="aberto ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="aberto" x-transition style="display: none;" class="mt-3">
        @if($atividades->isEmpty())
            <p class="px-1 text-sm text-slate-500">Nada aconteceu neste card ainda.</p>
        @else
            {{-- A linha vertical liga os pontos da timeline. --}}
            <ol class="relative space-y-3 border-l border-ink-800 pl-4">
                @foreach($atividades as $i => $atividade)
                    <li class="relative"
                        @if($i >= $visiveisPorPadrao) x-show="tudo" style="display: none;" @endif>

                        <span class="absolute -left-[21px] top-1.5 h-2 w-2 rounded-full ring-4 ring-ink-900"
                              style="background: {{ $atividade->color() }}"></span>

                        <div class="flex items-start gap-2">
                            <div class="shrink-0 pt-0.5">
                                <x-avatar :user="$atividade->user" size="6" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] leading-snug text-slate-300">
                                    <span class="font-semibold text-slate-200">{{ $atividade->authorName() }}</span>
                                    {{ $atividade->description }}
                                </p>

                                @if(filled($atividade->meta['trecho'] ?? null))
                                    <p class="mt-1 border-l-2 border-ink-700 pl-2 text-[12px] italic text-slate-500">
                                        {{ $atividade->meta['trecho'] }}
                                    </p>
                                @endif

                                @if(count($atividade->meta['arquivos'] ?? []) > 1)
                                    <ul class="mt-1 space-y-0.5">
                                        @foreach($atividade->meta['arquivos'] as $arquivo)
                                            <li class="truncate text-[12px] text-slate-500" title="{{ $arquivo }}">• {{ $arquivo }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if(filled($atividade->meta['reason'] ?? null))
                                    <p class="mt-1 border-l-2 border-rose-500/40 pl-2 text-[12px] text-slate-400">
                                        {{ $atividade->meta['reason'] }}
                                    </p>
                                @endif

                                <span class="text-[10px] text-slate-500"
                                      title="{{ $atividade->created_at->format('d/m/Y H:i') }}">
                                    {{ $atividade->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>

            @if($atividades->count() > $visiveisPorPadrao)
                <button type="button" @click="tudo = !tudo"
                        class="mt-3 pl-4 text-xs font-medium text-brand-400 transition hover:text-brand-300"
                        x-text="tudo
                            ? 'Mostrar menos'
                            : 'Ver tudo ({{ $atividades->count() }})'"></button>
            @endif
        @endif
    </div>
</div>
