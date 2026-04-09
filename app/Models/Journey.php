<?php

namespace App\Models;

use App\Enums\DispatchStatus;
use App\Enums\JourneyStatus;
use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Journey extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, VersionableTrait, HasDomainAudit;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $casts = [
        'status' => JourneyStatus::class,
        'dispatch_status' => DispatchStatus::class,
        'plan_version' => 'integer',

        // datetime
        'planned_start_at'         => 'datetime',
        'planned_end_at'           => 'datetime',
        'actual_start_at'          => 'datetime',
        'actual_end_at'            => 'datetime',
        'primary_warehouse_download_at' => 'datetime',
        'secondary_warehouse_download_at' => 'datetime',
        'dispatch_started_at'          => 'datetime',
        'dispatch_managed_at'          => 'datetime',
        'dispatch_updated_at'          => 'datetime',

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
        'dispatch_status',
        'dispatch_started_at',
        'dispatch_managed_at',
        'dispatch_updated_at',
        'notes',
        'plan_version',

        // per lo scarico
        'primary_warehouse_id',
        'primary_warehouse_download_at',
        'secondary_warehouse_id',
        'secondary_warehouse_download_at',
    ];

    protected $auditInclude = [
        'driver_id',
        'vehicle_id',
        'trailer_id',
        'vehicle_cargo_id',
        'trailer_cargo_id',
        'planned_start_at',
        'planned_end_at',
        'actual_start_at',
        'actual_end_at',
        'status',
        'dispatch_status',
        'notes',
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

    public function loadCensusItems(): HasMany
    {
        return $this->hasMany(JourneyLoadCensusItem::class);
    }

    public function cargoAllocations(): HasMany
    {
        return $this->hasMany(JourneyCargoAllocation::class);
    }

    public function transshipmentNeeds(): HasMany
    {
        return $this->hasMany(TransshipmentNeed::class);
    }

    public function cargoMismatchDecisions(): HasMany
    {
        return $this->hasMany(JourneyCargoMismatchDecision::class);
    }

    public function stopOrders(): HasMany
    {
        return $this->hasMany(JourneyStopOrder::class);
    }

    public function primaryWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'primary_warehouse_id');
    }

    public function secondaryWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'secondary_warehouse_id');
    }

}
