<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Caderno de aprendizados do cliente. O Claude sugere (a partir dos pedidos
 * de ajuste: "zoom mais sutil" vale para os próximos vídeos, "tira essa
 * frase" não); a equipe aprova. Só os aplicados entram no contexto das
 * próximas edições — anotar não é mudar.
 */
class VideoLearning extends Model
{
    use BelongsToCompany;
    use Concerns\GuardaEmUtc;

    protected $fillable = ['company_id', 'client_id', 'video_job_id', 'texto', 'etapa', 'status', 'created_by'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(VideoJob::class, 'video_job_id');
    }
}
