<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdraw;
use App\Policies\Concerns\AuthorizesDomainRoles;

class WithdrawPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function view(User $user, Withdraw $withdraw): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Withdraw $withdraw): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, Withdraw $withdraw): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Withdraw $withdraw): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Withdraw $withdraw): bool
    {
        return $this->isControlRole($user);
    }
}
