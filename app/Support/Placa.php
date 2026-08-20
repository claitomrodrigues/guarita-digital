<?php

namespace App\Support;

final class Placa
{
    private const PADRAO_ANTIGO = '/^[A-Z]{3}[0-9]{4}$/';
    private const PADRAO_MERCOSUL = '/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/';

    private function __construct()
    {
    }

    public static function normalizar(?string $placa): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper((string) $placa, 'UTF-8')) ?? '';
    }

    public static function valida(?string $placa): bool
    {
        $placa = self::normalizar($placa);

        return preg_match(self::PADRAO_ANTIGO, $placa) === 1
            || preg_match(self::PADRAO_MERCOSUL, $placa) === 1;
    }

    public static function antiga(?string $placa): bool
    {
        return preg_match(self::PADRAO_ANTIGO, self::normalizar($placa)) === 1;
    }

    public static function mercosul(?string $placa): bool
    {
        return preg_match(self::PADRAO_MERCOSUL, self::normalizar($placa)) === 1;
    }

    public static function tipo(?string $placa): ?string
    {
        return match (true) {
            self::antiga($placa) => 'antiga',
            self::mercosul($placa) => 'mercosul',
            default => null,
        };
    }

    public static function formatar(?string $placa): string
    {
        $placa = self::normalizar($placa);

        return self::antiga($placa)
            ? substr($placa, 0, 3).'-'.substr($placa, 3)
            : $placa;
    }
}
