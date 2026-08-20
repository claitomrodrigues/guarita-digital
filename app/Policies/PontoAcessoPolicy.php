<?php

namespace App\Policies;

use App\Models\PontoAcesso;
use App\Models\User;

class PontoAcessoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, PontoAcesso $pontoAcesso): bool
    {
        return $user->ativo;
    }

    public function create(User $user): bool
    {
        return $user->ativo && $user->isAdministrador();
    }

    public function update(User $user, PontoAcesso $pontoAcesso): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, PontoAcesso $pontoAcesso): bool
    {
        return $this->create($user);
    }
}
