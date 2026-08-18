<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskFolder extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'task_id',
        'name',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'folder_id');
    }
}
