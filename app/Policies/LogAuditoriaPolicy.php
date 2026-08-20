<?php

namespace App\Policies;

use App\Models\LogAuditoria;
use App\Models\User;

class LogAuditoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->ativo && $user->isAdministrador();
    }

    public function view(User $user, LogAuditoria $log): bool
    {
        return $this->viewAny($user);
    }
}
