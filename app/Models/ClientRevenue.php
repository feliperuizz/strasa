<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Faturamento mensal do próprio cliente.
 *
 * Informado por ele e lançado pela equipe. Não confundir com Payment, que é
 * a cobrança da agência: aqui é o resultado do negócio DELE, usado para
 * mostrar se o trabalho está gerando retorno.
 */
class ClientRevenue extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'client_id', 'reference_month',
        'revenue', 'ad_spend', 'orders', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'reference_month' => 'date',
            'revenue' => 'decimal:2',
            'ad_spend' => 'decimal:2',
            'orders' => 'integer',
        ];
    }

    /* Relações ------------------------------------------------------------ */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* Helpers ------------------------------------------------------------- */

    /**
     * ROAS: quanto o cliente faturou para cada real investido em mídia.
     * É o número que responde "está valendo a pena?".
     */
    public function roas(): ?float
    {
        if (! $this->ad_spend || (float) $this->ad_spend <= 0) {
            return null;
        }

        return round((float) $this->revenue / (float) $this->ad_spend, 2);
    }

    /** Ticket médio: faturamento dividido pelo número de vendas. */
    public function averageTicket(): ?float
    {
        if (! $this->orders) {
            return null;
        }

        return round((float) $this->revenue / $this->orders, 2);
    }

    public function monthLabel(): string
    {
        return $this->reference_month->translatedFormat('M/y');
    }
}
