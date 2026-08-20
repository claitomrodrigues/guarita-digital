<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracaoSistemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chave' => $this->chave,
            'valor' => $this->valor,
            'valor_convertido' => $this->valorConvertido(),
            'tipo' => $this->tipo?->value ?? $this->tipo,
            'tipo_label' => $this->tipo?->label(),
            'grupo' => $this->grupo,
            'descricao' => $this->descricao,
            'publica' => (bool) $this->publica,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
