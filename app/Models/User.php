<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'role',
        'is_admin',
        'user_code',
        'is_ragnista'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Automatically load the warehouses relationship when the user is retrieved.
     *
     * @var array<string>
     */
    protected $with = ['warehouses'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    /*
    It ensures that whenever you access the role field, it will return an instance of the SiteTipologia enum rather than just a string (e.g., 'worker').
    if ($user->role === UserRole::WORKER)
    */
    protected $casts = [
        'role' => UserRole::class,
    ];

    /**
     * Scope a query to only include drivers.
     */
    public function scopeDrivers($query)
    {
        return $query->where('role', 'driver');
    }

    /** 
     * Accessor
     * Devo utilizzare una sintassi diversa ripsetto al PHP 7
    */
    public function getAvatarAttribute($value){
        return $value ? '/storage/avatars/' . $value : '/storage/avatars/fallback-avatar.png';
    }
    /*

    /**
     * Get the vehicle where this user is the preferred driver.
     */
    public function preferredVehicle()
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    /**
     * The warehouses that this user can operate on.
     */
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse')
                    ->withTimestamps();
    }




    //

}
