<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum PerfilUsuario: string
{
    use HasEnumOptions;

    case Administrador = 'administrador';
    case Seguranca = 'seguranca';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Seguranca => 'Segurança da guarita',
        };
    }
}
