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
        $pessoa = $this->route('pessoa');

        return $pessoa instanceof Pessoa
            && ($this->user()?->can('update', $pessoa) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $dados = [];

        if ($this->has('nome')) {
            $dados['nome'] = trim((string) $this->input('nome'));
        }

        if ($this->has('cpf')) {
            $dados['cpf'] = Cpf::normalizar($this->input('cpf'));
        }

        if ($this->has('matricula')) {
            $dados['matricula'] = $this->filled('matricula') ? trim((string) $this->input('matricula')) : null;
        }

        if ($this->has('email')) {
            $dados['email'] = $this->filled('email')
                ? mb_strtolower(trim((string) $this->input('email')), 'UTF-8')
                : null;
        }

        if ($this->has('telefone')) {
            $dados['telefone'] = $this->filled('telefone') ? trim((string) $this->input('telefone')) : null;
        }

        $this->merge($dados);
    }

    public function rules(): array
    {
        $pessoa = $this->route('pessoa');

        return [
            'nome' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'cpf' => ['nullable', 'digits:11', new CpfValido, Rule::unique('pessoas', 'cpf')->ignore($pessoa)],
            'matricula' => ['nullable', 'string', 'max:40', Rule::unique('pessoas', 'matricula')->ignore($pessoa)],
            'email' => ['nullable', 'email:rfc', 'max:150'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'tipo_vinculo' => ['sometimes', 'required', Rule::enum(TipoVinculo::class)],
            'ativo' => ['sometimes', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
