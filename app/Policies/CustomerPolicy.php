<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class CustomerPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $this->isControlRole($user);
    }
}
