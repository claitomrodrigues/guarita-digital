<?php

namespace App\Support;

final class Cpf
{
    private function __construct()
    {
    }

    public static function normalizar(?string $cpf): ?string
    {
        $normalizado = preg_replace('/\D+/', '', (string) $cpf) ?? '';

        return $normalizado === '' ? null : $normalizado;
    }

    public static function valida(?string $cpf): bool
    {
        $cpf = self::normalizar($cpf);

        if ($cpf === null || strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($digito = 9; $digito < 11; $digito++) {
            $soma = 0;

            for ($indice = 0; $indice < $digito; $indice++) {
                $soma += ((int) $cpf[$indice]) * (($digito + 1) - $indice);
            }

            $verificador = (10 * $soma) % 11;
            $verificador = $verificador === 10 ? 0 : $verificador;

            if ($verificador !== (int) $cpf[$digito]) {
                return false;
            }
        }

        return true;
    }

    public static function formatar(?string $cpf): ?string
    {
        $cpf = self::normalizar($cpf);

        if ($cpf === null || strlen($cpf) !== 11) {
            return $cpf;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2),
        );
    }
}
