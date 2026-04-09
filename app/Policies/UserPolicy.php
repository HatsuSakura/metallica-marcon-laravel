<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class UserPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $currentUser): bool
    {
        return $this->isControlRole($currentUser);
    }

    public function view(User $currentUser, User $managedUser): bool
    {
        return $this->isControlRole($currentUser);
    }

    public function create(User $currentUser): bool
    {
        return $this->isControlRole($currentUser);
    }

    public function update(User $currentUser, User $managedUser): bool
    {
        return $this->isControlRole($currentUser);
    }

    public function delete(User $currentUser, User $managedUser): bool
    {
        return $this->isControlRole($currentUser) && (int) $currentUser->id !== (int) $managedUser->id;
    }

    public function restore(User $currentUser, User $managedUser): bool
    {
        return $this->isControlRole($currentUser);
    }

    public function forceDelete(User $currentUser, User $managedUser): bool
    {
        return $this->isControlRole($currentUser) && (int) $currentUser->id !== (int) $managedUser->id;
    }

    public function manageCredentials(User $currentUser, User $managedUser): bool
    {
        return $this->update($currentUser, $managedUser);
    }
}
