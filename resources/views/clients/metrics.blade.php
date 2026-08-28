<x-app-layout title="Métricas · {{ $client->name }}" :client="$client">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @if($client->logo_url)
                    <img src="{{ $client->logo_url }}" class="h-8 w-8 rounded object-cover ring-1 ring-white/10" alt="">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded text-xs font-bold text-slate-200"
                          style="background: {{ $client->color ?? '#475569' }}">{{ \Illuminate\Support\Str::substr($client->name, 0, 1) }}</span>
                @endif
                <div>
                    <h1 class="text-base font-semibold text-slate-200">{{ $client->name }}</h1>
                    <p class="text-[11.5px] text-slate-400">Métricas de redes sociais</p>
                </div>
            </div>

            @can('update', $client)
                <button x-data @click="$dispatch('abrir-metrica')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-brand-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Lançar métrica
                </button>
            @endcan
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 space-y-6" x-data="{ modal: false, editando: null }" @abrir-metrica.window="modal = true; editando = null">

        {{-- Filtros --}}
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-ink-600 bg-ink-800/60 p-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Rede</label>
                <select name="network" class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Todas</option>
                    @foreach(\App\Models\ClientMetric::NETWORKS as $chave => $info)
                        <option value="{{ $chave }}" @selected($filtros['network'] === $chave)>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Período</label>
                <select name="periodo" class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                    <option value="90" @selected($filtros['periodo'] === '90')>Últimos 3 meses</option>
                    <option value="180" @selected($filtros['periodo'] === '180')>Últimos 6 meses</option>
                    <option value="365" @selected($filtros['periodo'] === '365')>Último ano</option>
                    <option value="todos" @selected($filtros['periodo'] === 'todos')>Tudo</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 transition">Filtrar</button>
            @if($filtros['network'] || $filtros['periodo'] !== '365')
                <a href="{{ route('clients.metrics', $client) }}" class="px-2 py-2 text-sm text-slate-400 hover:text-slate-200">Limpar</a>
            @endif
        </form>

        @if($registros->isEmpty())
            <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 p-12 text-center">
                <div class="text-3xl mb-3">📊</div>
                <h3 class="text-slate-200 font-medium mb-1">Nenhuma métrica lançada ainda</h3>
                <p class="text-sm text-slate-400 mb-4">
                    Registre os números de cada rede periodicamente — o sistema calcula sozinho o
                    ganho entre um lançamento e o seguinte.
                </p>
                @can('update', $client)
                    <button @click="modal = true" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                        Lançar a primeira
                    </button>
                @endcan
            </div>
        @else

            {{-- Resumo --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Seguidores</div>
                    <div class="text-2xl sm:text-3xl font-bold text-slate-100">{{ number_format($resumo['seguidores'], 0, ',', '.') }}</div>
                    <div class="text-xs text-slate-500 mt-2">somando as redes</div>
                </div>

                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Ganho no período</div>
                    @if($resumo['ganho'] === null)
                        <div class="text-2xl font-bold text-slate-500">—</div>
                        <div class="text-xs text-slate-500 mt-2">precisa de 2 lançamentos</div>
                    @else
                        <div class="text-2xl sm:text-3xl font-bold {{ $resumo['ganho'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $resumo['ganho'] >= 0 ? '+' : '' }}{{ number_format($resumo['ganho'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-slate-500 mt-2">novos seguidores</div>
                    @endif
                </div>

                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Alcance</div>
                    <div class="text-2xl sm:text-3xl font-bold text-brand-400">{{ number_format($resumo['alcance'], 0, ',', '.') }}</div>
                    <div class="text-xs text-slate-500 mt-2">somado no período</div>
                </div>

                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Interações</div>
                    <div class="text-2xl sm:text-3xl font-bold text-amber-400">{{ number_format($resumo['engajamento'], 0, ',', '.') }}</div>
                    <div class="text-xs text-slate-500 mt-2">{{ $resumo['publicacoes'] }} publicações</div>
                </div>
            </div>

            {{-- Gráficos --}}
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <h3 class="text-sm font-semibold text-slate-200 mb-4">Evolução de seguidores</h3>
                    <div class="h-64"><canvas id="graficoSeguidores"></canvas></div>
                </div>

                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                    <h3 class="text-sm font-semibold text-slate-200 mb-4">Ganho entre lançamentos</h3>
                    <div class="h-64"><canvas id="graficoGanho"></canvas></div>
                </div>

                <div class="rounded-xl border border-ink-600 bg-ink-800 p-5 lg:col-span-2">
                    <h3 class="text-sm font-semibold text-slate-200 mb-4">Alcance e interações</h3>
                    <div class="h-64"><canvas id="graficoAlcance"></canvas></div>
                </div>
            </div>

            {{-- Tabela --}}
            <div class="rounded-xl border border-ink-600 bg-ink-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-ink-700">
                    <h3 class="text-sm font-semibold text-slate-200">Lançamentos ({{ $registros->count() }})</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-ink-900/50 text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold">Data</th>
                                <th class="px-4 py-2.5 text-left font-semibold">Rede</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Seguidores</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Alcance</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Impressões</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Interações</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Taxa</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Posts</th>
                                @can('update', $client)<th class="px-4 py-2.5"></th>@endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-700">
                            @foreach($registros as $m)
                                <tr class="hover:bg-ink-700/30">
                                    <td class="px-4 py-2.5 text-slate-300 whitespace-nowrap">{{ $m->reference_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center gap-1.5 text-slate-300">
                                            <span class="h-2 w-2 rounded-full" style="background: {{ $m->networkColor() }}"></span>
                                            {{ $m->networkLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-slate-200 font-medium">{{ $m->followers !== null ? number_format($m->followers, 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-400">{{ $m->reach !== null ? number_format($m->reach, 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-400">{{ $m->impressions !== null ? number_format($m->impressions, 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-400">{{ $m->engagement !== null ? number_format($m->engagement, 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-400">{{ $m->engagementRate() !== null ? number_format($m->engagementRate(), 2, ',', '.').'%' : '—' }}</td>
                                    <td class="px-4 py-2.5 text-right text-slate-400">{{ $m->posts_count ?? '—' }}</td>
                                    @can('update', $client)
                                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                            <form method="POST" action="{{ route('metrics.destroy', $m) }}" class="inline"
                                                  onsubmit="return confirm('Remover o lançamento de {{ $m->networkLabel() }} em {{ $m->reference_date->format('d/m/Y') }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-slate-500 hover:text-rose-400">Remover</button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                                @if($m->notes)
                                    <tr class="bg-ink-900/30">
                                        <td colspan="9" class="px-4 pb-2.5 pt-0 text-[12px] text-slate-500 italic">{{ $m->notes }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Formulário de lançamento --}}
        @can('update', $client)
            <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/70 p-4 sm:p-8"
                 @click.self="modal = false" @keydown.escape.window="modal = false">
                <div class="w-full max-w-2xl rounded-xl border border-ink-600 bg-ink-800 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
                        <h3 class="font-semibold text-slate-200">Lançar métrica</h3>
                        <button @click="modal = false" class="text-slate-500 hover:text-slate-200 text-xl leading-none">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('clients.metrics.store', $client) }}" class="p-5 space-y-4">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Rede social *</label>
                                <select name="network" required class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                                    @foreach(\App\Models\ClientMetric::NETWORKS as $chave => $info)
                                        <option value="{{ $chave }}" @selected(old('network') === $chave)>{{ $info['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Data de referência *</label>
                                <input type="date" name="reference_date" required max="{{ now()->toDateString() }}"
                                       value="{{ old('reference_date', now()->toDateString()) }}"
                                       class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>

                        <div class="rounded-lg border border-ink-700 bg-ink-900/40 px-3 py-2 text-[12px] text-slate-400">
                            Informe o <strong class="text-slate-300">total</strong> de seguidores naquela data, não o ganho.
                            O sistema calcula a variação comparando com o lançamento anterior.
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            @foreach(\App\Models\ClientMetric::FIELDS as $campo => $rotulo)
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">{{ $rotulo }}</label>
                                    <input type="number" name="{{ $campo }}" min="0" value="{{ old($campo) }}" placeholder="—"
                                           class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Observação</label>
                            <textarea name="notes" rows="2" maxlength="1000" placeholder="Ex.: campanha de lançamento no ar entre 10 e 20/11"
                                      class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">{{ old('notes') }}</textarea>
                        </div>

                        @if($errors->any())
                            <div class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-[13px] text-rose-300">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" @click="modal = false" class="rounded-lg border border-ink-600 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-ink-700">Cancelar</button>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Salvar lançamento</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>

    @if($registros->isNotEmpty())
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            var series = @json($series);

            Chart.defaults.color = '#94a3b8';
            Chart.defaults.borderColor = 'rgba(148,163,184,0.12)';
            Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

            // Eixo X comum: todas as datas presentes, em ordem cronológica.
            var datas = [];
            Object.values(series).forEach(function (rede) {
                rede.pontos.forEach(function (p) {
                    if (datas.indexOf(p.iso) === -1) { datas.push(p.iso); }
                });
            });
            datas.sort();

            var rotulos = datas.map(function (iso) {
                var partes = iso.split('-');
                return partes[2] + '/' + partes[1];
            });

            /** Monta um dataset por rede, alinhado ao eixo de datas. */
            function conjuntos(campo, preenchido) {
                return Object.values(series).map(function (rede) {
                    var porData = {};
                    rede.pontos.forEach(function (p) { porData[p.iso] = p[campo]; });

                    return {
                        label: rede.label,
                        data: datas.map(function (d) { return porData[d] !== undefined ? porData[d] : null; }),
                        borderColor: rede.cor,
                        backgroundColor: preenchido ? rede.cor + '55' : rede.cor + '22',
                        borderWidth: 2,
                        tension: 0.35,
                        spanGaps: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    };
                });
            }

            var base = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { boxWidth: 12, boxHeight: 12, padding: 14, font: { size: 11 } } },
                },
                scales: {
                    y: { beginAtZero: false, ticks: { font: { size: 11 } } },
                    x: { ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                },
            };

            new Chart(document.getElementById('graficoSeguidores'), {
                type: 'line',
                data: { labels: rotulos, datasets: conjuntos('seguidores', false) },
                options: base,
            });

            new Chart(document.getElementById('graficoGanho'), {
                type: 'bar',
                data: { labels: rotulos, datasets: conjuntos('ganho', true) },
                options: Object.assign({}, base, {
                    scales: {
                        y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                        x: { ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                    },
                }),
            });

            // Alcance e interações no mesmo gráfico, somando as redes por data.
            function somaPorData(campo) {
                return datas.map(function (d) {
                    var total = null;
                    Object.values(series).forEach(function (rede) {
                        rede.pontos.forEach(function (p) {
                            if (p.iso === d && p[campo] !== null) { total = (total || 0) + p[campo]; }
                        });
                    });
                    return total;
                });
            }

            new Chart(document.getElementById('graficoAlcance'), {
                type: 'line',
                data: {
                    labels: rotulos,
                    datasets: [
                        {
                            label: 'Alcance', data: somaPorData('alcance'),
                            borderColor: '#38bdf8', backgroundColor: '#38bdf822',
                            borderWidth: 2, tension: 0.35, fill: true, spanGaps: true,
                        },
                        {
                            label: 'Interações', data: somaPorData('engajamento'),
                            borderColor: '#fbbf24', backgroundColor: '#fbbf2422',
                            borderWidth: 2, tension: 0.35, fill: true, spanGaps: true,
                        },
                    ],
                },
                options: Object.assign({}, base, {
                    scales: {
                        y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                        x: { ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                    },
                }),
            });
        });
        </script>
        @endpush
    @endif
</x-app-layout>
