<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Convite de usuário. Não usa o CompanyScope global porque a aceitação
 * acontece com o visitante deslogado (busca pelo token). O isolamento por
 * empresa é feito explicitamente nos controllers de administração.
 */
class CompanyInvitation extends Model
{
    protected $fillable = [
        'company_id', 'invited_by', 'name', 'email', 'role',
        'token', 'accepted_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CompanyInvitation $invitation) {
            $invitation->token ??= Str::random(48);
            $invitation->expires_at ??= now()->addDays(7);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }
}
