<?php

namespace App\Http\Resources;

use App\Support\Cpf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PessoaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'cpf' => $this->cpf,
            'cpf_formatado' => Cpf::formatar($this->cpf),
            'matricula' => $this->matricula,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'tipo_vinculo' => $this->tipo_vinculo?->value ?? $this->tipo_vinculo,
            'tipo_vinculo_label' => $this->tipo_vinculo?->label(),
            'ativo' => (bool) $this->ativo,
            'observacoes' => $this->observacoes,
            'veiculos_count' => $this->whenCounted('veiculos'),
            'veiculos' => VeiculoResource::collection($this->whenLoaded('veiculos')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
