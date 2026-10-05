<?php

namespace App\Http\Requests;

use App\Enums\TipoVeiculo;
use App\Rules\PlacaBrasileira;
use App\Support\Placa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->ativo;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'placa' => Placa::normalizar((string) $this->input('placa')),
            'ativo' => $this->boolean('ativo'),
            'autorizado' => $this->boolean('autorizado'),
        ]);
    }

    public function rules(): array
    {
        return [
            'pessoa_id' => ['required', 'integer', Rule::exists('pessoas', 'id')->whereNull('deleted_at')],
            'placa' => ['required', 'string', 'size:7', new PlacaBrasileira, Rule::unique('veiculos', 'placa')],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['required', 'string', 'max:100'],
            'cor' => ['nullable', 'string', 'max:50'],
            'tipo' => ['required', Rule::enum(TipoVeiculo::class)],
            'ano' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'ativo' => ['required', 'boolean'],
            'autorizado' => ['required', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
