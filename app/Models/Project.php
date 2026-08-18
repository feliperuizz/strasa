<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'client_id', 'name', 'description', 'archived_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'project_user_favorites')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Cria as colunas padrão a partir do template do cliente.
     * Chamado logo após criar o projeto.
     */
    public function createDefaultColumns(): void
    {
        $position = 0;

        foreach ($this->client->columnsTemplate() as $col) {
            $this->columns()->create([
                'company_id' => $this->company_id,
                'name' => $col['name'],
                'key' => $col['key'] ?? null,
                'color' => $col['color'] ?? '#64748b',
                'position' => $position++,
                'marks_published' => $col['marks_published'] ?? false,
                'requires_rejection_reason' => $col['requires_rejection_reason'] ?? false,
            ]);
        }
    }
}
