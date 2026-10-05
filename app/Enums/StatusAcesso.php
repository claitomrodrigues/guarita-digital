<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum StatusAcesso: string
{
    use HasEnumOptions;

    case Autorizado = 'autorizado';
    case NaoCadastrado = 'nao_cadastrado';
    case Bloqueado = 'bloqueado';
    case LiberadoManualmente = 'liberado_manualmente';
    case LeituraInconclusiva = 'leitura_inconclusiva';

    public function label(): string
    {
        return match ($this) {
            self::Autorizado => 'Autorizado',
            self::NaoCadastrado => 'Não cadastrado',
            self::Bloqueado => 'Bloqueado',
            self::LiberadoManualmente => 'Liberado manualmente',
            self::LeituraInconclusiva => 'Leitura inconclusiva',
        };
    }

    public function permitePassagem(): bool
    {
        return in_array($this, [self::Autorizado, self::LiberadoManualmente], true);
    }
}
