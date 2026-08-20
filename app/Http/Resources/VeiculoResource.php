<?php

namespace App\Http\Resources;

use App\Support\Placa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VeiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pessoa_id' => $this->pessoa_id,
            'placa' => $this->placa,
            'placa_formatada' => Placa::formatar($this->placa),
            'padrao_placa' => Placa::tipo($this->placa),
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'cor' => $this->cor,
            'tipo' => $this->tipo?->value ?? $this->tipo,
            'tipo_label' => $this->tipo?->label(),
            'ano' => $this->ano,
            'ativo' => (bool) $this->ativo,
            'autorizado' => (bool) $this->autorizado,
            'apto_ao_acesso' => $this->estaAutorizado(),
            'validade_autorizacao' => $this->validade_autorizacao?->toDateString(),
            'motivo_bloqueio' => $this->motivo_bloqueio,
            'observacoes' => $this->observacoes,
            'pessoa' => new PessoaResource($this->whenLoaded('pessoa')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
