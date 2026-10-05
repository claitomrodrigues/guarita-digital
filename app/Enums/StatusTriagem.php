<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum StatusTriagem: string
{
    use HasEnumOptions;

    case Pendente = 'pendente';
    case Autorizada = 'autorizada';
    case Negada = 'negada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Autorizada => 'Autorizada',
            self::Negada => 'Negada',
            self::Cancelada => 'Cancelada',
        };
    }
}
