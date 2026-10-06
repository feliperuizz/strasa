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
                    <p class="text-[11.5px] text-slate-400">Métricas e faturamento</p>
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

    @php
        // Valor para um campo de média no formulário: vírgula decimal, sem
        // separador de milhar (2.2 → "2,2"; 15 → "15").
        $paraCampo = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');

        // Formulário em branco (lançar) ou o que voltou com erro de validação.
        $formMetrica = old('_form') === 'metrica';
        $formInicial = ['network' => old('network', array_key_first(\App\Models\ClientMetric::NETWORKS)), 'reference_month' => old('reference_month', \App\Support\Fuso::agora()->format('Y-m')), 'notes' => old('notes', '')];
        foreach (\App\Models\ClientMetric::FIELDS as $campo => $rotulo) {
            $formInicial[$campo] = $formMetrica ? (string) old($campo, '') : '';
        }
    @endphp

    <div class="p-4 sm:p-6 space-y-6"
         x-data="{
             modal: {{ $formMetrica ? 'true' : 'false' }},
             modalFat: {{ old('_form') === 'faturamento' ? 'true' : 'false' }},
             editando: {{ $formMetrica && old('_editando') ? (int) old('_editando') : 'null' }},
             vazio: @js(array_merge($formInicial, ['network' => array_key_first(\App\Models\ClientMetric::NETWORKS), 'reference_month' => \App\Support\Fuso::agora()->format('Y-m'), 'notes' => ''], array_fill_keys(array_keys(\App\Models\ClientMetric::FIELDS), ''))),
             form: @js($formInicial),
             urlNova: @js(route('clients.metrics.store', $client)),
             urlEditar: @js(route('metrics.update', ['metric' => '__ID__'])),
             nova() { this.editando = null; this.form = Object.assign({}, this.vazio); this.modal = true; },
             editar(linha) { this.editando = linha.id; this.form = Object.assign({}, this.vazio, linha.form); this.modal = true; },
             get acao() { return this.editando ? this.urlEditar.replace('__ID__', this.editando) : this.urlNova; },
         }"
         @abrir-metrica.window="nova()">

        {{-- Filtros: mês a mês (as métricas são o fechamento de cada mês) --}}
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
                <label class="block text-xs font-medium text-slate-400 mb-1">De (mês/ano)</label>
                <input type="month" name="de" value="{{ $filtros['de'] }}" max="{{ \App\Support\Fuso::agora()->format('Y-m') }}"
                       class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500 [color-scheme:dark]">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Até (mês/ano)</label>
                <input type="month" name="ate" value="{{ $filtros['ate'] }}" max="{{ \App\Support\Fuso::agora()->format('Y-m') }}"
                       class="rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500 [color-scheme:dark]">
            </div>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 transition">Filtrar</button>
            @if($filtros['personalizado'])
                <a href="{{ route('clients.metrics', $client) }}" class="px-2 py-2 text-sm text-slate-400 hover:text-slate-200">Limpar</a>
            @endif
            <p class="w-full text-[11.5px] text-slate-500">
                Mostrando de {{ \App\Models\ClientMetric::rotuloDe(\Illuminate\Support\Carbon::createFromFormat('Y-m-d', $filtros['de'].'-01')) }}
                a {{ \App\Models\ClientMetric::rotuloDe(\Illuminate\Support\Carbon::createFromFormat('Y-m-d', $filtros['ate'].'-01')) }}.
            </p>
        </form>

        {{-- ============================ REDES SOCIAIS ============================ --}}
        <div>
            <h2 class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Redes sociais</h2>

            @if($registros->isEmpty())
                <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 p-12 text-center">
                    <div class="text-3xl mb-3">📊</div>
                    <h3 class="text-slate-200 font-medium mb-1">Nenhuma métrica lançada ainda</h3>
                    <p class="text-sm text-slate-400 mb-4">
                        Registre os números de cada rede no fechamento do mês — o sistema calcula sozinho o
                        ganho de um mês para o outro.
                    </p>
                    @can('update', $client)
                        <button @click="nova()" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                            Lançar a primeira
                        </button>
                    @endcan
                </div>
            @else
                <div class="space-y-4">
                    {{-- Cards --}}
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Seguidores</div>
                            <div class="text-2xl sm:text-3xl font-bold text-slate-100">{{ number_format($resumo['seguidores'], 0, ',', '.') }}</div>
                            <div class="text-xs text-slate-500 mt-2">somando as redes</div>
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Ganho no período</div>
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
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Visualizações</div>
                            <div class="text-2xl sm:text-3xl font-bold text-brand-400">{{ number_format($resumo['visualizacoes'], 0, ',', '.') }}</div>
                            <div class="text-xs text-slate-500 mt-2">somadas no período</div>
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Taxa de engajamento</div>
                            @if($resumo['taxa'] === null)
                                <div class="text-2xl font-bold text-slate-500">—</div>
                                <div class="text-xs text-slate-500 mt-2">informe curtidas e seguidores</div>
                            @else
                                <div class="text-2xl sm:text-3xl font-bold text-amber-400">{{ number_format($resumo['taxa'], 2, ',', '.') }}%</div>
                                <div class="text-xs text-slate-500 mt-2">interações por seguidor</div>
                            @endif
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

                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <h3 class="text-sm font-semibold text-slate-200 mb-1">Interações médias por publicação</h3>
                            <p class="text-[11.5px] text-slate-500 mb-3">curtidas, comentários e compartilhamentos somados</p>
                            <div class="h-64"><canvas id="graficoInteracoes"></canvas></div>
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <h3 class="text-sm font-semibold text-slate-200 mb-4">Visualizações, visitas e cliques</h3>
                            <div class="h-64"><canvas id="graficoAlcance"></canvas></div>
                        </div>
                    </div>

                    {{-- Tabela --}}
                    <div class="rounded-xl border border-ink-600 bg-ink-800 overflow-hidden">
                        <div class="px-5 py-4 border-b border-ink-700">
                            <h3 class="text-sm font-semibold text-slate-200">Lançamentos ({{ $registros->count() }})</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="tabela-celular w-full text-sm whitespace-nowrap">
                                <thead class="bg-ink-900/50 text-[11px] uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold">Mês</th>
                                        <th class="px-4 py-2.5 text-left font-semibold">Rede</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Seguidores</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Curtidas</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Coment.</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Compart.</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Visualiz.</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Visitas</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Cliques</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Posts</th>
                                        <th class="px-4 py-2.5 text-right font-semibold">Taxa</th>
                                        @can('update', $client)<th class="px-4 py-2.5"></th>@endcan
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-700">
                                    @foreach($registros as $m)
                                        @php
                                            // Dados para "Editar" abrir o formulário já preenchido.
                                            $linha = ['id' => $m->id, 'form' => ['network' => $m->network, 'reference_month' => $m->mes(), 'notes' => (string) $m->notes]];
                                            foreach (\App\Models\ClientMetric::FIELDS as $campo => $rotulo) {
                                                $linha['form'][$campo] = in_array($campo, \App\Models\ClientMetric::DECIMAIS, true) ? $paraCampo($m->{$campo}) : (string) ($m->{$campo} ?? '');
                                            }
                                        @endphp
                                        <tr class="hover:bg-ink-700/30">
                                            <td data-rotulo="Mês" class="cel-metade px-4 py-2.5 text-slate-300">{{ $m->rotuloDoMes() }}</td>
                                            <td data-rotulo="Rede" class="cel-metade px-4 py-2.5">
                                                <span class="inline-flex items-center gap-1.5 text-slate-300">
                                                    <span class="h-2 w-2 rounded-full" style="background: {{ $m->networkColor() }}"></span>
                                                    {{ $m->networkLabel() }}
                                                </span>
                                            </td>
                                            <td data-rotulo="Seguidores" class="cel-terco px-4 py-2.5 text-right text-slate-200 font-medium">{{ \App\Models\ClientMetric::formatar($m->followers) }}</td>
                                            <td data-rotulo="Curtidas" class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ \App\Models\ClientMetric::formatar($m->avg_likes) }}</td>
                                            <td data-rotulo="Coment." class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ \App\Models\ClientMetric::formatar($m->avg_comments) }}</td>
                                            <td data-rotulo="Compart." class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ \App\Models\ClientMetric::formatar($m->avg_shares) }}</td>
                                            <td data-rotulo="Visualiz." class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ \App\Models\ClientMetric::formatar($m->views) }}</td>
                                            <td data-rotulo="Visitas" class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ \App\Models\ClientMetric::formatar($m->profile_visits) }}</td>
                                            <td data-rotulo="Cliques" class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ \App\Models\ClientMetric::formatar($m->link_clicks) }}</td>
                                            <td data-rotulo="Posts" class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ \App\Models\ClientMetric::formatar($m->posts_count) }}</td>
                                            <td data-rotulo="Taxa" class="cel-terco px-4 py-2.5 text-right text-slate-400">{{ $m->engagementRate() !== null ? number_format($m->engagementRate(), 2, ',', '.').'%' : '—' }}</td>
                                            @can('update', $client)
                                                <td class="px-4 py-2.5 text-right">
                                                    <button type="button" @click="editar(@js($linha))" class="mr-3 text-xs text-brand-300 hover:text-brand-200">Editar</button>
                                                    <form method="POST" action="{{ route('metrics.destroy', $m) }}" class="inline"
                                                          onsubmit="return confirm('Remover o lançamento de {{ $m->networkLabel() }} em {{ $m->rotuloDoMes() }}?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-xs text-slate-500 hover:text-rose-400">Remover</button>
                                                    </form>
                                                </td>
                                            @endcan
                                        </tr>
                                        @if($m->notes)
                                            <tr class="bg-ink-900/30">
                                                <td colspan="12" class="px-4 pb-2.5 pt-0 text-[12px] text-slate-500 italic whitespace-normal">{{ $m->notes }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ==================== FATURAMENTO DO CLIENTE ==================== --}}
        <div>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Faturamento do cliente</h2>
                    <p class="text-[11.5px] text-slate-500">
                        Quanto o negócio dele faturou — informado pelo cliente. Não é a cobrança da agência.
                    </p>
                </div>
                @can('update', $client)
                    <button @click="modalFat = true"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-ink-600 px-3 py-1.5 text-[12.5px] font-semibold text-slate-200 hover:bg-ink-700 transition">
                        ＋ Lançar mês
                    </button>
                @endcan
            </div>

            @if($faturamento['meses'] === 0)
                <div class="rounded-xl border border-dashed border-ink-600 bg-ink-800/60 p-10 text-center">
                    <div class="text-2xl mb-2">💰</div>
                    <h3 class="text-slate-200 font-medium mb-1">Nenhum faturamento lançado</h3>
                    <p class="text-sm text-slate-400 mb-4">
                        Quando o cliente compartilhar o faturamento dele, lance aqui mês a mês.<br>
                        Com o investimento em mídia junto, o sistema calcula o retorno.
                    </p>
                    @can('update', $client)
                        <button @click="modalFat = true" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                            Lançar o primeiro mês
                        </button>
                    @endcan
                </div>
            @else
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Faturamento</div>
                            <div class="text-2xl font-bold text-slate-100">R$ {{ number_format($faturamento['total'], 2, ',', '.') }}</div>
                            <div class="text-xs text-slate-500 mt-2">{{ $faturamento['meses'] }} {{ $faturamento['meses'] === 1 ? 'mês' : 'meses' }}</div>
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Crescimento</div>
                            @if($faturamento['variacao'] === null)
                                <div class="text-2xl font-bold text-slate-500">—</div>
                                <div class="text-xs text-slate-500 mt-2">precisa de 2 meses</div>
                            @else
                                <div class="text-2xl font-bold {{ $faturamento['variacao'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                    {{ $faturamento['variacao'] >= 0 ? '+' : '' }}{{ number_format($faturamento['variacao'], 1, ',', '.') }}%
                                </div>
                                <div class="text-xs text-slate-500 mt-2">do 1º ao último mês</div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Retorno (ROAS)</div>
                            @if($faturamento['roas'] === null)
                                <div class="text-2xl font-bold text-slate-500">—</div>
                                <div class="text-xs text-slate-500 mt-2">informe o investimento</div>
                            @else
                                <div class="text-2xl font-bold text-emerald-400">{{ number_format($faturamento['roas'], 2, ',', '.') }}x</div>
                                <div class="text-xs text-slate-500 mt-2">para cada R$ 1 investido</div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1 max-[379px]:tracking-normal">Média mensal</div>
                            <div class="text-2xl font-bold text-brand-400">R$ {{ number_format($faturamento['media'], 2, ',', '.') }}</div>
                            @if($faturamento['vendas'] > 0)
                                <div class="text-xs text-slate-500 mt-2">{{ number_format($faturamento['vendas'], 0, ',', '.') }} vendas no período</div>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="rounded-xl border border-ink-600 bg-ink-800 p-5 lg:col-span-2">
                            <h3 class="text-sm font-semibold text-slate-200 mb-1">Faturamento e investimento por mês</h3>
                            <p class="text-[11.5px] text-slate-500 mb-3">a distância entre as barras é o retorno gerado</p>
                            <div class="h-64"><canvas id="graficoFaturamento"></canvas></div>
                        </div>

                        <div class="rounded-xl border border-ink-600 bg-ink-800 overflow-hidden">
                            <div class="px-5 py-4 border-b border-ink-700">
                                <h3 class="text-sm font-semibold text-slate-200">Lançamentos</h3>
                            </div>
                            <div class="max-h-72 overflow-y-auto divide-y divide-ink-700">
                                @foreach($faturamento['lancamentos'] as $lanc)
                                    <div class="px-4 py-3 hover:bg-ink-700/30 group">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[13px] font-medium text-slate-200">{{ \App\Models\ClientMetric::rotuloDe($lanc->reference_month) }}</span>
                                            <span class="text-[13px] font-semibold text-slate-100">R$ {{ number_format($lanc->revenue, 2, ',', '.') }}</span>
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 text-[11px] text-slate-500">
                                            @if($lanc->ad_spend !== null)
                                                <span>invest. R$ {{ number_format($lanc->ad_spend, 2, ',', '.') }}</span>
                                            @endif
                                            @if($lanc->roas() !== null)
                                                <span class="text-emerald-400">{{ number_format($lanc->roas(), 2, ',', '.') }}x</span>
                                            @endif
                                            @if($lanc->orders)
                                                <span>{{ $lanc->orders }} vendas</span>
                                            @endif
                                            @if($lanc->averageTicket() !== null)
                                                <span>ticket R$ {{ number_format($lanc->averageTicket(), 2, ',', '.') }}</span>
                                            @endif
                                            @can('update', $client)
                                                <form method="POST" action="{{ route('revenues.destroy', $lanc) }}" class="ml-auto opacity-0 group-hover:opacity-100 transition"
                                                      onsubmit="return confirm('Remover o lançamento de {{ \App\Models\ClientMetric::rotuloDe($lanc->reference_month) }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-[11px] text-slate-500 hover:text-rose-400">remover</button>
                                                </form>
                                            @endcan
                                        </div>
                                        @if($lanc->notes)
                                            <p class="mt-1 text-[11px] text-slate-500 italic">{{ $lanc->notes }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Formulário do faturamento do cliente --}}
        @can('update', $client)
            <div x-show="modalFat" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/70 p-4 sm:p-8"
                 @click.self="modalFat = false" @keydown.escape.window="modalFat = false">
                <div class="w-full max-w-lg rounded-xl border border-ink-600 bg-ink-800 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
                        <h3 class="font-semibold text-slate-200">Faturamento do cliente</h3>
                        <button @click="modalFat = false" class="text-slate-500 hover:text-slate-200 text-xl leading-none">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('clients.revenues.store', $client) }}" class="p-5 space-y-4">
                        @csrf
                        <input type="hidden" name="_form" value="faturamento">

                        <div class="rounded-lg border border-ink-700 bg-ink-900/40 px-3 py-2 text-[12px] text-slate-400">
                            O quanto o negócio do cliente faturou no mês, informado por ele.
                            Não tem relação com as cobranças da agência no Financeiro.
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Mês de referência *</label>
                                <input type="month" name="reference_month" required max="{{ \App\Support\Fuso::agora()->format('Y-m') }}"
                                       value="{{ old('reference_month', \App\Support\Fuso::agora()->format('Y-m')) }}"
                                       class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Faturamento (R$) *</label>
                                <input type="number" name="revenue" step="0.01" min="0" required placeholder="0,00"
                                       value="{{ old('revenue') }}"
                                       class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Investimento em mídia (R$)</label>
                                <input type="number" name="ad_spend" step="0.01" min="0" placeholder="—"
                                       value="{{ old('ad_spend') }}"
                                       class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Nº de vendas</label>
                                <input type="number" name="orders" min="0" placeholder="—"
                                       value="{{ old('orders') }}"
                                       class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>

                        <p class="text-[11.5px] text-slate-500">
                            Com investimento e vendas preenchidos, o sistema calcula o ROAS e o ticket médio.
                        </p>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Observação</label>
                            <textarea name="notes" rows="2" maxlength="1000" placeholder="Ex.: mês com Black Friday"
                                      class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">{{ old('notes') }}</textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" @click="modalFat = false" class="rounded-lg border border-ink-600 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-ink-700">Cancelar</button>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        {{-- Formulário de lançamento --}}
        @can('update', $client)
            <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/70 p-4 sm:p-8"
                 @click.self="modal = false" @keydown.escape.window="modal = false">
                <div class="w-full max-w-2xl rounded-xl border border-ink-600 bg-ink-800 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-ink-700 px-5 py-4">
                        <h3 class="font-semibold text-slate-200" x-text="editando ? 'Editar métrica' : 'Lançar métrica'">Lançar métrica</h3>
                        <button @click="modal = false" class="text-slate-500 hover:text-slate-200 text-xl leading-none">&times;</button>
                    </div>

                    <form method="POST" :action="acao" action="{{ route('clients.metrics.store', $client) }}" class="p-5 space-y-4">
                        @csrf
                        <input type="hidden" name="_method" :value="editando ? 'PATCH' : 'POST'" value="POST">
                        <input type="hidden" name="_form" value="metrica">
                        <input type="hidden" name="_editando" :value="editando || ''">

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Rede social *</label>
                                <select name="network" required x-model="form.network" class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500">
                                    @foreach(\App\Models\ClientMetric::NETWORKS as $chave => $info)
                                        <option value="{{ $chave }}">{{ $info['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Mês de referência *</label>
                                <input type="month" name="reference_month" required x-model="form.reference_month" max="{{ \App\Support\Fuso::agora()->format('Y-m') }}"
                                       class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 focus:border-brand-500 focus:ring-brand-500 [color-scheme:dark]">
                            </div>
                        </div>

                        <div class="rounded-lg border border-ink-700 bg-ink-900/40 px-3 py-2 text-[12px] text-slate-400 space-y-1">
                            <p>Um lançamento por rede por mês: lançar de novo o mesmo mês <strong class="text-slate-300">atualiza</strong> os números.</p>
                            <p>Seguidores: informe o <strong class="text-slate-300">total</strong> no fechamento do mês, não o ganho — o sistema calcula a variação sozinho.</p>
                            <p>Curtidas, comentários e compartilhamentos: a <strong class="text-slate-300">média por publicação</strong>, com decimais se precisar (ex.: 2,2).</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            @foreach(\App\Models\ClientMetric::FIELDS as $campo => $rotulo)
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">{{ $rotulo }}</label>
                                    @if(in_array($campo, \App\Models\ClientMetric::DECIMAIS, true))
                                        {{-- Texto (e não number) para aceitar vírgula: "2,2". --}}
                                        <input type="text" name="{{ $campo }}" inputmode="decimal" autocomplete="off" x-model="form.{{ $campo }}" placeholder="ex.: 2,2"
                                               class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">
                                    @else
                                        <input type="number" name="{{ $campo }}" min="0" step="1" inputmode="numeric" x-model="form.{{ $campo }}" placeholder="—"
                                               class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500">
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Observação</label>
                            <textarea name="notes" rows="2" maxlength="1000" x-model="form.notes" placeholder="Ex.: campanha de lançamento no ar entre 10 e 20/11"
                                      class="w-full rounded-lg border-ink-600 bg-ink-700 text-sm text-slate-200 placeholder-slate-600 focus:border-brand-500 focus:ring-brand-500"></textarea>
                        </div>

                        @if($errors->any() && old('_form') === 'metrica')
                            <div class="rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-[13px] text-rose-300">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" @click="modal = false" class="rounded-lg border border-ink-600 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-ink-700">Cancelar</button>
                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500" x-text="editando ? 'Salvar alterações' : 'Salvar lançamento'">Salvar lançamento</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>

    @if($registros->isNotEmpty() || $faturamento['meses'] > 0)
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            Chart.defaults.color = '#94a3b8';
            Chart.defaults.borderColor = 'rgba(148,163,184,0.12)';
            Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

            var base = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { labels: { boxWidth: 12, boxHeight: 12, padding: 14, font: { size: 11 } } } },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                    x: { ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                },
            };

            /* ---------------- Redes sociais ---------------- */
            var series = @json($series);

            if (Object.keys(series).length) {
                // Eixo X comum: todos os meses presentes, em ordem cronológica.
                var datas = [];
                var rotuloDoMes = {};
                Object.values(series).forEach(function (rede) {
                    rede.pontos.forEach(function (p) {
                        if (datas.indexOf(p.mes) === -1) { datas.push(p.mes); }
                        rotuloDoMes[p.mes] = p.rotulo;
                    });
                });
                datas.sort();

                var rotulos = datas.map(function (mes) { return rotuloDoMes[mes]; });

                /** Um dataset por rede, alinhado ao eixo de meses. */
                function porRede(campo) {
                    return Object.values(series).map(function (rede) {
                        var mapa = {};
                        rede.pontos.forEach(function (p) { mapa[p.mes] = p[campo]; });

                        return {
                            label: rede.label,
                            data: datas.map(function (d) { return mapa[d] !== undefined ? mapa[d] : null; }),
                            borderColor: rede.cor,
                            backgroundColor: rede.cor + '55',
                            borderWidth: 2,
                            tension: 0.35,
                            spanGaps: true,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        };
                    });
                }

                /** Soma um campo entre todas as redes, por mês (com decimais). */
                function somaPorData(campo) {
                    return datas.map(function (d) {
                        var total = null;
                        Object.values(series).forEach(function (rede) {
                            rede.pontos.forEach(function (p) {
                                if (p.mes === d && p[campo] !== null && p[campo] !== undefined) {
                                    total = (total || 0) + Number(p[campo]);
                                }
                            });
                        });
                        return total === null ? null : Math.round(total * 100) / 100;
                    });
                }

                new Chart(document.getElementById('graficoSeguidores'), {
                    type: 'line',
                    data: { labels: rotulos, datasets: porRede('seguidores') },
                    options: Object.assign({}, base, {
                        scales: {
                            y: { beginAtZero: false, ticks: { font: { size: 11 } } },
                            x: base.scales.x,
                        },
                    }),
                });

                new Chart(document.getElementById('graficoGanho'), {
                    type: 'bar',
                    data: { labels: rotulos, datasets: porRede('ganho') },
                    options: base,
                });

                // Interações empilhadas: mostra a composição do engajamento.
                new Chart(document.getElementById('graficoInteracoes'), {
                    type: 'bar',
                    data: {
                        labels: rotulos,
                        datasets: [
                            { label: 'Curtidas', data: somaPorData('curtidas'), backgroundColor: '#38bdf8' },
                            { label: 'Comentários', data: somaPorData('comentarios'), backgroundColor: '#a78bfa' },
                            { label: 'Compartilhamentos', data: somaPorData('compartilhamentos'), backgroundColor: '#34d399' },
                        ],
                    },
                    options: Object.assign({}, base, {
                        scales: {
                            y: { beginAtZero: true, stacked: true, ticks: { font: { size: 11 } } },
                            x: { stacked: true, ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                        },
                    }),
                });

                new Chart(document.getElementById('graficoAlcance'), {
                    type: 'line',
                    data: {
                        labels: rotulos,
                        datasets: [
                            { label: 'Visualizações', data: somaPorData('visualizacoes'), borderColor: '#38bdf8', backgroundColor: '#38bdf822', borderWidth: 2, tension: 0.35, fill: true, spanGaps: true },
                            { label: 'Visitas ao perfil', data: somaPorData('visitas'), borderColor: '#fbbf24', backgroundColor: '#fbbf2422', borderWidth: 2, tension: 0.35, fill: true, spanGaps: true },
                            { label: 'Cliques no link', data: somaPorData('cliques'), borderColor: '#f472b6', backgroundColor: '#f472b622', borderWidth: 2, tension: 0.35, fill: true, spanGaps: true },
                        ],
                    },
                    options: base,
                });
            }

            /* ---------------- Faturamento ---------------- */
            var faturamento = @json($faturamento['pontos']);

            if (faturamento.length) {
                new Chart(document.getElementById('graficoFaturamento'), {
                    type: 'bar',
                    data: {
                        labels: faturamento.map(function (p) { return p.rotulo; }),
                        datasets: [
                            { label: 'Faturamento', data: faturamento.map(function (p) { return p.faturamento; }), backgroundColor: '#34d399' },
                            { label: 'Investimento', data: faturamento.map(function (p) { return p.investimento; }), backgroundColor: '#f472b6' },
                        ],
                    },
                    options: Object.assign({}, base, {
                        plugins: {
                            legend: base.plugins.legend,
                            tooltip: {
                                callbacks: {
                                    label: function (ctx) {
                                        if (ctx.parsed.y === null) {
                                            return ctx.dataset.label + ': não informado';
                                        }
                                        return ctx.dataset.label + ': R$ ' +
                                            ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                    },
                                },
                            },
                        },
                        // Barras LADO A LADO, não empilhadas: faturamento e
                        // investimento são grandezas a comparar, não partes de
                        // um todo — empilhar somaria uma na outra e mentiria.
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: { size: 11 },
                                    callback: function (v) { return 'R$ ' + v.toLocaleString('pt-BR'); },
                                },
                            },
                            x: { ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                        },
                    }),
                });
            }
        });
        </script>
        @endpush
    @endif
</x-app-layout>
