<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum TipoConfiguracao: string
{
    use HasEnumOptions;

    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case Json = 'json';

    public function label(): string
    {
        return match ($this) {
            self::String => 'Texto',
            self::Boolean => 'Sim/Não',
            self::Integer => 'Número inteiro',
            self::Float => 'Número decimal',
            self::Json => 'JSON',
        };
    }
}
