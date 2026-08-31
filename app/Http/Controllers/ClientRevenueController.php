<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientRevenue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lançamento do faturamento do próprio cliente.
 *
 * Separado do FinancialController de propósito: lá é a cobrança da agência
 * (se o cliente pagou o nosso boleto), aqui é o resultado do negócio dele.
 */
class ClientRevenueController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $dados = $this->validar($request);

        // Relançar o mesmo mês corrige o valor em vez de duplicar.
        ClientRevenue::updateOrCreate(
            [
                'client_id' => $client->id,
                'reference_month' => $dados['reference_month'],
            ],
            $dados + [
                'company_id' => $client->company_id,
                'created_by' => $request->user()->id,
            ]
        );

        return back()->with('status', 'Faturamento do cliente registrado.');
    }

    public function destroy(Request $request, ClientRevenue $revenue): RedirectResponse
    {
        $this->authorize('update', $revenue->client);

        $revenue->delete();

        return back()->with('status', 'Lançamento removido.');
    }

    /* --------------------------------------------------------------------- */

    private function validar(Request $request): array
    {
        $dados = $request->validate([
            // O formulário manda "2026-08"; normalizamos para o primeiro dia.
            'reference_month' => ['required', 'date_format:Y-m'],
            'revenue' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'ad_spend' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'orders' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $dados['reference_month'] = $dados['reference_month'].'-01';

        return $dados;
    }
}
