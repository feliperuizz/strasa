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
        'assignee_id', 'created_by',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
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

    public function contentTypeLabel(): ?string
    {
        return self::CONTENT_TYPES[$this->content_type] ?? $this->content_type;
    }
}
