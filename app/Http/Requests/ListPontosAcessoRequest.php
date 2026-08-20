<?php

namespace App\Http\Requests;

use App\Enums\SentidoPontoAcesso;
use App\Models\PontoAcesso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPontosAcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', PontoAcesso::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'sentido' => ['nullable', Rule::enum(SentidoPontoAcesso::class)],
            'ativo' => ['nullable', 'boolean'],
        ];
    }
}
