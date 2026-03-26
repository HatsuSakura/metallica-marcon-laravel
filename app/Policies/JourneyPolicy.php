<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Journey;

class JourneyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Journey $journey): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Journey $journey): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Journey $journey): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Journey $journey): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Journey $journey): bool
    {
        return true;
    }

    public function dispatchWorkspaceView(User $user, Journey $journey): bool
    {
        if ($this->isLogisticControlRole($user)) {
            return true;
        }

        if ($this->isWarehouseRole($user)) {
            return true;
        }

        if ($user->role === UserRole::DRIVER) {
            return (int) $journey->driver_id === (int) $user->id;
        }

        return false;
    }

    public function dispatchWorkspaceSave(User $user, Journey $journey): bool
    {
        return $this->isLogisticControlRole($user);
    }

    public function dispatchWorkspaceConfirm(User $user, Journey $journey): bool
    {
        return $this->isLogisticControlRole($user);
    }

    public function dispatchWorkspaceAppendWarehouseEvent(User $user, Journey $journey): bool
    {
        return $this->isLogisticControlRole($user) || $this->isWarehouseRole($user);
    }

    public function dispatchWorkspaceAppendTransshipmentProposal(User $user, Journey $journey): bool
    {
        return $this->isLogisticControlRole($user)
            || in_array($user->role, [UserRole::WAREHOUSE_CHIEF, UserRole::WAREHOUSE_MANAGER], true);
    }

    public function dispatchWorkspaceAppendLogisticEvent(User $user, Journey $journey): bool
    {
        return $this->isLogisticControlRole($user);
    }

    public function dispatchTransshipmentApprove(User $user, Journey $journey): bool
    {
        return $this->isLogisticControlRole($user);
    }

    public function dispatchWorkspaceClose(User $user, Journey $journey): bool
    {
        return $this->isLogisticControlRole($user);
    }

    private function isLogisticControlRole(User $user): bool
    {
        return in_array($user->role, [
            UserRole::LOGISTIC,
            UserRole::MANAGER,
            UserRole::DEVELOPER,
        ], true);
    }

    private function isWarehouseRole(User $user): bool
    {
        return in_array($user->role, [
            UserRole::WAREHOUSE_CHIEF,
            UserRole::WAREHOUSE_MANAGER,
            UserRole::WAREHOUSE_WORKER,
        ], true);
    }
}
