<?php

namespace App\Http\Requests;

use App\Enums\TipoVeiculo;
use App\Models\Veiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListVeiculosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Veiculo::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'pessoa_id' => ['nullable', 'integer', 'exists:pessoas,id'],
            'tipo' => ['nullable', Rule::enum(TipoVeiculo::class)],
            'ativo' => ['nullable', 'boolean'],
            'autorizado' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
