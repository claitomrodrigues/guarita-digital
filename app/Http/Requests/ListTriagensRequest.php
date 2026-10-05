<?php

namespace App\Http\Requests;

use App\Enums\StatusTriagem;
use App\Models\Triagem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTriagensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Triagem::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusTriagem::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'inicio' => ['nullable', 'date'],
            'fim' => ['nullable', 'date', 'after_or_equal:inicio'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
