<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class OrderPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isControlRole($user)
            || $this->isWarehouseRole($user)
            || $this->isAssignedDriverForOrder($user, $order);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user) || $this->isDriver($user);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->isControlRole($user) || $this->isAssignedDriverForOrder($user, $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Order $order): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $this->isControlRole($user);
    }

    public function warehouseManage(User $user, Order $order): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function redirectAfterUpdate(User $user, Order $order): bool
    {
        return $this->update($user, $order);
    }
}
