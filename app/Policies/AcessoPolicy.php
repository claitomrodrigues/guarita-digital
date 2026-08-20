<?php

namespace App\Policies;

use App\Models\Acesso;
use App\Models\User;

class AcessoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, Acesso $acesso): bool
    {
        return $user->ativo;
    }

    public function create(User $user): bool
    {
        return $user->ativo && ($user->isAdministrador() || $user->isSeguranca());
    }

    public function update(User $user, Acesso $acesso): bool
    {
        return $this->create($user);
    }
}
