<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use BelongsToCompany;

    /** Tipos de conteúdo aceitos (rótulos para a UI). */
    public const CONTENT_TYPES = [
        'feed' => 'Feed',
        'carousel' => 'Carrossel',
        'story' => 'Story',
        'reel' => 'Reel',
        'blog' => 'Blog',
        'video' => 'Vídeo',
    ];



    protected $fillable = [
        'company_id', 'client_id', 'project_id', 'column_id',
        'created_by',
        'title', 'description', 'content_type',
        'publish_date', 'publish_time', 'position', 'is_published', 'published_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'social_networks' => 'array',
            'publish_date' => 'date',
            // publish_time is treated as a string 'H:i:s' by default
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * Qualquer mudança em tarefa derruba o cache do dashboard da empresa —
     * é o que garante que os contadores nunca fiquem atrasados.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $t) => \App\Http\Controllers\DashboardController::invalidar($t->company_id));
        static::deleted(fn (self $t) => \App\Http\Controllers\DashboardController::invalidar($t->company_id));
    }

    /* --------------------------------------------------------------------- */
    /* Relações                                                              */
    /* --------------------------------------------------------------------- */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function folders(): HasMany
    {
        return $this->hasMany(TaskFolder::class)->oldest('name');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaskItem::class)->orderBy('position');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(TaskApproval::class)->orderByDesc('round');
    }

    /** A submissão mais recente ao painel do cliente, se houver. */
    public function currentApproval(): ?TaskApproval
    {
        return $this->relationLoaded('approvals')
            ? $this->approvals->first()
            : $this->approvals()->first();
    }

    /* --------------------------------------------------------------------- */
    /* Scopes / Helpers                                                      */
    /* --------------------------------------------------------------------- */

    public function scopeForCalendar(Builder $query, string $from, string $to): Builder
    {
        return $query->whereNotNull('publish_date')
            ->whereBetween('publish_date', [$from, $to]);
    }

    /** Primeira imagem anexada, usada como capa do card. */
    public function coverImage(): ?TaskAttachment
    {
        return $this->attachments->firstWhere('is_image', true);
    }

    /**
     * Peças visuais que o cliente vê no portal: todas as imagens (o carrossel
     * inteiro, na ordem em que foram anexadas) seguidas dos vídeos.
     */
    public function approvalMedia()
    {
        return $this->attachments
            ->filter(fn ($a) => $a->is_image || str_starts_with((string) $a->mime_type, 'video/'))
            ->sortBy([['is_image', 'desc'], ['id', 'asc']])
            ->values();
    }

    public function contentTypeLabel(): ?string
    {
        return self::CONTENT_TYPES[$this->content_type] ?? $this->content_type;
    }
}
