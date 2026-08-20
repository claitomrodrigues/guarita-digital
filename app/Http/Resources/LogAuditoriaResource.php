<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogAuditoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'acao' => $this->acao,
            'entidade' => $this->entidade,
            'entidade_id' => $this->entidade_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'dados_anteriores' => $this->dados_anteriores,
            'dados_novos' => $this->dados_novos,
            'usuario' => new UserResource($this->whenLoaded('usuario')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
