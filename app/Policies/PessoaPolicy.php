<?php

namespace App\Policies;

use App\Models\Pessoa;
use App\Models\User;

class PessoaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, Pessoa $pessoa): bool
    {
        return $user->ativo;
    }

    public function create(User $user): bool
    {
        return $user->ativo && $user->isAdministrador();
    }

    public function update(User $user, Pessoa $pessoa): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Pessoa $pessoa): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Pessoa $pessoa): bool
    {
        return $this->create($user);
    }
}
