<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\RecorrenciaDeCobrancas;
use Illuminate\Console\Command;

class GerarRecorrenciasFinanceiras extends Command
{
    protected $signature = 'financeiro:recorrencias';

    protected $description = 'Cria as cobranças mensais que faltam (até o mês seguinte) em todas as empresas';

    public function handle(RecorrenciaDeCobrancas $recorrencia): int
    {
        $total = 0;

        foreach (Company::query()->pluck('id') as $companyId) {
            $criadas = $recorrencia->gerar((int) $companyId);
            $total += $criadas;

            if ($criadas) {
                $this->line("empresa {$companyId}: {$criadas} cobrança(s) criada(s)");
            }
        }

        $this->info($total ? "{$total} cobrança(s) criada(s)." : 'Nada a criar: todas as mensalidades já estão em dia.');

        return self::SUCCESS;
    }
}
