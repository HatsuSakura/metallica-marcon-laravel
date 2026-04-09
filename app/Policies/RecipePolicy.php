<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class RecipePolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function view(User $user, Recipe $recipe): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        return $this->isControlRole($user);
    }
}
