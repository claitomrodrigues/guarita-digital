<?php

namespace App\Http\Requests;

use App\Enums\TipoAcesso;
use App\Models\Acesso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReconhecerPlacaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Acesso::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'imagem' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.max(1024, (int) config('guarita.max_capture_kb', 10240)),
                'dimensions:min_width=200,min_height=100,max_width=8000,max_height=8000',
            ],
            'ponto_acesso_id' => ['nullable', 'integer', Rule::exists('pontos_acesso', 'id')->whereNull('deleted_at')],
            'tipo' => ['nullable', Rule::enum(TipoAcesso::class)],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
