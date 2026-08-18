<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    use BelongsToCompany;

    public const TYPE_CREATED = 'created';
    public const TYPE_COLUMN_CHANGED = 'column_changed';
    public const TYPE_ASSIGNEE_CHANGED = 'assignee_changed';
    public const TYPE_PUBLISH_DATE_CHANGED = 'publish_date_changed';
    public const TYPE_PUBLISHED = 'published';
    public const TYPE_REJECTED = 'rejected';

    protected $fillable = [
        'company_id', 'task_id', 'user_id', 'type', 'description', 'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
