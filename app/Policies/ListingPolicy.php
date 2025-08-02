<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ListingPolicy
{
    public function before(?User $user, $ability){
        // il ? è il NULL-SAFE operator. Se $user è null, non dà eccezione e skippa la function
        if ($user?->is_admin){
            return true;
        }
        // IS POSSIBLE TO SPECIFY ONLY SOME ABILITIES (ACTIONS)
        /*
        if ($user->is_admin && $ability === 'update'){
            return true;
        }
        */
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Listing $listing): bool
    {
        if ($listing->by_user_id === $user?->id){
            return true;
        }
        return $listing->sold_at === null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // allow everyone who is authenticated -> User is not optional in this case
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Listing $listing): bool
    {
        return ($listing->sold_at === null) && ($user->id === $listing->by_user_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->by_user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Listing $listing): bool
    {
        return $user->id === $listing->by_user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->by_user_id;
    }
}
