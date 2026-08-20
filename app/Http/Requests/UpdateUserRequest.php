<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');

        return $usuario instanceof User
            && ($this->user()?->can('update', $usuario) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $dados = [];

        if ($this->has('name')) {
            $dados['name'] = trim((string) $this->input('name'));
        }

        if ($this->has('email')) {
            $dados['email'] = mb_strtolower(trim((string) $this->input('email')), 'UTF-8');
        }

        $this->merge($dados);
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
