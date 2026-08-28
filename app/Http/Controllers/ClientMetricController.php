<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientMetric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Métricas de redes sociais por cliente, lançadas à mão.
 *
 * DESEMPENHO: a tela inteira sai de UMA consulta. As séries dos gráficos, os
 * cards de resumo e a tabela são derivados em memória da mesma coleção — nada
 * de uma query por rede ou por card.
 */
class ClientMetricController extends Controller
{
    public function index(Request $request, Client $client)
    {
        $this->authorize('view', $client);

        $filtros = $request->validate([
            'network' => ['nullable', 'string', 'max:30'],
            'periodo' => ['nullable', 'in:90,180,365,todos'],
        ]);

        $periodo = $filtros['periodo'] ?? '365';
        $rede = $filtros['network'] ?? null;

        $registros = ClientMetric::query()
            ->where('client_id', $client->id)
            ->network($rede)
            ->when($periodo !== 'todos', fn ($q) => $q->whereDate('reference_date', '>=', now()->subDays((int) $periodo)))
            ->with('creator:id,name')
            ->orderBy('reference_date')
            ->get();

        return view('clients.metrics', [
            'client' => $client,
            'registros' => $registros->sortByDesc('reference_date')->values(),
            'series' => $this->series($registros),
            'resumo' => $this->resumo($registros),
            'redesUsadas' => $registros->pluck('network')->unique()->values(),
            'filtros' => ['network' => $rede, 'periodo' => $periodo],
        ]);
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $dados = $this->validar($request);

        // Relançar a mesma rede na mesma data atualiza em vez de duplicar —
        // é o comportamento que a equipe espera ao corrigir um número.
        ClientMetric::updateOrCreate(
            [
                'client_id' => $client->id,
                'network' => $dados['network'],
                'reference_date' => $dados['reference_date'],
            ],
            $dados + [
                'company_id' => $client->company_id,
                'created_by' => $request->user()->id,
            ]
        );

        return back()->with('status', 'Métrica registrada.');
    }

    public function update(Request $request, ClientMetric $metric): RedirectResponse
    {
        $this->authorize('update', $metric->client);

        $metric->update($this->validar($request));

        return back()->with('status', 'Métrica atualizada.');
    }

    public function destroy(Request $request, ClientMetric $metric): RedirectResponse
    {
        $this->authorize('update', $metric->client);

        $metric->delete();

        return back()->with('status', 'Métrica removida.');
    }

    /* --------------------------------------------------------------------- */

    private function validar(Request $request): array
    {
        return $request->validate([
            'network' => ['required', 'string', 'in:'.implode(',', array_keys(ClientMetric::NETWORKS))],
            'reference_date' => ['required', 'date', 'before_or_equal:today'],
            'followers' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'reach' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'impressions' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'engagement' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'profile_visits' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'link_clicks' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'posts_count' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Séries para os gráficos, uma por rede.
     *
     * Além dos totais, calcula o GANHO entre leituras consecutivas — é o
     * número que a equipe quer ver ("quantos seguidores ganhamos no mês").
     *
     * @return array<string, mixed>
     */
    private function series($registros): array
    {
        $porRede = [];

        foreach ($registros->groupBy('network') as $rede => $leituras) {
            $leituras = $leituras->sortBy('reference_date')->values();

            $pontos = [];
            $anterior = null;

            foreach ($leituras as $l) {
                $ganho = ($anterior !== null && $l->followers !== null && $anterior->followers !== null)
                    ? $l->followers - $anterior->followers
                    : null;

                $pontos[] = [
                    'data' => $l->reference_date->format('d/m/Y'),
                    'iso' => $l->reference_date->toDateString(),
                    'seguidores' => $l->followers,
                    'ganho' => $ganho,
                    'alcance' => $l->reach,
                    'impressoes' => $l->impressions,
                    'engajamento' => $l->engagement,
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
    private function resumo($registros): array
    {
        $seguidoresAtuais = 0;
        $ganhoPeriodo = 0;
        $temBase = false;

        foreach ($registros->groupBy('network') as $leituras) {
            $comSeguidores = $leituras->whereNotNull('followers')->sortBy('reference_date')->values();

            if ($comSeguidores->isEmpty()) {
                continue;
            }

            $seguidoresAtuais += $comSeguidores->last()->followers;

            if ($comSeguidores->count() > 1) {
                $ganhoPeriodo += $comSeguidores->last()->followers - $comSeguidores->first()->followers;
                $temBase = true;
            }
        }

        return [
            'seguidores' => $seguidoresAtuais,
            'ganho' => $temBase ? $ganhoPeriodo : null,
            'alcance' => (int) $registros->sum('reach'),
            'engajamento' => (int) $registros->sum('engagement'),
            'publicacoes' => (int) $registros->sum('posts_count'),
            'lancamentos' => $registros->count(),
        ];
    }
}
