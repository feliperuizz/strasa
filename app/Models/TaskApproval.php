<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma submissão de card para o painel do cliente.
 *
 * Cada reenvio depois de um ajuste vira uma nova `round`, então o histórico
 * de idas e vindas de cada peça fica preservado.
 */
class TaskApproval extends Model
{
    use BelongsToCompany;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::PENDING => 'Aguardando',
        self::APPROVED => 'Aprovado',
        self::REJECTED => 'Ajuste pedido',
    ];

    protected $fillable = [
        'company_id', 'client_id', 'task_id', 'round', 'status',
        'submitted_at', 'submitted_by', 'origin_column_id',
        'responded_at', 'reviewer_name', 'feedback',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'responded_at' => 'datetime',
            'round' => 'integer',
        ];
    }

    /* Relações ------------------------------------------------------------ */

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function originColumn(): BelongsTo
    {
        return $this->belongsTo(Column::class, 'origin_column_id');
    }

    /* Scopes -------------------------------------------------------------- */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeAnswered(Builder $query): Builder
    {
        return $query->whereIn('status', [self::APPROVED, self::REJECTED]);
    }

    /* Helpers ------------------------------------------------------------- */

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::REJECTED;
    }
}
