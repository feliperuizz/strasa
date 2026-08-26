<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'task_id', 'user_id', 'body', 'mentions',
        'is_from_client', 'client_author_name', 'visible_to_client',
    ];

    protected function casts(): array
    {
        return [
            'mentions' => 'array',
            'is_from_client' => 'boolean',
            'visible_to_client' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Nome de quem escreveu, seja da equipe ou do cliente. */
    public function authorName(): string
    {
        if ($this->is_from_client) {
            return $this->client_author_name ?: 'Cliente';
        }

        return $this->user?->name ?: 'Usuário removido';
    }
}
