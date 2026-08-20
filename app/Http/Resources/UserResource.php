<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'perfil' => $this->perfil?->value ?? $this->perfil,
            'perfil_label' => $this->perfil?->label(),
            'ativo' => (bool) $this->ativo,
            'ultimo_login_em' => $this->ultimo_login_em?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
