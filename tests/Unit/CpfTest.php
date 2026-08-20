<?php

namespace Tests\Unit;

use App\Support\Cpf;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfTest extends TestCase
{
    #[DataProvider('cpfsValidos')]
    public function test_valida_e_formata_cpf(string $entrada, string $normalizado): void
    {
        $this->assertTrue(Cpf::valida($entrada));
        $this->assertSame($normalizado, Cpf::normalizar($entrada));
    }

    public static function cpfsValidos(): array
    {
        return [
            ['529.982.247-25', '52998224725'],
            ['11144477735', '11144477735'],
        ];
    }

    #[DataProvider('cpfsInvalidos')]
    public function test_rejeita_cpf_invalido(string $cpf): void
    {
        $this->assertFalse(Cpf::valida($cpf));
    }

    public static function cpfsInvalidos(): array
    {
        return [
            ['00000000000'],
            ['12345678900'],
            ['123'],
        ];
    }
}
