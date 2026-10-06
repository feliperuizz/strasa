<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientMetric;
use App\Models\ClientRevenue;
use App\Support\Fuso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Métricas de redes sociais por cliente, lançadas à mão no fechamento de
 * cada mês.
 *
 * Um lançamento por rede por mês: o registro guarda o dia 1 do mês de
 * referência. Relançar o mesmo mês atualiza em vez de duplicar — e
 * consolida lançamentos antigos daquele mês (de antes do fechamento mensal,
 * quando se escolhia o dia).
 *
 * DESEMPENHO: a tela inteira sai de UMA consulta. As séries dos gráficos, os
 * cards de resumo e a tabela são derivados em memória da mesma coleção — nada
 * de uma query por rede ou por card.
 */
class ClientMetricController extends Controller
{
    /** Meses mostrados quando ninguém escolheu o período. */
    private const MESES_PADRAO = 12;

    public function index(Request $request, Client $client)
    {
        $this->authorize('view', $client);

        $filtros = $request->validate([
            'network' => ['nullable', 'string', 'max:30'],
            'de' => ['nullable', 'date_format:Y-m'],
            'ate' => ['nullable', 'date_format:Y-m'],
        ]);

        $rede = $filtros['network'] ?? null;
        $ate = $filtros['ate'] ?? Fuso::agora()->format('Y-m');
        $de = $filtros['de'] ?? Carbon::createFromFormat('Y-m-d', $ate.'-01')->subMonths(self::MESES_PADRAO - 1)->format('Y-m');

        if ($de > $ate) {
            [$de, $ate] = [$ate, $de];
        }

        $registros = $this->umPorMes(
            ClientMetric::query()
                ->where('client_id', $client->id)
                ->network($rede)
                ->entreMeses($de, $ate)
                ->with('creator:id,name')
                ->orderBy('reference_date')
                ->orderBy('id')
                ->get()
        );

        return view('clients.metrics', [
            'client' => $client,
            'registros' => $registros->sortByDesc(fn (ClientMetric $m) => $m->mes().'|'.$m->network)->values(),
            'series' => $this->series($registros),
            'resumo' => $this->resumo($registros),
            'redesUsadas' => $registros->pluck('network')->unique()->values(),
            'faturamento' => $this->faturamento($client, $de, $ate),
            'filtros' => [
                'network' => $rede,
                'de' => $de,
                'ate' => $ate,
                'personalizado' => $rede || isset($filtros['de']) || isset($filtros['ate']),
            ],
        ]);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $dados = $this->validar($request);

        DB::transaction(function () use ($dados, $client, $request) {
            $doMes = $this->lancamentosDoMes($client->id, $dados['network'], $dados['reference_month']);

            // Relançar o mesmo mês atualiza (é o que a equipe espera ao
            // corrigir um número) e junta os lançamentos antigos do mês.
            $manter = $doMes->shift();
            $doMes->each->delete();

            $valores = $this->valores($dados);

            if ($manter) {
                $manter->update($valores);
            } else {
                ClientMetric::create($valores + [
                    'client_id' => $client->id,
                    'company_id' => $client->company_id,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('status', 'Métrica de '.ClientMetric::rotuloDe(Carbon::createFromFormat('Y-m-d', $dados['reference_month'].'-01')).' registrada.');
    }

    public function update(Request $request, ClientMetric $metric): RedirectResponse
    {
        $this->authorize('update', $metric->client);

        $dados = $this->validar($request);
        $mesmoLugar = $dados['network'] === $metric->network && $dados['reference_month'] === $metric->mes();

        $outros = $this->lancamentosDoMes($metric->client_id, $dados['network'], $dados['reference_month'])
            ->reject(fn (ClientMetric $m) => $m->id === $metric->id);

        // Mudou de rede ou de mês e lá já tem lançamento: não sobrescreve
        // o outro sem querer.
        if (! $mesmoLugar && $outros->isNotEmpty()) {
            return back()
                ->withErrors(['reference_month' => 'Já existe lançamento de '.(ClientMetric::NETWORKS[$dados['network']]['label'] ?? $dados['network'])
                    .' em '.$outros->first()->rotuloDoMes().'. Edite aquele lançamento.'])
                ->withInput();
        }

        DB::transaction(function () use ($metric, $dados, $outros) {
            $outros->each->delete();
            $metric->update($this->valores($dados));
        });

        return back()->with('status', 'Métrica atualizada.');
    }

    public function destroy(Request $request, ClientMetric $metric): RedirectResponse
    {
        $this->authorize('update', $metric->client);

        // A tela mostra um lançamento por rede por mês: remover tira o mês
        // inteiro (inclusive lançamentos antigos do mesmo mês).
        $this->lancamentosDoMes($metric->client_id, $metric->network, $metric->mes())->each->delete();

        return back()->with('status', 'Métrica removida.');
    }

    /* --------------------------------------------------------------------- */

    private function validar(Request $request): array
    {
        // Médias aceitam o jeito brasileiro: "2,2" → 2.2, "1.234,5" → 1234.5,
        // "1.234" → 1234 (ponto seguido de 3 dígitos é milhar: decimal aqui
        // tem no máximo 2 casas). "2.2" com ponto também vale.
        foreach (ClientMetric::DECIMAIS as $campo) {
            $valor = $request->input($campo);

            if (! is_string($valor) || trim($valor) === '') {
                continue;
            }

            $valor = str_replace(' ', '', trim($valor));

            if (str_contains($valor, ',')) {
                $valor = str_replace(['.', ','], ['', '.'], $valor);
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $valor)) {
                $valor = str_replace('.', '', $valor);
            }

            $request->merge([$campo => $valor]);
        }

        $inteiro = ['nullable', 'integer', 'min:0', 'max:4294967295'];
        $decimal = ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'];

        return $request->validate([
            'network' => ['required', 'string', 'in:'.implode(',', array_keys(ClientMetric::NETWORKS))],
            'reference_month' => ['required', 'date_format:Y-m', 'before_or_equal:'.Fuso::agora()->format('Y-m')],
            'followers' => $inteiro,
            'avg_likes' => $decimal,
            'avg_comments' => $decimal,
            'avg_shares' => $decimal,
            'views' => $inteiro,
            'profile_visits' => $inteiro,
            'link_clicks' => $inteiro,
            'posts_count' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'reference_month.required' => 'Escolha o mês de referência.',
            'reference_month.date_format' => 'Escolha o mês de referência (mês/ano).',
            'reference_month.before_or_equal' => 'O mês de referência não pode ser no futuro.',
            'network.required' => 'Escolha a rede social.',
            'network.in' => 'Escolha a rede social.',
            '*.integer' => ':attribute: use um número inteiro.',
            '*.numeric' => ':attribute: use um número (ex.: 2,2).',
            '*.decimal' => ':attribute: use no máximo 2 casas decimais (ex.: 2,25).',
            '*.min' => ':attribute não pode ser negativo.',
            '*.max' => ':attribute: valor alto demais.',
        ], ClientMetric::FIELDS + ['notes' => 'Observação', 'reference_month' => 'Mês de referência']);
    }

    /** O que vai para o banco: o mês vira o dia 1 do mês de referência. */
    private function valores(array $dados): array
    {
        return Arr::except($dados, 'reference_month') + ['reference_date' => $dados['reference_month'].'-01'];
    }

    /**
     * Lançamentos de uma rede num mês, do mais recente para o mais antigo.
     * Normalmente um só; mais de um só existe em meses de antes do
     * fechamento mensal.
     */
    private function lancamentosDoMes(int $clientId, string $rede, string $mes): Collection
    {
        $inicio = Carbon::createFromFormat('Y-m-d', $mes.'-01');

        return ClientMetric::query()
            ->where('client_id', $clientId)
            ->where('network', $rede)
            ->whereBetween('reference_date', [$inicio->toDateString(), $inicio->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('reference_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Um lançamento por rede por mês: em meses antigos com mais de uma
     * leitura, vale a mais recente (o fechamento).
     */
    private function umPorMes(Collection $registros): Collection
    {
        return $registros
            ->groupBy(fn (ClientMetric $m) => $m->network.'|'.$m->mes())
            ->map(fn (Collection $doMes) => $doMes->last())
            ->sortBy(fn (ClientMetric $m) => $m->mes())
            ->values();
    }

    /**
     * Faturamento do PRÓPRIO CLIENTE, mês a mês, no mesmo período das métricas.
     *
     * Vem dos lançamentos manuais (ClientRevenue), informados pelo cliente —
     * não da tabela `payments`, que é a cobrança da agência. São coisas
     * diferentes: uma mede o resultado do negócio dele, a outra mede se ele
     * nos pagou.
     *
     * Uma consulta; os totais e derivados saem em memória.
     *
     * @return array<string, mixed>
     */
    private function faturamento(Client $client, string $de, string $ate): array
    {
        $lancamentos = ClientRevenue::query()
            ->where('client_id', $client->id)
            ->whereDate('reference_month', '>=', $de.'-01')
            ->whereDate('reference_month', '<=', $ate.'-01')
            ->orderBy('reference_month')
            ->get();

        $pontos = $lancamentos->map(fn (ClientRevenue $r) => [
            'mes' => $r->reference_month->format('Y-m'),
            'rotulo' => ClientMetric::rotuloDe($r->reference_month, true),
            'faturamento' => (float) $r->revenue,
            'investimento' => $r->ad_spend !== null ? (float) $r->ad_spend : null,
            'vendas' => $r->orders,
            'roas' => $r->roas(),
            'ticket' => $r->averageTicket(),
        ])->values();

        $total = (float) $lancamentos->sum('revenue');
        $investido = (float) $lancamentos->sum('ad_spend');

        // Variação entre o primeiro e o último mês lançado: é o que responde
        // "o faturamento dele cresceu desde que começamos?".
        $variacao = null;
        if ($lancamentos->count() > 1) {
            $primeiro = (float) $lancamentos->first()->revenue;
            $ultimo = (float) $lancamentos->last()->revenue;

            if ($primeiro > 0) {
                $variacao = round(($ultimo - $primeiro) / $primeiro * 100, 1);
            }
        }

        return [
            'pontos' => $pontos,
            'total' => $total,
            'investido' => $investido,
            'roas' => $investido > 0 ? round($total / $investido, 2) : null,
            'media' => $lancamentos->isNotEmpty() ? $total / $lancamentos->count() : 0.0,
            'variacao' => $variacao,
            'vendas' => (int) $lancamentos->sum('orders'),
            'meses' => $lancamentos->count(),
            'lancamentos' => $lancamentos->sortByDesc('reference_month')->values(),
        ];
    }

    /**
     * Séries para os gráficos, uma por rede, um ponto por mês.
     *
     * Além dos totais, calcula o GANHO entre meses consecutivos — é o
     * número que a equipe quer ver ("quantos seguidores ganhamos no mês").
     *
     * @return array<string, mixed>
     */
    private function series(Collection $registros): array
    {
        $porRede = [];

        foreach ($registros->groupBy('network') as $rede => $leituras) {
            $leituras = $leituras->sortBy(fn (ClientMetric $m) => $m->mes())->values();

            $pontos = [];
            $anterior = null;

            foreach ($leituras as $l) {
                $ganho = ($anterior !== null && $l->followers !== null && $anterior->followers !== null)
                    ? $l->followers - $anterior->followers
                    : null;

                $pontos[] = [
                    'mes' => $l->mes(),
                    'rotulo' => $l->rotuloDoMes(true),
                    'seguidores' => $l->followers,
                    'ganho' => $ganho,
                    'curtidas' => $l->avg_likes,
                    'comentarios' => $l->avg_comments,
                    'compartilhamentos' => $l->avg_shares,
                    'interacoes' => $l->engagementPerPost(),
                    'visualizacoes' => $l->views,
                    'visitas' => $l->profile_visits,
                    'cliques' => $l->link_clicks,
                    'taxa' => $l->engagementRate(),
                ];

                if ($l->followers !== null) {
                    $anterior = $l;
                }
            }

            $porRede[$rede] = [
                'label' => ClientMetric::NETWORKS[$rede]['label'] ?? ucfirst($rede),
                'cor' => ClientMetric::NETWORKS[$rede]['color'] ?? '#64748B',
                'pontos' => $pontos,
            ];
        }

        return $porRede;
    }

    /**
     * Cartões do topo: situação atual e variação no período exibido.
     *
     * @return array<string, mixed>
     */
    private function resumo(Collection $registros): array
    {
        $seguidoresAtuais = 0;
        $ganhoPeriodo = 0;
        $temBase = false;

        foreach ($registros->groupBy('network') as $leituras) {
            $comSeguidores = $leituras->whereNotNull('followers')->sortBy(fn (ClientMetric $m) => $m->mes())->values();

            if ($comSeguidores->isEmpty()) {
                continue;
            }

            $seguidoresAtuais += $comSeguidores->last()->followers;

            if ($comSeguidores->count() > 1) {
                $ganhoPeriodo += $comSeguidores->last()->followers - $comSeguidores->first()->followers;
                $temBase = true;
            }
        }

        // A taxa de engajamento média do período usa o último mês de cada
        // rede — média de médias antigas não diz nada útil.
        $taxas = $registros->groupBy('network')
            ->map(fn ($l) => $l->sortBy(fn (ClientMetric $m) => $m->mes())->last()?->engagementRate())
            ->filter()
            ->values();

        return [
            'seguidores' => $seguidoresAtuais,
            'ganho' => $temBase ? $ganhoPeriodo : null,
            'visualizacoes' => (int) $registros->sum('views'),
            'taxa' => $taxas->isNotEmpty() ? round($taxas->avg(), 2) : null,
            'publicacoes' => (int) $registros->sum('posts_count'),
            'visitas' => (int) $registros->sum('profile_visits'),
            'cliques' => (int) $registros->sum('link_clicks'),
            'lancamentos' => $registros->count(),
        ];
    }
}
