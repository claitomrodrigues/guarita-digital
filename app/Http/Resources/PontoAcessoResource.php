<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PontoAcessoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'codigo' => $this->codigo,
            'sentido' => $this->sentido?->value ?? $this->sentido,
            'sentido_label' => $this->sentido?->label(),
            'localizacao' => $this->localizacao,
            'descricao' => $this->descricao,
            'ativo' => (bool) $this->ativo,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
