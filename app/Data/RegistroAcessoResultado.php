<?php

namespace App\Data;

use App\Models\Acesso;

final readonly class RegistroAcessoResultado
{
    public function __construct(
        public Acesso $acesso,
        public bool $duplicado,
    ) {
    }
}
