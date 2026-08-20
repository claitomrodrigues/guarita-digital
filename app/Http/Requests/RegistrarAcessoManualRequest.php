<?php

namespace App\Http\Requests;

use App\Enums\TipoAcesso;
use App\Models\Acesso;
use App\Rules\PlacaBrasileira;
use App\Support\Placa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarAcessoManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Acesso::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => Placa::normalizar((string) $this->input('placa'))]);
        }
    }

    public function rules(): array
    {
        return [
            'placa' => ['required', 'string', 'size:7', new PlacaBrasileira],
            'tipo' => ['required', Rule::enum(TipoAcesso::class)],
            'ponto_acesso_id' => ['nullable', 'integer', Rule::exists('pontos_acesso', 'id')->whereNull('deleted_at')],
            'data_hora' => ['nullable', 'date'],
            'liberar_manualmente' => ['sometimes', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
