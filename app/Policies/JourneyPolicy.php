<?php

namespace App\Policies;

use App\Models\Journey;
use App\Models\User;
use App\Policies\Concerns\AuthorizesDomainRoles;

class JourneyPolicy
{
    use AuthorizesDomainRoles;

    public function viewAny(User $user): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function view(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user)
            || $this->isWarehouseRole($user)
            || $this->isAssignedDriverForJourney($user, $journey);
    }

    public function create(User $user): bool
    {
        return $this->isControlRole($user);
    }

    public function update(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user) || $this->isAssignedDriverForJourney($user, $journey);
    }

    public function delete(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }

    public function restore(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }

    public function forceDelete(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }

    public function warehouseManage(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function dispatchWorkspaceView(User $user, Journey $journey): bool
    {
        return $this->view($user, $journey);
    }

    public function dispatchWorkspaceSave(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }

    public function dispatchWorkspaceConfirm(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }

    public function dispatchWorkspaceAppendWarehouseEvent(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function dispatchWorkspaceAppendTransshipmentProposal(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user) || $this->isWarehouseRole($user);
    }

    public function dispatchWorkspaceAppendLogisticEvent(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }

    public function dispatchTransshipmentApprove(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }

    public function dispatchWorkspaceClose(User $user, Journey $journey): bool
    {
        return $this->isControlRole($user);
    }
}
