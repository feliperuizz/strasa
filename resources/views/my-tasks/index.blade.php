<x-app-layout title="Minhas Tarefas">
    <x-slot name="header">
        <div class="flex items-start justify-between gap-3" data-recarga-suave="minhas-tarefas-cabecalho">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-200 tracking-wide">Minhas Tarefas</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">
                    Arraste para definir a ordem do seu dia. Concluídas saem da lista.
                </p>
            </div>
            @if($tasks->isNotEmpty())
                <span class="mt-0.5 shrink-0 rounded-full bg-ink-700 px-2.5 py-1 text-[12px] font-semibold text-slate-300">
                    {{ $tasks->count() }} {{ $tasks->count() === 1 ? 'pendente' : 'pendentes' }}
                </span>
            @endif
        </div>
    </x-slot>

    {{-- data-recarga-suave: fechar um card ou concluir pela bolinha atualiza
         so este miolo, sem recarregar a pagina (ver recargaSuave no layout). --}}
    <div class="mx-auto w-full max-w-4xl px-3 py-4 sm:px-6 sm:py-6" data-recarga-suave="minhas-tarefas">
        @forelse($grupos as $grupo)
            @php
                $corDoTitulo = match ($grupo['tom']) {
                    'alerta' => 'text-rose-400',
                    'destaque' => 'text-brand-300',
                    'apagado' => 'text-slate-500',
                    default => 'text-slate-300',
                };
            @endphp

            <section class="mb-6 last:mb-0">
                <div class="mb-2 flex items-baseline gap-2 px-1">
                    <h2 class="text-[12px] font-bold uppercase tracking-wider {{ $corDoTitulo }}">{{ $grupo['titulo'] }}</h2>
                    @if($grupo['subtitulo'])
                        <span class="text-[11.5px] text-slate-500">{{ $grupo['subtitulo'] }}</span>
                    @endif
                    <span class="ml-auto text-[11.5px] tabular-nums text-slate-500">{{ $grupo['tarefas']->count() }}</span>
                </div>

                {{-- Uma lista arrastável por dia: a ordem vale só para quem arrastou. --}}
                <ul class="divide-y divide-ink-700/70 overflow-hidden rounded-xl border {{ $grupo['tom'] === 'alerta' ? 'border-rose-500/25' : 'border-ink-700' }} bg-ink-800"
                    x-init="ordenarMinhasTarefas($el, @js(route('my-tasks.reorder')))">
                    @foreach($grupo['tarefas'] as $task)
                        @php
                            $cliente = $task->project?->client;
                            $horario = $task->publish_time ? \Illuminate\Support\Carbon::parse($task->publish_time)->format('H:i') : null;
                        @endphp
                        {{-- A linha inteira abre a tarefa. Segurar e arrastar muda a
                             ordem (no celular, segurar um instante antes de arrastar). --}}
                        <li data-task-id="{{ $task->id }}"
                            class="group flex cursor-pointer select-none items-start gap-1.5 py-3 pl-1 pr-3 transition hover:bg-ink-700/40 sm:gap-2.5 sm:pl-2 [-webkit-touch-callout:none]"
                            @click="window.__arrastandoMinhaTarefa || $dispatch('open-task-modal', '{{ route('tasks.show', $task) }}')">

                            <span class="mt-0.5 grid h-6 w-5 shrink-0 cursor-grab place-items-center text-slate-600 transition group-hover:text-slate-400 active:cursor-grabbing"
                                  title="Arraste para mudar a ordem" aria-hidden="true">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="7" cy="5" r="1.4"/><circle cx="13" cy="5" r="1.4"/><circle cx="7" cy="10" r="1.4"/><circle cx="13" cy="10" r="1.4"/><circle cx="7" cy="15" r="1.4"/><circle cx="13" cy="15" r="1.4"/></svg>
                            </span>

                            <button type="button" data-nao-arrasta
                                    onclick="window.completeTask(this, {{ $task->id }}, event)"
                                    class="mt-px shrink-0 text-slate-500 transition-colors hover:text-emerald-400 focus:outline-none"
                                    title="Concluir tarefa">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>

                            <div class="min-w-0 flex-1">
                                {{-- href real para ctrl+clique abrir em outra aba. Clique normal
                                     nao navega: sobe ate a linha, que abre o slideover. --}}
                                <a href="{{ route('tasks.show', $task) }}"
                                   class="block break-words text-[14.5px] font-medium leading-snug text-slate-100 transition hover:text-brand-300"
                                   @click="if ($event.ctrlKey || $event.metaKey) { $event.stopPropagation(); } else { $event.preventDefault(); }">
                                    {{ $task->title ?: 'Sem título' }}
                                </a>

                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11.5px] text-slate-400">
                                    @if($task->project)
                                        <a href="{{ route('projects.board', $task->project_id) }}" @click.stop
                                           class="flex min-w-0 max-w-full items-start gap-1.5 hover:text-slate-200">
                                            <span class="mt-[5px] h-1.5 w-1.5 shrink-0 rounded-full" style="background: {{ $cliente?->color ?? '#64748b' }}"></span>
                                            <span class="truncate">{{ $cliente ? $cliente->name.' · ' : '' }}{{ $task->project->name }}</span>
                                        </a>
                                    @endif

                                    @if($task->column)
                                        <span class="rounded border border-ink-600 bg-ink-900 px-1.5 py-px text-[10.5px] text-slate-300">{{ $task->column->name }}</span>
                                    @endif

                                    @if($grupo['chave'] === 'atrasadas' && $task->publish_date)
                                        <span class="font-semibold text-rose-400">{{ $task->publish_date->format('d/m') }}</span>
                                    @endif

                                    @if($horario)
                                        <span class="inline-flex items-center gap-1 tabular-nums">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ $horario }}
                                        </span>
                                    @endif

                                    @foreach($task->tags as $tag)
                                        <span class="rounded px-1.5 py-px text-[10.5px] font-medium" style="background: {{ $tag->color }}22; color: {{ $tag->color }}">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 px-6 py-12 text-center">
                <div class="mb-2 text-2xl">🎉</div>
                <p class="text-sm text-slate-300">Nada pendente por aqui.</p>
                <p class="mt-1 text-[12.5px] text-slate-500">As tarefas que você concluiu continuam no quadro do projeto.</p>
            </div>
        @endforelse
    </div>

    @push('scripts')
    <script>
        /**
         * Arrastar para ordenar as tarefas de um dia. Chamado pelo x-init de
         * cada lista, então também religa sozinho depois da recarga suave.
         */
        window.ordenarMinhasTarefas = function (lista, url) {
            if (!lista || typeof Sortable === 'undefined') { return; }
            if (lista._ordenacao) { lista._ordenacao.destroy(); }

            var itens = function () {
                return Array.prototype.slice.call(lista.querySelectorAll(':scope > [data-task-id]'));
            };

            lista._ordenacao = new Sortable(lista, {
                animation: 160,
                draggable: '[data-task-id]',
                ghostClass: 'opacity-40',
                chosenClass: 'bg-ink-700/60',
                // Fallback em vez do drag nativo: clique continua abrindo a
                // tarefa; só vira arraste depois de mover alguns pixels.
                forceFallback: true,
                fallbackTolerance: 4,
                // No celular, segurar um instante antes de arrastar — senão
                // rolar a página com o dedo em cima de uma linha a arrastaria.
                delay: 220,
                delayOnTouchOnly: true,
                touchStartThreshold: 6,
                filter: '[data-nao-arrasta]',
                preventOnFilter: false,

                onStart: function () {
                    window.__arrastandoMinhaTarefa = true;
                    lista._ordemAntes = itens();
                },

                onEnd: function (evt) {
                    // O clique que o navegador dispara ao soltar não pode abrir a tarefa.
                    setTimeout(function () { window.__arrastandoMinhaTarefa = false; }, 0);
                    if (evt.oldIndex === evt.newIndex) { return; }

                    var desfazer = function () {
                        (lista._ordemAntes || []).forEach(function (el) { lista.appendChild(el); });
                    };

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ ids: itens().map(function (el) { return parseInt(el.dataset.taskId, 10); }) })
                    })
                    .then(function (res) {
                        if (window.sessaoExpirou(res.status)) { desfazer(); return; }
                        if (!res.ok) { throw new Error('HTTP ' + res.status); }
                    })
                    .catch(function () {
                        desfazer();
                        alert('Não foi possível salvar a nova ordem. Tente de novo.');
                    });
                }
            });
        };
    </script>
    @endpush
</x-app-layout>
