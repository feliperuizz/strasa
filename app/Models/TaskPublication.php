<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Situação da postagem de um post numa rede, informada pelo sistema parceiro
 * através do webhook da API (POST /api/v1/posts/{id}/publications).
 */
class TaskPublication extends Model
{
    use Concerns\GuardaEmUtc;

    use BelongsToCompany;

    public const REDES = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'x' => 'X (Twitter)',
        'threads' => 'Threads',
        'pinterest' => 'Pinterest',
        'google_business' => 'Google Meu Negócio',
        'other' => 'Outra',
    ];

    public const STATUS = [
        'scheduled' => 'Agendado',
        'publishing' => 'Publicando',
        'published' => 'Publicado',
        'failed' => 'Falhou',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'company_id', 'task_id', 'api_token_id', 'network', 'status', 'external_id', 'permalink',
        'error_message', 'scheduled_for', 'published_at', 'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function apiToken(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class);
    }

    public function redeLabel(): string
    {
        return self::REDES[$this->network] ?? ucfirst($this->network);
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Classes de cor do selo (Tailwind) por situação. */
    public function tom(): string
    {
        return match ($this->status) {
            'published' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
            'failed' => 'border-rose-500/30 bg-rose-500/10 text-rose-300',
            'cancelled' => 'border-ink-600 bg-ink-800 text-slate-400',
            default => 'border-sky-500/30 bg-sky-500/10 text-sky-300',
        };
    }
}
