<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum OrigemAcesso: string
{
    use HasEnumOptions;

    case Ocr = 'ocr';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Ocr => 'Reconhecimento automático',
            self::Manual => 'Registro manual',
        };
    }
}
