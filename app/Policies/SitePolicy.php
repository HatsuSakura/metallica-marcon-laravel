<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class SitePolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function view(User $user, Site $site): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Site $site): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, Site $site): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Site $site): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Site $site): bool
    {
        return $this->isControlRole($user);
    }
}
