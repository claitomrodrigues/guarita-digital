<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum TipoVinculo: string
{
    use HasEnumOptions;

    case Aluno = 'aluno';
    case Professor = 'professor';
    case Servidor = 'servidor';
    case Terceirizado = 'terceirizado';
    case Visitante = 'visitante';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Aluno => 'Aluno',
            self::Professor => 'Professor',
            self::Servidor => 'Servidor',
            self::Terceirizado => 'Terceirizado',
            self::Visitante => 'Visitante',
            self::Outro => 'Outro',
        };
    }
}
