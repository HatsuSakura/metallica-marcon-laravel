<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the currentUser can view any models.
     */
    public function viewAny(User $currentUser): bool
    {
        return true;
    }

    /**
     * Determine whether the currentUser can view the model.
     */
    public function view(User $currentUser, User $managedUser): bool
    {
        return true;
    }

    /**
     * Determine whether the currentUser can create models.
     */
    public function create(User $currentUser): bool
    {
        return true;
    }

    /**
     * Determine whether the currentUser can update the model.
     */
    public function update(User $currentUser, User $managedUser): bool
    {
        return true;
    }

    /**
     * Determine whether the currentUser can delete the model.
     */
    public function delete(User $currentUser, User $managedUser): bool
    {
        return true;
    }

    /**
     * Determine whether the currentUser can restore the model.
     */
    public function restore(User $currentUser, User $managedUser): bool
    {
        return true;
    }

    /**
     * Determine whether the currentUser can permanently delete the model.
     */
    public function forceDelete(User $currentUser, User $managedUser): bool
    {
        return true;
    }
}
