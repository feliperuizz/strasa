<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma linha da linha do tempo de uma edição (sistema, pedido, Claude, erro). */
class VideoJobEvent extends Model
{
    use Concerns\GuardaEmUtc;

    protected $fillable = ['video_job_id', 'user_id', 'tipo', 'texto', 'dados'];

    protected function casts(): array
    {
        return ['dados' => 'array'];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VideoJob::class, 'video_job_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
