<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum SentidoPontoAcesso: string
{
    use HasEnumOptions;

    case Entrada = 'entrada';
    case Saida = 'saida';
    case Ambos = 'ambos';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Somente entrada',
            self::Saida => 'Somente saída',
            self::Ambos => 'Entrada e saída',
        };
    }

    public function permite(TipoAcesso $tipo): bool
    {
        return $this === self::Ambos
            || ($this === self::Entrada && $tipo === TipoAcesso::Entrada)
            || ($this === self::Saida && $tipo === TipoAcesso::Saida);
    }
}
