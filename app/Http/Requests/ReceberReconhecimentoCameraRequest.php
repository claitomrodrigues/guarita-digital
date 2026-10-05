<?php

namespace App\Http\Requests;

use App\Enums\TipoAcesso;
use App\Rules\PlacaBrasileira;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceberReconhecimentoCameraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('camera_ponto_acesso');
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->input('placa')) ?? '')]);
        }

        foreach (['confianca_ocr', 'confianca_yolo'] as $campo) {
            if (is_numeric($this->input($campo)) && (float) $this->input($campo) > 1) {
                $this->merge([$campo => (float) $this->input($campo) / 100]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'capture_id' => ['required', 'string', 'min:8', 'max:120'],
            'placa' => ['required', new PlacaBrasileira],
            'confianca_ocr' => ['required', 'numeric', 'between:0,1'],
            'confianca_yolo' => ['nullable', 'numeric', 'between:0,1'],
            'modelo_placa' => ['nullable', Rule::in(['antiga', 'mercosul', 'indefinida'])],
            'quadros_confirmados' => ['nullable', 'integer', 'min:1', 'max:255'],
            'tipo' => ['nullable', Rule::enum(TipoAcesso::class)],
            'capturado_em' => ['required', 'date'],
            'candidatos' => ['nullable', 'array', 'max:10'],
            'candidatos.*.placa' => ['required_with:candidatos', 'string', 'max:10'],
            'candidatos.*.confianca' => ['nullable', 'numeric', 'between:0,1'],
            'imagem' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.max(1024, (int) config('guarita.max_capture_kb', 10240))],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'versao_camera' => ['nullable', 'string', 'max:40'],
        ];
    }
}
