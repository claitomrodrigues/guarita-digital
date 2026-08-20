<?php

namespace App\Http\Requests;

use App\Models\LogAuditoria;
use Illuminate\Foundation\Http\FormRequest;

class ListLogsAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', LogAuditoria::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'acao' => ['nullable', 'string', 'max:40'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
