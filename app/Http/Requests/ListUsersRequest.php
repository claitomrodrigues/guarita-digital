<?php

namespace App\Http\Requests;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'perfil' => ['nullable', Rule::enum(PerfilUsuario::class)],
            'ativo' => ['nullable', 'boolean'],
        ];
    }
}
