<?php

namespace App\Http\Resources\Api;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cliente da agência (a marca dona dos posts). O "id" é o que o sistema
 * parceiro usa para ligar o cliente às contas de rede social dele.
 *
 * @mixin Client
 */
class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'segment' => $this->segment,
            'color' => $this->color,
            'logo_url' => $this->logo_url,
            // Redes informadas no cadastro do STRASA (pode vir vazio).
            'networks' => array_values((array) ($this->social_networks ?? [])),
        ];
    }
}
