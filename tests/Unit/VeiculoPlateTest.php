<?php

namespace Tests\Unit;

use App\Models\Veiculo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VeiculoPlateTest extends TestCase
{
    #[DataProvider('placasValidas')]
    public function test_reconhece_placas_brasileiras_validas(string $entrada, string $normalizada): void
    {
        $this->assertSame($normalizada, Veiculo::normalizarPlaca($entrada));
        $this->assertTrue(Veiculo::placaValida($entrada));
    }

    public static function placasValidas(): array
    {
        return [
            'antiga com hífen' => ['CDU-9598', 'CDU9598'],
            'antiga sem hífen' => ['BRA5118', 'BRA5118'],
            'mercosul' => ['FJB4E12', 'FJB4E12'],
            'minúscula' => ['fjb-4e12', 'FJB4E12'],
        ];
    }

    #[DataProvider('placasInvalidas')]
    public function test_rejeita_placas_invalidas(string $placa): void
    {
        $this->assertFalse(Veiculo::placaValida($placa));
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
