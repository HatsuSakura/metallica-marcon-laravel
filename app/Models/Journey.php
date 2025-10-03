<?php

namespace App\Models;

use App\Enums\JourneysState;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journey extends Model
{
    use HasFactory, SoftDeletes, VersionableTrait;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $casts = [
        'state' => JourneysState::class,

        // datetime
        'dt_start'                 => 'datetime',
        'dt_end'                   => 'datetime',
        'real_dt_start'            => 'datetime',
        'real_dt_end'              => 'datetime',
        'warehouse_download_dt_1'  => 'datetime',
        'warehouse_download_dt_2'  => 'datetime',

        // boolean
        'is_double_load'           => 'boolean',
        'is_temporary_storage'     => 'boolean',

    ];

    protected $fillable = [
        'dt_start',
        'dt_end',
        'real_dt_start',
        'real_dt_end',

        'is_double_load',
        'is_temporary_storage',

        'vehicle_id',
        'cargo_for_vehicle_id',
        'trailer_id',
        'cargo_for_trailer_id',
        'driver_id',
        'logistic_id',
        'state',

        // per lo scarico
        'warehouse_id_1',
        'warehouse_download_dt_1',
        'warehouse_id_2',
        'warehouse_download_dt_2',
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
        return $this->belongsTo(Cargo::class, 'cargo_for_vehicle_id');
    }

    public function trailer()
    {
        return $this->belongsTo(Trailer::class, 'trailer_id');
    }

    public function cargoForTrailer()
    {
        return $this->belongsTo(Cargo::class, 'cargo_for_trailer_id');
    }

    public function journeyCargos()
    {
        return $this->hasMany(JourneyCargo::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

}

