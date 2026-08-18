<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskItem extends Model
{
    protected $fillable = ['task_id', 'description', 'is_completed', 'position'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
