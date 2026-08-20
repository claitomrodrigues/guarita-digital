<?php

namespace App\Http\Requests;

use App\Enums\OrigemAcesso;
use App\Enums\StatusAcesso;
use App\Enums\TipoAcesso;
use App\Models\Acesso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAcessosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Acesso::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'placa' => ['nullable', 'string', 'max:10'],
            'tipo' => ['nullable', Rule::enum(TipoAcesso::class)],
            'status' => ['nullable', Rule::enum(StatusAcesso::class)],
            'origem' => ['nullable', Rule::enum(OrigemAcesso::class)],
            'veiculo_id' => ['nullable', 'integer', 'exists:veiculos,id'],
            'pessoa_id' => ['nullable', 'integer', 'exists:pessoas,id'],
            'ponto_acesso_id' => ['nullable', 'integer', 'exists:pontos_acesso,id'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
