<?php

namespace App\Http\Resources\Api;

use App\Models\ApiToken;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * Imagem ou vídeo do post, na ordem do carrossel.
 *
 * A URL é assinada e temporária: funciona sem cabeçalho de autenticação (dá
 * para repassar direto à API do Instagram/Facebook, que baixa o arquivo),
 * expira em alguns dias e deixa de funcionar se a chave for revogada.
 *
 * @mixin TaskAttachment
 */
class MediaResource extends JsonResource
{
    /** Validade dos links de mídia. */
    public const VALIDADE_EM_DIAS = 7;

    private int $posicao = 1;

    private ?ApiToken $chave = null;

    public function naPosicao(int $posicao): self
    {
        $this->posicao = $posicao;

        return $this;
    }

    public function daChave(?ApiToken $chave): self
    {
        $this->chave = $chave;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $chave = $this->chave ?? $request->attributes->get('api_token');
        $expira = now()->addDays(self::VALIDADE_EM_DIAS);

        return [
            'id' => $this->id,
            'position' => $this->posicao,
            'type' => $this->is_image ? 'image' : 'video',
            'mime_type' => $this->mime_type,
            'file_name' => $this->original_name,
            'size_bytes' => (int) $this->size,
            'url' => URL::temporarySignedRoute('api.media', $expira, ['attachment' => $this->id, 'k' => $chave?->id]),
            'url_expires_at' => $expira->toIso8601String(),
        ];
    }
}
