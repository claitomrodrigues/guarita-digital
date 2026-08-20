<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Veiculo;

class VeiculoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, Veiculo $veiculo): bool
    {
        return $user->ativo;
    }

    public function create(User $user): bool
    {
        return $user->ativo && $user->isAdministrador();
    }

    public function update(User $user, Veiculo $veiculo): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Veiculo $veiculo): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Veiculo $veiculo): bool
    {
        return $this->create($user);
    }
}
