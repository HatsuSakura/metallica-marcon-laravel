<?php

namespace App\Policies;

use App\Models\Trailer;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class TrailerPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function view(User $user, Trailer $trailer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Trailer $trailer): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, Trailer $trailer): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Trailer $trailer): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Trailer $trailer): bool
    {
        return $this->isControlRole($user);
    }
}
