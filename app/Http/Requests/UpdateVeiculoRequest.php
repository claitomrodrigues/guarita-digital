<?php

namespace App\Http\Requests;

use App\Enums\TipoVeiculo;
use App\Models\Veiculo;
use App\Rules\PlacaBrasileira;
use App\Support\Placa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $veiculo = $this->route('veiculo');

        return $veiculo instanceof Veiculo
            && ($this->user()?->can('update', $veiculo) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => Placa::normalizar((string) $this->input('placa'))]);
        }
    }

    public function rules(): array
    {
        $veiculo = $this->route('veiculo');

        return [
            'pessoa_id' => ['nullable', 'integer', Rule::exists('pessoas', 'id')->whereNull('deleted_at')],
            'placa' => ['sometimes', 'required', 'string', 'size:7', new PlacaBrasileira, Rule::unique('veiculos', 'placa')->ignore($veiculo)],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'cor' => ['nullable', 'string', 'max:50'],
            'tipo' => ['sometimes', 'required', Rule::enum(TipoVeiculo::class)],
            'ano' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'ativo' => ['sometimes', 'boolean'],
            'autorizado' => ['sometimes', 'boolean'],
            'validade_autorizacao' => ['nullable', 'date'],
            'motivo_bloqueio' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
