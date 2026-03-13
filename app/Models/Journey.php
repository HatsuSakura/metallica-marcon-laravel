<?php

namespace App\Models;

use App\Enums\JourneysState;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journey extends Model
{
    use HasFactory, SoftDeletes, VersionableTrait;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $casts = [
        'status' => JourneysState::class,
        'plan_version' => 'integer',

        // datetime
        'planned_start_at'         => 'datetime',
        'planned_end_at'           => 'datetime',
        'actual_start_at'          => 'datetime',
        'actual_end_at'            => 'datetime',
        'primary_warehouse_download_at' => 'datetime',
        'secondary_warehouse_download_at' => 'datetime',

        // boolean
        'is_double_load'           => 'boolean',
        'is_temporary_storage'     => 'boolean',

    ];

    protected $fillable = [
        'planned_start_at',
        'planned_end_at',
        'actual_start_at',
        'actual_end_at',

        'is_double_load',
        'is_temporary_storage',

        'vehicle_id',
        'vehicle_cargo_id',
        'trailer_id',
        'trailer_cargo_id',
        'driver_id',
        'logistics_user_id',
        'status',
        'notes',
        'plan_version',

        // per lo scarico
        'primary_warehouse_id',
        'primary_warehouse_download_at',
        'secondary_warehouse_id',
        'secondary_warehouse_download_at',
    ];

    // Relationship to order items
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }  
    
    public function cargoForVehicle()
    {
        return $this->belongsTo(Cargo::class, 'vehicle_cargo_id');
    }

    public function trailer()
    {
        return $this->belongsTo(Trailer::class, 'trailer_id');
    }

    public function cargoForTrailer()
    {
        return $this->belongsTo(Cargo::class, 'trailer_cargo_id');
    }

    public function journeyCargos()
    {
        return $this->hasMany(JourneyCargo::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(JourneyStop::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(JourneyEvent::class);
    }

    public function stopOrders(): HasMany
    {
        return $this->hasMany(JourneyStopOrder::class);
    }

}
