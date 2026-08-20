<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum TipoVeiculo: string
{
    use HasEnumOptions;

    case Carro = 'carro';
    case Moto = 'moto';
    case Caminhonete = 'caminhonete';
    case Van = 'van';
    case Onibus = 'onibus';
    case Caminhao = 'caminhao';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Carro => 'Carro',
            self::Moto => 'Moto',
            self::Caminhonete => 'Caminhonete',
            self::Van => 'Van',
            self::Onibus => 'Ônibus',
            self::Caminhao => 'Caminhão',
            self::Outro => 'Outro',
        };
    }
}
