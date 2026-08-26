<x-app-layout title="Aprovações">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-200 tracking-wide">Aprovações dos Clientes</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">Acompanhe o que foi enviado, aprovado e devolvido para ajuste.</p>
            </div>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 space-y-6">

        {{-- Resumo do período --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Aguardando o cliente</div>
                <div class="text-3xl font-bold text-amber-400">{{ $resumo['pendentes'] }}</div>
            </div>
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Aprovadas</div>
                <div class="text-3xl font-bold text-emerald-400">{{ $resumo['aprovadas'] }}</div>
            </div>
            <div class="rounded-xl border border-ink-600 bg-ink-800 p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Voltaram para ajuste</div>
                <div class="text-3xl font-bold text-rose-400">{{ $resumo['ajustes'] }}</div>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-ink-600 bg-ink-800/60 p-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Cliente</label>
                <select name="client" class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(($filtros['client'] ?? null) == $cliente->id)>
                            {{ $cliente->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Situação</label>
                <select name="status" class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todas</option>
                    <option value="pending" @selected(($filtros['status'] ?? null) === 'pending')>Aguardando</option>
                    <option value="approved" @selected(($filtros['status'] ?? null) === 'approved')>Aprovadas</option>
                    <option value="rejected" @selected(($filtros['status'] ?? null) === 'rejected')>Ajuste pedido</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Período</label>
                <select name="periodo" class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="7" @selected($filtros['periodo'] === '7')>Últimos 7 dias</option>
                    <option value="30" @selected($filtros['periodo'] === '30')>Últimos 30 dias</option>
                    <option value="90" @selected($filtros['periodo'] === '90')>Últimos 90 dias</option>
                    <option value="todos" @selected($filtros['periodo'] === 'todos')>Tudo</option>
                </select>
            </div>

            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 transition">
                Filtrar
            </button>

            @if(($filtros['client'] ?? null) || ($filtros['status'] ?? null) || $filtros['periodo'] !== '30')
                <a href="{{ route('approvals.index') }}" class="text-sm text-slate-400 hover:text-slate-200 px-2 py-2">Limpar</a>
            @endif
        </form>

        {{-- Lista --}}
        @if($aprovacoes->isEmpty())
            <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 p-12 text-center">
                <div class="text-3xl mb-3">📋</div>
                <h3 class="text-slate-200 font-medium mb-1">Nenhuma aprovação neste filtro</h3>
                <p class="text-sm text-slate-400">
                    Arraste um card para a coluna de aprovação, ou use o botão “Enviar para aprovação” dentro da tarefa.
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($aprovacoes as $aprovacao)
                    @php
                        $task = $aprovacao->task;
                        $cores = match($aprovacao->status) {
                            'approved' => ['border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-400'],
                            'rejected' => ['border-rose-500/30', 'bg-rose-500/10', 'text-rose-400'],
                            default    => ['border-amber-500/30', 'bg-amber-500/10', 'text-amber-400'],
                        };
                    @endphp

                    <div class="rounded-xl border border-ink-600 bg-ink-800 p-4 hover:border-ink-500 transition">
                        <div class="flex flex-col sm:flex-row sm:items-start gap-4">

                            {{-- Cliente --}}
                            <div class="flex items-center gap-3 sm:w-52 shrink-0">
                                @if($aprovacao->client?->logo_url)
                                    <img src="{{ $aprovacao->client->logo_url }}" alt=""
                                         class="h-9 w-9 rounded-lg object-cover bg-ink-700">
                                @else
                                    <span class="h-9 w-9 rounded-lg grid place-items-center text-xs font-bold text-white"
                                          style="background: {{ $aprovacao->client?->color ?: '#475569' }}">
                                        {{ mb_strtoupper(mb_substr($aprovacao->client?->name ?? '?', 0, 2)) }}
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-200 truncate">{{ $aprovacao->client?->name }}</div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ $aprovacao->round > 1 ? $aprovacao->round.'ª versão' : '1ª versão' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Peça --}}
                            <div class="flex-1 min-w-0">
                                @if($task)
                                    <a href="{{ route('projects.board', $task->project_id) }}"
                                       class="text-sm font-medium text-slate-100 hover:text-brand-400 transition">
                                        {{ $task->title }}
                                    </a>
                                @else
                                    <span class="text-sm text-slate-500 italic">Tarefa removida</span>
                                @endif

                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] text-slate-500">
                                    @if($task?->contentTypeLabel())
                                        <span>{{ $task->contentTypeLabel() }}</span>
                                    @endif
                                    <span>Enviado {{ $aprovacao->submitted_at?->diffForHumans() }}</span>
                                    @if($aprovacao->submitter)
                                        <span>por {{ $aprovacao->submitter->name }}</span>
                                    @endif
                                </div>

                                @if(filled($aprovacao->feedback))
                                    <div class="mt-2 rounded-lg border border-ink-600 bg-ink-900/60 px-3 py-2">
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-0.5">
                                            Comentário do cliente
                                        </div>
                                        <p class="text-[13px] text-slate-300 whitespace-pre-line">{{ $aprovacao->feedback }}</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Situação --}}
                            <div class="sm:w-44 shrink-0 sm:text-right">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide {{ $cores[0] }} {{ $cores[1] }} {{ $cores[2] }}">
                                    {{ $aprovacao->statusLabel() }}
                                </span>

                                @if($aprovacao->responded_at)
                                    <div class="mt-1.5 text-[11.5px] text-slate-500">
                                        {{ $aprovacao->reviewer_name }}<br>
                                        {{ $aprovacao->responded_at->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>{{ $aprovacoes->links() }}</div>
        @endif
    </div>
</x-app-layout>
