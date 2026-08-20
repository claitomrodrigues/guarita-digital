<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo && $user->isAdministrador();
    }

    public function view(User $user, User $model): bool
    {
        return $user->ativo && ($user->isAdministrador() || $user->is($model));
    }

    public function update(User $user, User $model): bool
    {
        return $user->ativo && ($user->isAdministrador() || $user->is($model));
    }
}
