<?php

namespace App\Database;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Datas e horas usadas em consultas vão ao banco em UTC.
 *
 * O sistema roda no horário de Brasília, mas o banco guarda em UTC (ver
 * App\Models\Concerns\GuardaEmUtc). Sem isto, um where('created_at', '>=',
 * $inicioDoDia) compararia 00:00 de Brasília com valores em UTC — 3 horas
 * de diferença. Texto não é tocado: whereDate('publish_date', '2026-10-02')
 * e afins continuam iguais.
 */
trait ConvertsDatesToUtc
{
    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $chave => $valor) {
            if ($valor instanceof DateTimeInterface) {
                $bindings[$chave] = Carbon::instance($valor)->setTimezone('UTC');
            }
        }

        return parent::prepareBindings($bindings);
    }
}
