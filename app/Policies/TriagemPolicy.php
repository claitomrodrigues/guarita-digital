<?php

namespace App\Policies;

use App\Models\Triagem;
use App\Models\User;

class TriagemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo;
    }

    public function view(User $user, Triagem $triagem): bool
    {
        return $user->ativo;
    }

    public function update(User $user, Triagem $triagem): bool
    {
        return $user->ativo && ($user->isAdministrador() || $user->isSeguranca());
    }
}
