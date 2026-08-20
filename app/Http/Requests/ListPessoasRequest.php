<?php

namespace App\Http\Requests;

use App\Enums\TipoVinculo;
use App\Models\Pessoa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPessoasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Pessoa::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'tipo_vinculo' => ['nullable', Rule::enum(TipoVinculo::class)],
            'ativo' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
