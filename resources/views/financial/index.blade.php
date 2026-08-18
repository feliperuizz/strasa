<x-app-layout title="Financeiro">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white tracking-wide">Gestão Financeira & Faturamento</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-0.5">Acompanhe cobranças, previsões de faturamento e pagamentos de clientes.</p>
            </div>
            <div class="flex items-center gap-2">
                <button x-data @click="$dispatch('open-modal', 'create-payment-modal')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nova Cobrança
                </button>
            </div>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 space-y-6" x-data="{
        editModal: false,
        editData: {},
        payModal: false,
        payData: {}
    }"
    @open-edit-payment.window="editData = $event.detail; editModal = true"
    @open-mark-paid.window="payData = $event.detail; payModal = true">

        {{-- 1. Barra de Resumo & KPIs Financeiros --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            {{-- Recebido no Período --}}
            <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Recebido no Mês</div>
                    <div class="text-2xl sm:text-3xl font-bold text-emerald-400">
                        R$ {{ number_format($stats['received'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-slate-400 mt-2 flex items-center justify-between">
                        <span class="text-emerald-400 font-semibold">{{ $stats['collection_rate'] }}%</span>
                        <span class="text-slate-400">{{ $stats['paid_count'] }} recebidos</span>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-emerald-900/20" style="background-color: #22c55e22"></div>
            </div>

            {{-- A Receber / Pendente --}}
            <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">A Receber (Em Dia)</div>
                    <div class="text-2xl sm:text-3xl font-bold text-sky-400">
                        R$ {{ number_format($stats['pending'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-slate-400 mt-2 flex items-center justify-between">
                        <span>A vencer no prazo</span>
                        <span class="text-slate-400">{{ $stats['pending_count'] }} pendentes</span>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-sky-900/20" style="background-color: #0ea5e922"></div>
            </div>

            {{-- Em Atraso / Inadimplência --}}
            <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Em Atraso</div>
                    <div class="text-2xl sm:text-3xl font-bold {{ $stats['late'] > 0 ? 'text-rose-400' : 'text-slate-300' }}">
                        R$ {{ number_format($stats['late'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-slate-400 mt-2 flex items-center justify-between">
                        @if($stats['late'] > 0)
                            <span class="text-rose-400 font-medium animate-pulse">Atenção requerida</span>
                            <span class="text-rose-400 font-bold">{{ $stats['late_count'] }} atrasados</span>
                        @else
                            <span class="text-emerald-400 font-medium">Nenhum atraso 🎉</span>
                            <span class="text-slate-400">0 cobranças</span>
                        @endif
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-rose-900/20" style="background-color: #ef444422"></div>
            </div>

            {{-- Previsão Total / Faturamento do Mês --}}
            <div class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Previsão do Mês</div>
                    <div class="text-2xl sm:text-3xl font-bold text-amber-400">
                        R$ {{ number_format($stats['projected'], 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-slate-400 mt-2 flex items-center justify-between">
                        <span>Total acumulado:</span>
                        <span class="text-slate-300 font-medium">R$ {{ number_format($stats['total_all_time'], 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="absolute -right-4 -bottom-4 h-24 w-24 rounded-full blur-2xl transition-transform group-hover:scale-150 bg-amber-900/20" style="background-color: #eab30822"></div>
            </div>
        </div>

        {{-- 2. Gráficos de Faturamento & Métodos de Pagamento --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Gráfico 1: Evolução Mensal --}}
            <section class="lg:col-span-2 rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm flex flex-col justify-between">
                <header class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">Evolução do Faturamento Mensal</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Comparativo entre faturamento recebido e previsto nos últimos 12 meses.</p>
                    </div>
                    <span class="text-xs font-medium text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">Últimos 12 Meses</span>
                </header>
                <div class="relative h-64 w-full">
                    <canvas id="revenueEvolutionChart"></canvas>
                </div>
            </section>

            {{-- Gráfico 2: Métodos de Pagamento --}}
            <section class="lg:col-span-1 rounded-xl border border-ink-600 bg-[#2a2b2d] p-5 shadow-sm flex flex-col justify-between">
                <header class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">Métodos de Pagamento</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Distribuição do volume recebido</p>
                    </div>
                </header>
                <div class="relative h-64 w-full flex items-center justify-center">
                    @if(count($methodChartData['values']) > 0)
                        <canvas id="paymentMethodsChart"></canvas>
                    @else
                        <p class="text-xs text-slate-400 italic text-center">Nenhum pagamento registrado ainda para calcular métodos.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- 3. Barra de Filtros e Busca --}}
        <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] p-4 shadow-sm">
            <form method="GET" action="{{ route('financial.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                {{-- Busca --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Buscar</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Título ou cliente..."
                           class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-xs text-white placeholder-slate-500 focus:border-brand-500 focus:outline-none">
                </div>

                {{-- Mês de Competência --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Competência / Mês</label>
                    <select name="month" onchange="this.form.submit()"
                            class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-xs text-white focus:border-brand-500 focus:outline-none">
                        <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>Todos os Meses</option>
                        @for($i = -3; $i <= 6; $i++)
                            @php
                                $m = now()->subMonths($i);
                                $val = $m->format('Y-m');
                            @endphp
                            <option value="{{ $val }}" {{ $selectedMonth === $val ? 'selected' : '' }}>
                                {{ $m->translatedFormat('F / Y') }}
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Cliente --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Cliente</label>
                    <select name="client_id" onchange="this.form.submit()"
                            class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-xs text-white focus:border-brand-500 focus:outline-none">
                        <option value="">Todos os Clientes</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ (string)$selectedClient === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Status</label>
                    <select name="status" onchange="this.form.submit()"
                            class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-xs text-white focus:border-brand-500 focus:outline-none">
                        <option value="">Todos os Status</option>
                        <option value="paid" {{ $selectedStatus === 'paid' ? 'selected' : '' }}>Pagos</option>
                        <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pendentes (Em Dia)</option>
                        <option value="late" {{ $selectedStatus === 'late' ? 'selected' : '' }}>Em Atraso</option>
                        <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Cancelados</option>
                    </select>
                </div>

                {{-- Método --}}
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Método</label>
                        <select name="method" onchange="this.form.submit()"
                                class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-xs text-white focus:border-brand-500 focus:outline-none">
                            <option value="">Todos os Métodos</option>
                            @foreach(\App\Models\Payment::METHODS as $key => $label)
                                <option value="{{ $key }}" {{ $selectedMethod === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($search || $selectedClient || $selectedStatus || $selectedMethod || ($selectedMonth && $selectedMonth !== now()->format('Y-m')))
                        <a href="{{ route('financial.index') }}" title="Limpar Filtros"
                           class="rounded-lg border border-ink-600 bg-ink-800 p-2 text-slate-400 hover:text-white hover:bg-ink-700 transition">
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </section>

        {{-- 4. Tabela de Cobranças / Pagamentos --}}
        <section class="rounded-xl border border-ink-600 bg-[#2a2b2d] shadow-sm overflow-hidden">
            <header class="border-b border-ink-600 px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold text-white">Cobranças & Pagamentos</h2>
                    <span class="bg-brand-500/20 text-brand-400 text-xs font-bold px-2 py-0.5 rounded-full">{{ $payments->count() }} registros</span>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300 min-w-[850px]">
                    <thead class="border-b border-ink-600 bg-ink-900/50 text-xs uppercase text-slate-400 tracking-wider">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Cliente</th>
                            <th class="px-4 py-3 font-semibold">Descrição / Serviço</th>
                            <th class="px-4 py-3 font-semibold">Valor</th>
                            <th class="px-4 py-3 font-semibold">Vencimento</th>
                            <th class="px-4 py-3 font-semibold">Pagamento</th>
                            <th class="px-4 py-3 font-semibold">Método</th>
                            <th class="px-4 py-3 font-semibold text-center">Status</th>
                            <th class="px-5 py-3 font-semibold text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-700/60">
                        @forelse($payments as $payment)
                            @php
                                $isLate = $payment->isLate();
                                $isPaid = $payment->isPaid();
                            @endphp
                            <tr class="hover:bg-ink-800/60 transition {{ $isLate ? 'bg-rose-950/10' : '' }}">
                                {{-- Cliente --}}
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        @if($payment->client->logo_url)
                                            <img src="{{ $payment->client->logo_url }}" class="h-6 w-6 rounded object-cover" alt="{{ $payment->client->name }}">
                                        @else
                                            <span class="grid h-6 w-6 place-items-center rounded text-[10px] font-bold text-white shadow-sm" style="{{ $payment->client->background_style ?: ('background: ' . ($payment->client->color ?? '#6366f1')) }}">
                                                {{ \Illuminate\Support\Str::substr($payment->client->name, 0, 1) }}
                                            </span>
                                        @endif
                                        <span class="font-medium text-white">{{ $payment->client->name }}</span>
                                    </div>
                                </td>

                                {{-- Descrição --}}
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-slate-200">{{ $payment->title }}</div>
                                    @if($payment->notes)
                                        <div class="text-xs text-slate-400 truncate max-w-xs mt-0.5" title="{{ $payment->notes }}">{{ $payment->notes }}</div>
                                    @endif
                                    @if($payment->attachment_url)
                                        <a href="{{ $payment->attachment_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-brand-400 hover:underline mt-0.5">
                                            📎 Ver Comprovante
                                        </a>
                                    @endif
                                </td>

                                {{-- Valor --}}
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="font-bold text-sm {{ $isPaid ? 'text-emerald-400' : ($isLate ? 'text-rose-400' : 'text-white') }}">
                                        {{ $payment->formatted_amount }}
                                    </span>
                                </td>

                                {{-- Vencimento --}}
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="text-xs font-medium {{ $isLate ? 'text-rose-400 font-bold' : 'text-slate-300' }}">
                                        {{ $payment->due_date->format('d/m/Y') }}
                                    </div>
                                    @if($isLate)
                                        <span class="text-[10px] text-rose-400 font-semibold uppercase">Vencido</span>
                                    @elseif($payment->due_date->isToday() && !$isPaid)
                                        <span class="text-[10px] text-amber-400 font-semibold uppercase animate-pulse">Vence Hoje</span>
                                    @endif
                                </td>

                                {{-- Pagamento --}}
                                <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-300">
                                    @if($payment->paid_at)
                                        <span class="text-emerald-400 font-medium">{{ $payment->paid_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-slate-500">—</span>
                                    @endif
                                </td>

                                {{-- Método --}}
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="text-xs text-slate-300 bg-ink-900 px-2 py-0.5 rounded border border-ink-700">
                                        {{ $payment->method_label }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if($isPaid)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                            Pago
                                        </span>
                                    @elseif($isLate)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-400 border border-rose-500/30 animate-pulse">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                                            Atrasado
                                        </span>
                                    @elseif($payment->status === \App\Models\Payment::STATUS_CANCELLED)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-ink-700 text-slate-400 border border-ink-600">
                                            Cancelado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                            Pendente
                                        </span>
                                    @endif
                                </td>

                                {{-- Ações --}}
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(!$isPaid && $payment->status !== \App\Models\Payment::STATUS_CANCELLED)
                                            <button type="button"
                                                    @click="$dispatch('open-mark-paid', { id: {{ $payment->id }}, title: '{{ addslashes($payment->title) }}', amount: '{{ $payment->formatted_amount }}', client: '{{ addslashes($payment->client->name) }}', method: '{{ $payment->payment_method ?: 'pix' }}' })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-sm transition">
                                                ✓ Pagar
                                            </button>
                                        @endif

                                        <button type="button"
                                                @click="$dispatch('open-edit-payment', {
                                                    id: {{ $payment->id }},
                                                    client_id: {{ $payment->client_id }},
                                                    title: '{{ addslashes($payment->title) }}',
                                                    amount: '{{ $payment->amount }}',
                                                    due_date: '{{ $payment->due_date->format('Y-m-d') }}',
                                                    paid_at: '{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '' }}',
                                                    status: '{{ $payment->status }}',
                                                    payment_method: '{{ $payment->payment_method }}',
                                                    reference_month: '{{ $payment->reference_month }}',
                                                    recurrence: '{{ $payment->recurrence }}',
                                                    notes: '{{ addslashes($payment->notes ?? '') }}',
                                                    update_url: '{{ route('financial.update', $payment) }}'
                                                })"
                                                class="text-xs font-medium text-brand-400 hover:text-brand-300 p-1">
                                            Editar
                                        </button>

                                        <form method="POST" action="{{ route('financial.destroy', $payment) }}" onsubmit="return confirm('Tem certeza que deseja excluir esta cobrança?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-400 hover:text-rose-300 p-1">
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-400 italic">
                                    <div class="text-3xl mb-2">💸</div>
                                    Nenhuma cobrança encontrada para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ================================================================= --}}
        {{--                    MODAL: NOVA COBRANÇA (Alpine.js)               --}}
        {{-- ================================================================= --}}
        <div x-data="{ open: false }"
             @open-modal.window="if ($event.detail === 'create-payment-modal') open = true"
             x-show="open" style="display: none;"
             class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-ink-900/80 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="open"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         @click.outside="open = false"
                         class="relative transform overflow-hidden rounded-xl border border-ink-600 bg-ink-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl">
                        
                        <form method="POST" action="{{ route('financial.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4 space-y-4">
                                <div class="flex items-center justify-between border-b border-ink-600 pb-3">
                                    <h3 class="text-lg font-semibold text-white">Nova Cobrança / Pagamento</h3>
                                    <button type="button" @click="open = false" class="text-slate-400 hover:text-white">✕</button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {{-- Cliente --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Cliente *</label>
                                        <select name="client_id" required class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            <option value="">Selecione o Cliente</option>
                                            @foreach($clients as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Título / Descrição --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Descrição do Serviço / Cobrança *</label>
                                        <input type="text" name="title" required placeholder="Ex: Mensalidade Gestão de Redes Sociais"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Valor --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Valor (R$) *</label>
                                        <input type="number" name="amount" step="0.01" min="0.01" required placeholder="1500.00"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Data de Vencimento --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Data de Vencimento *</label>
                                        <input type="date" name="due_date" value="{{ now()->toDateString() }}" required
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Status Inicial --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Status *</label>
                                        <select name="status" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            <option value="pending">Pendente (Aguardando Pagamento)</option>
                                            <option value="paid">Já Pago (Confirmado)</option>
                                        </select>
                                    </div>

                                    {{-- Método de Pagamento --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Método de Pagamento</label>
                                        <select name="payment_method" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            @foreach(\App\Models\Payment::METHODS as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Competência / Mês --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Mês de Competência</label>
                                        <input type="month" name="reference_month" value="{{ now()->format('Y-m') }}"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Recorrência --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Tipo de Cobrança</label>
                                        <select name="recurrence" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            <option value="monthly">Mensalidade (Recorrente)</option>
                                            <option value="one_time">Avulso / Projeto Único</option>
                                        </select>
                                    </div>

                                    {{-- Observações --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Notas / Observações</label>
                                        <textarea name="notes" rows="2" placeholder="Observações internas sobre o faturamento, link da nota fiscal, etc..."
                                                  class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none"></textarea>
                                    </div>

                                    {{-- Comprovante / Anexo --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Comprovante / Recibo (Opcional)</label>
                                        <input type="file" name="attachment"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-xs text-slate-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-ink-700 file:text-white hover:file:bg-ink-600">
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-ink-600 bg-ink-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 sm:ml-3 sm:w-auto">
                                    Salvar Cobrança
                                </button>
                                <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg border border-ink-600 bg-ink-800 px-4 py-2 text-sm font-semibold text-slate-300 shadow-sm hover:bg-ink-700 sm:mt-0 sm:w-auto">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================================= --}}
        {{--                    MODAL: EDITAR COBRANÇA (Alpine.js)             --}}
        {{-- ================================================================= --}}
        <div x-show="editModal" style="display: none;"
             class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="editModal" x-transition.opacity class="fixed inset-0 bg-ink-900/80 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="editModal"
                         @click.outside="editModal = false"
                         class="relative transform overflow-hidden rounded-xl border border-ink-600 bg-ink-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl">
                        
                        <form method="POST" :action="editData.update_url" enctype="multipart/form-data">
                            @csrf @method('PATCH')
                            <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4 space-y-4">
                                <div class="flex items-center justify-between border-b border-ink-600 pb-3">
                                    <h3 class="text-lg font-semibold text-white">Editar Cobrança</h3>
                                    <button type="button" @click="editModal = false" class="text-slate-400 hover:text-white">✕</button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {{-- Cliente --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Cliente *</label>
                                        <select name="client_id" x-model="editData.client_id" required class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            @foreach($clients as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Título / Descrição --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Descrição do Serviço *</label>
                                        <input type="text" name="title" x-model="editData.title" required
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Valor --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Valor (R$) *</label>
                                        <input type="number" name="amount" x-model="editData.amount" step="0.01" min="0.01" required
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Vencimento --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Data de Vencimento *</label>
                                        <input type="date" name="due_date" x-model="editData.due_date" required
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Status --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Status *</label>
                                        <select name="status" x-model="editData.status" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            <option value="pending">Pendente</option>
                                            <option value="paid">Pago</option>
                                            <option value="cancelled">Cancelado</option>
                                        </select>
                                    </div>

                                    {{-- Data de Pagamento --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Data do Pagamento</label>
                                        <input type="date" name="paid_at" x-model="editData.paid_at"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Método --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Método de Pagamento</label>
                                        <select name="payment_method" x-model="editData.payment_method" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            @foreach(\App\Models\Payment::METHODS as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Competência --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Mês de Competência</label>
                                        <input type="month" name="reference_month" x-model="editData.reference_month"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    {{-- Observações --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Notas / Observações</label>
                                        <textarea name="notes" x-model="editData.notes" rows="2"
                                                  class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none"></textarea>
                                    </div>

                                    {{-- Anexo --}}
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Substituir Comprovante</label>
                                        <input type="file" name="attachment"
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-1.5 text-xs text-slate-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-ink-700 file:text-white hover:file:bg-ink-600">
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-ink-600 bg-ink-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 sm:ml-3 sm:w-auto">
                                    Atualizar Cobrança
                                </button>
                                <button type="button" @click="editModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg border border-ink-600 bg-ink-800 px-4 py-2 text-sm font-semibold text-slate-300 shadow-sm hover:bg-ink-700 sm:mt-0 sm:w-auto">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================================= --}}
        {{--                MODAL: MARCAR COMO PAGO (CONFIRMAÇÃO RÁPIDA)       --}}
        {{-- ================================================================= --}}
        <div x-show="payModal" style="display: none;"
             class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="payModal" x-transition.opacity class="fixed inset-0 bg-ink-900/80 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div x-show="payModal"
                         @click.outside="payModal = false"
                         class="relative transform overflow-hidden rounded-xl border border-ink-600 bg-ink-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                        
                        <form method="POST" :action="`{{ url('/financial') }}/${payData.id}/mark-paid`">
                            @csrf
                            <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4 space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-emerald-500/20 text-emerald-400 grid place-items-center shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-white">Confirmar Recebimento</h3>
                                        <p class="text-xs text-slate-400" x-text="`${payData.client} · ${payData.title}`"></p>
                                    </div>
                                </div>

                                <div class="rounded-lg bg-ink-900 p-3 border border-ink-700 flex items-center justify-between">
                                    <span class="text-xs text-slate-400">Valor a Confirmar:</span>
                                    <span class="text-base font-bold text-emerald-400" x-text="payData.amount"></span>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Data do Pagamento</label>
                                        <input type="date" name="paid_at" value="{{ now()->toDateString() }}" required
                                               class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-300">Forma / Método de Pagamento</label>
                                        <select name="payment_method" x-model="payData.method" class="w-full rounded-lg border border-ink-600 bg-ink-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none">
                                            @foreach(\App\Models\Payment::METHODS as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-ink-600 bg-ink-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:ml-3 sm:w-auto">
                                    Confirmar Pagamento
                                </button>
                                <button type="button" @click="payModal = false" class="mt-3 inline-flex w-full justify-center rounded-lg border border-ink-600 bg-ink-800 px-4 py-2 text-sm font-semibold text-slate-300 shadow-sm hover:bg-ink-700 sm:mt-0 sm:w-auto">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Gráfico de Evolução Mensal do Faturamento
            const ctxRev = document.getElementById('revenueEvolutionChart');
            if (ctxRev) {
                const revData = @json($revenueChartData);
                new Chart(ctxRev.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: revData.labels,
                        datasets: [
                            {
                                label: 'Recebido (R$)',
                                data: revData.received,
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7
                            },
                            {
                                label: 'Previsto / A Receber (R$)',
                                data: revData.pending,
                                backgroundColor: '#38bdf8',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7
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
                                padding: 10,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        label += 'R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#64748b',
                                    font: { size: 11 },
                                    callback: function(val) {
                                        return 'R$ ' + (val >= 1000 ? (val/1000) + 'k' : val);
                                    }
                                },
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

            // 2. Gráfico de Métodos de Pagamento
            const ctxMethod = document.getElementById('paymentMethodsChart');
            if (ctxMethod) {
                const methodData = @json($methodChartData);
                const colors = ['#10b981', '#6366f1', '#f59e0b', '#0ea5e9', '#ec4899', '#8b5cf6'];
                
                new Chart(ctxMethod.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: methodData.labels,
                        datasets: [{
                            data: methodData.values,
                            backgroundColor: colors.slice(0, methodData.labels.length),
                            borderWidth: 2,
                            borderColor: '#2a2b2d'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 8,
                                    boxHeight: 8,
                                    usePointStyle: true,
                                    color: '#94a3b8',
                                    font: { size: 10 },
                                    padding: 12
                                }
                            },
                            tooltip: {
                                backgroundColor: '#1e1e1e',
                                titleColor: '#94a3b8',
                                bodyColor: '#fff',
                                borderColor: '#343b4a',
                                borderWidth: 1,
                                padding: 10,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) label += ': ';
                                        let val = context.parsed;
                                        label += 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
