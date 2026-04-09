<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use App\Policies\Concerns\AuthorizesDomainRoles;

class VehiclePolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $this->isControlRole($user);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Vehicle $vehicle): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Vehicle $vehicle): bool
    {
        return $this->isControlRole($user);
    }
}
