<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mensalidades que se repetem sozinhas.
 *
 * Uma "série" é o conjunto de cobranças ligadas pelo recurrence_series_id
 * (o id da primeira). O gerador olha a cobrança mais recente de cada série
 * ativa e cria as que faltam até o mês seguinte ao atual — assim a próxima
 * já aparece como "a vencer" e o boleto/Pix dela pode ser anexado quando
 * existir. Roda ao abrir o Financeiro e uma vez por dia pelo agendador.
 */
class RecorrenciaDeCobrancas
{
    /** Transforma uma cobrança avulsa na primeira de uma série mensal. */
    public function ativar(Payment $cobranca): Payment
    {
        $cobranca->forceFill([
            'recurrence' => Payment::RECURRENCE_MONTHLY,
            'recurrence_series_id' => $cobranca->recurrence_series_id ?: $cobranca->id,
            'recurrence_day' => $cobranca->recurrence_day ?: $cobranca->due_date->day,
            'recurrence_ended_at' => null,
        ])->save();

        return $cobranca;
    }

    /**
     * Encerra a série: nada mais é gerado, e as cobranças futuras ainda
     * pendentes (criadas automaticamente, sem ninguém ter mexido) somem.
     * As já pagas e a do mês atual ficam — são histórico.
     */
    public function encerrar(Payment $cobranca): int
    {
        if (! $cobranca->recurrence_series_id) {
            return 0;
        }

        $serie = Payment::withoutGlobalScopes()
            ->where('company_id', $cobranca->company_id)
            ->where('recurrence_series_id', $cobranca->recurrence_series_id);

        return DB::transaction(function () use ($serie) {
            $removidas = (clone $serie)
                ->where('status', Payment::STATUS_PENDING)
                ->where('reference_month', '>', now()->format('Y-m'))
                ->whereNull('attachment_path')
                ->delete();

            (clone $serie)->update(['recurrence_ended_at' => now()->toDateString()]);

            return $removidas;
        });
    }

    /**
     * Cria as cobranças que faltam em todas as séries ativas da empresa.
     * Devolve quantas foram criadas.
     */
    public function gerar(int $companyId, ?Carbon $referencia = null): int
    {
        // Até o mês seguinte ao atual, para a próxima já aparecer.
        $limite = ($referencia ?? now())->copy()->startOfMonth()->addMonthNoOverflow();

        $series = Payment::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('recurrence', Payment::RECURRENCE_MONTHLY)
            ->whereNotNull('recurrence_series_id')
            ->whereNull('recurrence_ended_at')
            ->get()
            ->groupBy('recurrence_series_id');

        $criadas = 0;

        foreach ($series as $serieId => $cobrancas) {
            $criadas += $this->completarSerie((int) $serieId, $cobrancas->all(), $limite);
        }

        return $criadas;
    }

    /** Gera os meses que faltam de uma série, do último existente até o limite. */
    private function completarSerie(int $serieId, array $cobrancas, Carbon $limite): int
    {
        usort($cobrancas, fn (Payment $a, Payment $b) => strcmp($a->referenceMonth(), $b->referenceMonth()));

        /** @var Payment $modelo A mais recente é o molde dos próximos meses. */
        $modelo = end($cobrancas);
        $dia = $modelo->recurrence_day ?: $modelo->due_date->day;
        $mes = Carbon::createFromFormat('Y-m', $modelo->referenceMonth())->startOfMonth();
        $criadas = 0;

        while (($mes = $mes->copy()->addMonthNoOverflow())->lte($limite)) {
            $chave = $mes->format('Y-m');

            // Alguém já cadastrou esse mês na mão (era o jeito antigo)? Então
            // essa cobrança vira parte da série em vez de sair uma duplicada.
            $existente = Payment::withoutGlobalScopes()
                ->where('company_id', $modelo->company_id)
                ->where('client_id', $modelo->client_id)
                ->where('reference_month', $chave)
                ->whereRaw('LOWER(title) = ?', [mb_strtolower($modelo->title)])
                ->first();

            if ($existente) {
                if (! $existente->recurrence_series_id) {
                    $existente->forceFill([
                        'recurrence' => Payment::RECURRENCE_MONTHLY,
                        'recurrence_series_id' => $serieId,
                        'recurrence_day' => $dia,
                    ])->save();
                }
                $modelo = $existente;
                continue;
            }

            $modelo = Payment::create([
                'company_id' => $modelo->company_id,
                'client_id' => $modelo->client_id,
                'title' => $modelo->title,
                'amount' => $modelo->amount,
                'due_date' => $mes->copy()->day(min($dia, $mes->daysInMonth))->toDateString(),
                'paid_at' => null,
                'status' => Payment::STATUS_PENDING,
                'payment_method' => $modelo->payment_method,
                'reference_month' => $chave,
                'recurrence' => Payment::RECURRENCE_MONTHLY,
                'recurrence_series_id' => $serieId,
                'recurrence_day' => $dia,
                'notes' => $modelo->notes,
                'created_by' => $modelo->created_by,
            ]);

            $criadas++;
        }

        return $criadas;
    }
}
