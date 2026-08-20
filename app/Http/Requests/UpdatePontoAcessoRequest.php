<?php

namespace App\Http\Requests;

use App\Enums\SentidoPontoAcesso;
use App\Models\PontoAcesso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePontoAcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ponto = $this->route('ponto_acesso');

        return $ponto instanceof PontoAcesso
            && ($this->user()?->can('update', $ponto) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('codigo')) {
            $this->merge(['codigo' => mb_strtoupper(trim((string) $this->input('codigo')), 'UTF-8')]);
        }
    }

    public function rules(): array
    {
        $ponto = $this->route('ponto_acesso');

        return [
            'nome' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'codigo' => ['sometimes', 'required', 'alpha_dash:ascii', 'max:40', Rule::unique('pontos_acesso', 'codigo')->ignore($ponto)],
            'sentido' => ['sometimes', 'required', Rule::enum(SentidoPontoAcesso::class)],
            'localizacao' => ['nullable', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
