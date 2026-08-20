<?php

namespace App\Http\Requests;

use App\Enums\SentidoPontoAcesso;
use App\Models\PontoAcesso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePontoAcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PontoAcesso::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('codigo')) {
            $this->merge(['codigo' => mb_strtoupper(trim((string) $this->input('codigo')), 'UTF-8')]);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:100'],
            'codigo' => ['required', 'alpha_dash:ascii', 'max:40', Rule::unique('pontos_acesso', 'codigo')],
            'sentido' => ['required', Rule::enum(SentidoPontoAcesso::class)],
            'localizacao' => ['nullable', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
