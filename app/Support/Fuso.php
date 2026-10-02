<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * "Agora" e "hoje" no horário de Brasília.
 *
 * O servidor roda em UTC: now() e today() viram o dia às 21h de Brasília,
 * e um aviso marcado para 10:00 saía às 07:00. Use isto sempre que a
 * pergunta for "que horas/que dia é para quem usa o sistema".
 *
 * Datas e horas que vêm dos modelos já chegam em Brasília
 * (App\Models\Concerns\GuardaEmUtc); datas sem hora (publish_date,
 * due_date) são só o dia, e comparam com hoje() abaixo.
 */
final class Fuso
{
    public const BRASILIA = 'America/Sao_Paulo';

    /** Data e hora de agora em Brasília (para horários e intervalos). */
    public static function agora(): Carbon
    {
        return Carbon::now(self::BRASILIA);
    }

    /**
     * O dia de hoje no calendário de Brasília, à meia-noite no fuso do
     * servidor — o mesmo formato das datas sem hora que vêm do banco
     * (publish_date, due_date), para comparar com ->lt(), ->isSameDay() etc.
     */
    public static function hoje(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', self::hojeTexto())->startOfDay();
    }

    /** Hoje em Brasília como "AAAA-MM-DD" (para consultas e campos de data). */
    public static function hojeTexto(): string
    {
        return self::agora()->toDateString();
    }

    /** A data (sem hora) é hoje, no calendário de Brasília. */
    public static function ehHoje($data): bool
    {
        return $data !== null && $data->format('Y-m-d') === self::hojeTexto();
    }

    /** A data (sem hora) é amanhã, no calendário de Brasília. */
    public static function ehAmanha($data): bool
    {
        return $data !== null && $data->format('Y-m-d') === self::agora()->addDay()->toDateString();
    }

    /** A data (sem hora) já passou: é de ontem para trás, em Brasília. */
    public static function jaPassou($data): bool
    {
        return $data !== null && $data->format('Y-m-d') < self::hojeTexto();
    }
}
