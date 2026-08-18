<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    use HasFactory, BelongsToCompany;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_LATE = 'late';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_PAID => 'Pago',
        self::STATUS_LATE => 'Em Atraso',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    public const METHOD_PIX = 'pix';
    public const METHOD_BOLETO = 'boleto';
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_OTHER = 'other';

    public const METHODS = [
        self::METHOD_PIX => 'Pix',
        self::METHOD_BOLETO => 'Boleto Bancário',
        self::METHOD_CREDIT_CARD => 'Cartão de Crédito',
        self::METHOD_BANK_TRANSFER => 'Transferência / TED',
        self::METHOD_CASH => 'Dinheiro',
        self::METHOD_OTHER => 'Outro',
    ];

    protected $fillable = [
        'company_id',
        'client_id',
        'title',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'reference_month',
        'recurrence',
        'notes',
        'attachment_path',
        'attachment_disk',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    /* --------------------------------------------------------------------- */
    /* Relações                                                              */
    /* --------------------------------------------------------------------- */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* --------------------------------------------------------------------- */
    /* Helpers e Acessores                                                   */
    /* --------------------------------------------------------------------- */

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isLate(): bool
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_CANCELLED) {
            return false;
        }

        return $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->isPaid()) {
            return self::STATUS_PAID;
        }

        if ($this->isCancelled()) {
            return self::STATUS_CANCELLED;
        }

        if ($this->isLate()) {
            return self::STATUS_LATE;
        }

        return self::STATUS_PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->effective_status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? ($this->payment_method ?: 'Não informado');
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format((float) $this->amount, 2, ',', '.');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk($this->attachment_disk ?: config('filesystems.attachments_disk'))
            ->url($this->attachment_path);
    }
}
