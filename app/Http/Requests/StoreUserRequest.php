<?php

namespace App\Http\Requests;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email')), 'UTF-8'),
            'matricula' => filled($this->input('matricula')) ? trim((string) $this->input('matricula')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'matricula' => ['nullable', 'string', 'max:40', Rule::unique('users', 'matricula')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'perfil' => ['required', Rule::enum(PerfilUsuario::class)],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
