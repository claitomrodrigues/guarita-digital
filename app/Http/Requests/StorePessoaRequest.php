<?php

namespace App\Http\Requests;

use App\Enums\TipoVinculo;
use App\Models\Pessoa;
use App\Rules\CpfValido;
use App\Support\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Pessoa::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => $this->has('nome') ? trim((string) $this->input('nome')) : null,
            'cpf' => Cpf::normalizar($this->input('cpf')),
            'matricula' => $this->filled('matricula') ? trim((string) $this->input('matricula')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email')), 'UTF-8') : null,
            'telefone' => $this->filled('telefone') ? trim((string) $this->input('telefone')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:150'],
            'cpf' => ['nullable', 'digits:11', new CpfValido, Rule::unique('pessoas', 'cpf')],
            'matricula' => ['nullable', 'string', 'max:40', Rule::unique('pessoas', 'matricula')],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'tipo_vinculo' => ['required', Rule::enum(TipoVinculo::class)],
            'ativo' => ['sometimes', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
