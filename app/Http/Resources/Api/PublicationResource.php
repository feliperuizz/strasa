<?php

namespace App\Http\Resources\Api;

use App\Models\TaskPublication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Situação da postagem numa rede, como o sistema parceiro informou.
 *
 * @mixin TaskPublication
 */
class PublicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'network' => $this->network,
            'status' => $this->status,
            'external_id' => $this->external_id,
            'permalink' => $this->permalink,
            'error_message' => $this->error_message,
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'reported_at' => $this->reported_at?->toIso8601String(),
        ];
    }
}
