<?php

namespace Tests\Unit;

use App\Support\Placa;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlacaTest extends TestCase
{
    #[DataProvider('placasValidas')]
    public function test_reconhece_placas_brasileiras_validas(string $entrada, string $normalizada, string $formatada): void
    {
        $this->assertSame($normalizada, Placa::normalizar($entrada));
        $this->assertSame($formatada, Placa::formatar($entrada));
        $this->assertTrue(Placa::valida($entrada));
    }

    public static function placasValidas(): array
    {
        return [
            'antiga com hífen' => ['CDU-9598', 'CDU9598', 'CDU-9598'],
            'antiga sem hífen' => ['BRA5118', 'BRA5118', 'BRA-5118'],
            'mercosul' => ['FJB4E12', 'FJB4E12', 'FJB4E12'],
            'minúscula' => ['fjb-4e12', 'FJB4E12', 'FJB4E12'],
        ];
    }

    #[DataProvider('placasInvalidas')]
    public function test_rejeita_placas_invalidas(string $placa): void
    {
        $this->assertFalse(Placa::valida($placa));
    }

    public static function placasInvalidas(): array
    {
        return [
            ['ABC123'],
            ['ABCD123'],
            ['ABC12D3'],
            ['1234567'],
            ['BRASIL1'],
        ];
    }
}
