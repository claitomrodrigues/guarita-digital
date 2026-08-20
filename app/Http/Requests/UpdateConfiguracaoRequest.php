<?php

namespace App\Http\Requests;

use App\Enums\TipoConfiguracao;
use App\Models\ConfiguracaoSistema;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use JsonException;

class UpdateConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $configuracao = $this->route('configuracao');

        return $configuracao instanceof ConfiguracaoSistema
            && ($this->user()?->can('update', $configuracao) ?? false);
    }

    public function rules(): array
    {
        return [
            'valor' => ['nullable', 'string', 'max:10000'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'publica' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $configuracao = $this->route('configuracao');

            if (! $configuracao instanceof ConfiguracaoSistema || ! $this->has('valor')) {
                return;
            }

            $valor = $this->input('valor');
            $tipo = $configuracao->tipo;

            if ($valor === null) {
                return;
            }

            if ($tipo === TipoConfiguracao::Boolean && filter_var($valor, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === null) {
                $validator->errors()->add('valor', 'Informe true ou false para esta configuração.');
            }

            if ($tipo === TipoConfiguracao::Integer && filter_var($valor, FILTER_VALIDATE_INT) === false) {
                $validator->errors()->add('valor', 'Informe um número inteiro válido.');
            }

            if ($tipo === TipoConfiguracao::Float && ! is_numeric($valor)) {
                $validator->errors()->add('valor', 'Informe um número decimal válido.');
            }

            if ($tipo === TipoConfiguracao::Json) {
                try {
                    json_decode((string) $valor, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    $validator->errors()->add('valor', 'Informe um JSON válido.');
                }
            }
        }];
    }
}
