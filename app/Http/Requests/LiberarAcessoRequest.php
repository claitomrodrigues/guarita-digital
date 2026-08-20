<?php

namespace App\Http\Requests;

use App\Models\Acesso;
use Illuminate\Foundation\Http\FormRequest;

class LiberarAcessoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $acesso = $this->route('acesso');

        return $acesso instanceof Acesso
            && ($this->user()?->can('update', $acesso) ?? false);
    }

    public function rules(): array
    {
        return [
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
