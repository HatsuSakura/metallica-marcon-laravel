<?php

namespace App\Policies;

use App\Models\CatalogItem;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class CatalogItemPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function view(User $user, CatalogItem $catalogItem): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, CatalogItem $catalogItem): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, CatalogItem $catalogItem): bool
    {
        return $this->isControlRole($user);
    }
}
