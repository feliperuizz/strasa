<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskItem extends Model
{
    protected $fillable = ['task_id', 'description', 'is_completed', 'position', 'assignee_id', 'due_date'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            // Só a data, sem hora: o slideover joga direto num <input type="date">.
            'due_date' => 'date:Y-m-d',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
