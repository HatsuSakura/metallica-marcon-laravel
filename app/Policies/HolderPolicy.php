<?php

namespace App\Policies;

use App\Models\Holder;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class HolderPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function view(User $user, Holder $holder): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Holder $holder): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, Holder $holder): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Holder $holder): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Holder $holder): bool
    {
        return $this->isControlRole($user);
    }
}
