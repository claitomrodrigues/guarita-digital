<?php

namespace App\Http\Requests;

use App\Enums\TipoVinculo;
use App\Models\Pessoa;
use App\Rules\CpfValido;
use App\Support\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->ativo;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => trim((string) $this->input('nome')),
            'cpf' => Cpf::normalizar($this->input('cpf')),
            'matricula' => $this->filled('matricula') ? trim((string) $this->input('matricula')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email')), 'UTF-8') : null,
            'telefone' => $this->filled('telefone') ? trim((string) $this->input('telefone')) : null,
            'ativo' => $this->boolean('ativo'),
        ]);
    }

    public function rules(): array
    {
        /** @var Pessoa $pessoa */
        $pessoa = $this->route('pessoa');

        return [
            'nome' => ['required', 'string', 'min:2', 'max:150'],
            'cpf' => ['nullable', 'digits:11', new CpfValido, Rule::unique('pessoas', 'cpf')->ignore($pessoa)],
            'matricula' => ['nullable', 'string', 'max:40', Rule::unique('pessoas', 'matricula')->ignore($pessoa)],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'tipo_vinculo' => ['required', Rule::enum(TipoVinculo::class)],
            'ativo' => ['required', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
