<?php

namespace App\Http\Requests;

use App\Enums\StatusTriagem;
use App\Models\Triagem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConcluirTriagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $triagem = $this->route('triagem');

        return $triagem instanceof Triagem && ($this->user()?->can('update', $triagem) ?? false);
    }

    public function rules(): array
    {
        return [
            'decisao' => ['required', Rule::in([StatusTriagem::Autorizada->value, StatusTriagem::Negada->value])],
            'nome_visitante' => ['required', 'string', 'max:150'],
            'documento_visitante' => ['nullable', 'string', 'max:80'],
            'destino' => ['required', 'string', 'max:180'],
            'motivo_visita' => ['required', 'string', 'max:2000'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
