<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TriagemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'nome_visitante' => $this->nome_visitante,
            'documento_visitante' => $this->documento_visitante,
            'destino' => $this->destino,
            'motivo_visita' => $this->motivo_visita,
            'observacoes' => $this->observacoes,
            'iniciada_em' => $this->iniciada_em?->toIso8601String(),
            'concluida_em' => $this->concluida_em?->toIso8601String(),
            'acesso' => new AcessoResource($this->whenLoaded('acesso')),
            'usuario' => new UserResource($this->whenLoaded('usuario')),
        ];
    }
}
