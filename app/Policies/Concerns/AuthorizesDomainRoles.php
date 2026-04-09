<?php

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\Order;
use App\Models\User;

trait AuthorizesDomainRoles
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true;
        }

        return null;
    }

    protected function isControlRole(User $user): bool
    {
        return in_array($user->role, [
            UserRole::DEVELOPER,
            UserRole::MANAGER,
            UserRole::LOGISTIC,
        ], true);
    }

    protected function isWarehouseRole(User $user): bool
    {
        return in_array($user->role, [
            UserRole::WAREHOUSE_CHIEF,
            UserRole::WAREHOUSE_MANAGER,
            UserRole::WAREHOUSE_WORKER,
        ], true);
    }

    protected function isDriver(User $user): bool
    {
        return $user->role === UserRole::DRIVER;
    }

    protected function isAssignedDriverForJourney(User $user, Journey $journey): bool
    {
        return $this->isDriver($user) && (int) $journey->driver_id === (int) $user->id;
    }

    protected function isAssignedDriverForOrder(User $user, Order $order): bool
    {
        if (!$this->isDriver($user)) {
            return false;
        }

        $journey = $order->relationLoaded('journey') ? $order->journey : $order->journey()->first();

        return $journey !== null && (int) $journey->driver_id === (int) $user->id;
    }
}
