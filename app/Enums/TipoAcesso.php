<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum TipoAcesso: string
{
    use HasEnumOptions;

    case Entrada = 'entrada';
    case Saida = 'saida';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
        };
    }

    public function oposto(): self
    {
        return $this === self::Entrada ? self::Saida : self::Entrada;
    }
}
