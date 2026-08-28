<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    use BelongsToCompany;

    /** Colunas padrão usadas quando o cliente não define um template próprio. */
    public const DEFAULT_COLUMNS = [
        ['name' => 'Documentos',         'key' => 'documents',   'color' => '#64748b'],
        ['name' => 'A Fazer',            'key' => 'todo',        'color' => '#3b82f6'],
        ['name' => 'Em Andamento',       'key' => 'in_progress', 'color' => '#eab308'],
        ['name' => 'Fila de Publicação', 'key' => 'queue',       'color' => '#a855f7'],
        ['name' => 'Postado',            'key' => 'posted',      'color' => '#22c55e', 'marks_published' => true],
        ['name' => 'Rejeitado',          'key' => 'rejected',    'color' => '#ef4444', 'requires_rejection_reason' => true],
    ];

    protected $fillable = [
        'company_id', 'name', 'slug', 'segment',
        'color', 'bg_type', 'bg_color', 'bg_gradient',
        'logo_path', 'logo_disk', 'social_networks', 'default_columns', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'social_networks' => 'array',
            'default_columns' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * A sidebar é cacheada pelo ShareTenantData; qualquer mudança em cliente
     * precisa derrubar esse cache, senão o menu mostra dado velho.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $c) => \App\Http\Middleware\ShareTenantData::esquecerSidebar($c->company_id));
        static::deleted(fn (self $c) => \App\Http\Middleware\ShareTenantData::esquecerSidebar($c->company_id));
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function portal(): HasOne
    {
        return $this->hasOne(ClientPortal::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(TaskApproval::class);
    }

    /* Scopes ------------------------------------------------------------- */

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /* Helpers ------------------------------------------------------------ */

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function columnsTemplate(): array
    {
        return ! empty($this->default_columns) ? $this->default_columns : self::DEFAULT_COLUMNS;
    }

    public function getBackgroundStyleAttribute(): string
    {
        if ($this->bg_type === 'gradient' && ! empty($this->bg_gradient)) {
            return "background: {$this->bg_gradient};";
        }

        if ($this->bg_type === 'color' && ! empty($this->bg_color)) {
            return "background-color: {$this->bg_color};";
        }

        return '';
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        try {
            return Storage::disk($this->logo_disk ?: 's3')
                ->url($this->logo_path);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
