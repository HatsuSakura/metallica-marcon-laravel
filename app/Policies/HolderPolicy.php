<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Holder;
use App\Models\User;

class HolderPolicy
{
    public function before(?User $user, $ability)
    {
        if ($user?->is_admin) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::LOGISTIC;
    }

    public function view(User $user, Holder $holder): bool
    {
        return $user->role === UserRole::LOGISTIC;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::LOGISTIC;
    }

    public function update(User $user, Holder $holder): bool
    {
        return $user->role === UserRole::LOGISTIC;
    }

    public function delete(User $user, Holder $holder): bool
    {
        return $user->role === UserRole::LOGISTIC;
    }
}
