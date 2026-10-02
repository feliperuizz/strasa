<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

/**
 * Datas e horas: o banco guarda em UTC, a tela mostra em Brasília.
 *
 * O servidor roda em UTC e o banco tem anos de registros gravados assim —
 * nada é reescrito. Mas quem usa o sistema está em Brasília: mostrar
 * created_at, responded_at etc. direto dava 3 horas a mais. Este trait faz
 * a ponte em cada modelo:
 *
 * - lendo do banco: "2026-10-02 21:00:00" (UTC) vira 18:00 de Brasília, então
 *   ->format('d/m H:i'), ->isToday() e afins já saem no horário certo;
 * - gravando: qualquer data/hora é convertida para UTC antes de ir ao banco.
 *
 * Datas sem hora (publish_date, vencimentos) não mudam: "2026-10-02" é o
 * mesmo dia nos dois fusos. Consultas com datas (where, whereBetween)
 * também vão em UTC, pela conexão (App\Database\ConvertsDatesToUtc).
 */
trait GuardaEmUtc
{
    /** Fuso de quem usa o sistema (o servidor continua em UTC). */
    public const FUSO_DE_EXIBICAO = 'America/Sao_Paulo';

    protected function asDateTime($value)
    {
        // Data e hora em texto vêm do banco: estão em UTC.
        if (is_string($value) && ! is_numeric($value) && ! $this->isStandardDateFormat($value)) {
            try {
                $data = Date::createFromFormat($this->getDateFormat(), $value, 'UTC');
            } catch (InvalidArgumentException) {
                $data = false;
            }

            return ($data ?: Date::parse($value, 'UTC'))->setTimezone(self::FUSO_DE_EXIBICAO);
        }

        return parent::asDateTime($value);
    }

    /**
     * Data sem hora (cast "date": publish_date, due_date) é um dia do
     * calendário, não um instante: nunca muda de fuso. Sem isto, um
     * vencimento recém-definido podia voltar um dia antes de ser salvo.
     */
    protected function asDate($value)
    {
        return parent::asDateTime($value)->startOfDay();
    }

    public function setAttribute($key, $value)
    {
        // Carbon atribuído a uma data sem hora vale pelo dia que ele mostra
        // (22:30 do dia 02 em Brasília = dia 02), não pelo dia em UTC.
        if ($value instanceof \DateTimeInterface && $this->ehDataSemHora($key)) {
            $value = $value->format('Y-m-d');
        }

        return parent::setAttribute($key, $value);
    }

    private function ehDataSemHora(string $key): bool
    {
        $cast = $this->getCasts()[$key] ?? null;

        return is_string($cast) && preg_match('/^(immutable_)?date(:|$)/', $cast) === 1;
    }

    public function fromDateTime($value)
    {
        if (empty($value)) {
            return $value;
        }

        // Texto atribuído no código (ex.: um campo de formulário) está no
        // horário do sistema; Carbon já sabe o próprio fuso. Os dois vão
        // para o banco em UTC.
        $data = is_string($value) && ! is_numeric($value) ? parent::asDateTime($value) : $this->asDateTime($value);

        return $data->copy()->setTimezone('UTC')->format($this->getDateFormat());
    }
}
