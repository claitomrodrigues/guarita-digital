<?php

namespace App\Policies;

use App\Models\ConfiguracaoSistema;
use App\Models\User;

class ConfiguracaoSistemaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo && $user->isAdministrador();
    }

    public function view(User $user, ConfiguracaoSistema $configuracao): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ConfiguracaoSistema $configuracao): bool
    {
        return $this->viewAny($user);
    }
}
