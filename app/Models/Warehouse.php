<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;




    /**
     * The users (WITH EVERY WAREHOUSE_xxx ROLE) that can operate in this warehouse.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_warehouse')
                    ->withTimestamps();
    }

    /**
     * Get the users with the WAREHOUSE_CHIEF role associated with this warehouse.
     */
    public function chiefs()
    {
        return $this->belongsToMany(User::class, 'user_warehouse')
                    ->where('role', UserRole::WAREHOUSE_CHIEF->value)
                    ->withTimestamps();
    }

    /**
     * Get the users with the WAREHOUSE_MANAGER role associated with this warehouse.
     */
    public function managers()
    {
        return $this->belongsToMany(User::class, 'user_warehouse')
                    ->where('role', UserRole::WAREHOUSE_MANAGER->value)
                    ->withTimestamps();
    }

    /**
     * Get the users with the WAREHOUSE_WORKER role associated with this warehouse.
     */
    public function workers()
    {
        return $this->belongsToMany(User::class, 'user_warehouse')
                    ->where('role', UserRole::WAREHOUSE_WORKER->value)
                    ->withTimestamps();
    }


}

/*
Attach a Warehouse to a User:
$user = User::find(1);
// Attach a warehouse with the ID 5; this creates a pivot record in user_warehouse.
$user->warehouses()->attach(5);

Syncing Multiple Warehouses:
// This will update the pivot records for user ID 1 so that the user is linked only to warehouses with IDs 3, 5, and 7.
$user->warehouses()->sync([3, 5, 7]);

Retrieving Related Warehouses:
$warehouses = $user->warehouses; // This gives you a collection of Warehouse models.

Retrieving Users for a Warehouse:
$warehouse = Warehouse::find(5);
$users = $warehouse->users; // Collection of User models assigned to that warehouse.
*/