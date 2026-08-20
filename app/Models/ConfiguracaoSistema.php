<?php

namespace App\Models;

use App\Enums\TipoConfiguracao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JsonException;

class ConfiguracaoSistema extends Model
{
    protected $table = 'configuracoes_sistema';

    protected $fillable = [
        'chave',
        'valor',
        'tipo',
        'grupo',
        'descricao',
        'publica',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoConfiguracao::class,
            'publica' => 'boolean',
        ];
    }

    public function scopePublicas(Builder $query): Builder
    {
        return $query->where('publica', true);
    }

    public function valorConvertido(): mixed
    {
        $tipo = $this->tipo instanceof TipoConfiguracao
            ? $this->tipo
            : TipoConfiguracao::tryFrom((string) $this->tipo) ?? TipoConfiguracao::String;

        return match ($tipo) {
            TipoConfiguracao::Boolean => filter_var($this->valor, FILTER_VALIDATE_BOOL),
            TipoConfiguracao::Integer => (int) $this->valor,
            TipoConfiguracao::Float => (float) $this->valor,
            TipoConfiguracao::Json => $this->decodificarJson(),
            TipoConfiguracao::String => $this->valor,
        };
    }

    private function decodificarJson(): mixed
    {
        try {
            return json_decode((string) $this->valor, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
