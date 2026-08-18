<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'task_id', 'uploaded_by',
        'disk', 'path', 'original_name', 'mime_type', 'size', 'is_image',
    ];

    protected function casts(): array
    {
        return [
            'is_image' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * URL para exibir o arquivo.
     *
     * - Se o disco tem URL pública configurada (AWS_URL no R2), usa-a direto.
     * - Caso contrário, gera uma URL temporária assinada (S3/R2 privados).
     * - Para o disco "public" local (dev), cai no url() simples.
     */
    public function getUrlAttribute(): string
    {
        $disk = Storage::disk($this->disk);
        $config = config("filesystems.disks.{$this->disk}");

        if (! empty($config['url'])) {
            return $disk->url($this->path);
        }

        try {
            return $disk->temporaryUrl($this->path, now()->addMinutes(30));
        } catch (\Throwable $e) {
            return $disk->url($this->path);
        }
    }

    /** Tamanho legível (ex.: 1.4 MB). */
    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
