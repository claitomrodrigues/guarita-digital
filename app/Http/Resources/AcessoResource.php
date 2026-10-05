<?php

namespace App\Http\Resources;

use App\Support\Placa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcessoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $podeLiberar = $request->user()?->can('update', $this->resource) ?? false;

        return [
            'id' => $this->id,
            'capture_id' => $this->capture_id,
            'placa_reconhecida' => $this->placa_reconhecida,
            'placa_formatada' => Placa::formatar($this->placa_reconhecida),
            'padrao_placa' => Placa::tipo($this->placa_reconhecida),
            'tipo' => $this->tipo?->value ?? $this->tipo,
            'tipo_label' => $this->tipo?->label(),
            'status' => $this->status?->value ?? $this->status,
            'status_label' => $this->status?->label(),
            'permite_passagem' => $this->permitePassagem(),
            'origem' => $this->origem?->value ?? $this->origem,
            'origem_label' => $this->origem?->label(),
            'data_hora' => $this->data_hora?->toIso8601String(),
            'confianca' => $this->confianca !== null ? (float) $this->confianca : null,
            'confianca_yolo' => $this->confianca_yolo !== null ? (float) $this->confianca_yolo : null,
            'quadros_confirmados' => $this->quadros_confirmados,
            'modelo_placa' => $this->modelo_placa,
            'observacoes' => $this->observacoes,
            'tem_imagem' => filled($this->imagem),
            'imagem_url' => filled($this->imagem) ? route('acessos.imagem', $this->resource) : null,
            'pode_liberar' => $podeLiberar && ! $this->permitePassagem(),
            'veiculo' => new VeiculoResource($this->whenLoaded('veiculo')),
            'pessoa' => new PessoaResource($this->whenLoaded('pessoa')),
            'usuario' => new UserResource($this->whenLoaded('usuario')),
            'ponto_acesso' => new PontoAcessoResource($this->whenLoaded('pontoAcesso')),
            'triagem' => new TriagemResource($this->whenLoaded('triagem')),
            'metadata' => $this->when(
                $request->user()?->isAdministrador() ?? false,
                $this->metadata,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
