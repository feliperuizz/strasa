<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Column extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'project_id', 'name', 'key', 'color',
        'position', 'marks_published', 'requires_rejection_reason', 'is_publish_column',
        'is_approval_column',
    ];

    protected function casts(): array
    {
        return [
            'marks_published' => 'boolean',
            'requires_rejection_reason' => 'boolean',
            'is_publish_column' => 'boolean',
            'is_approval_column' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }
}
