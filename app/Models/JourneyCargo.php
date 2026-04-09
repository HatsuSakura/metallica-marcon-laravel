<?php

namespace App\Models;

use App\Enums\JourneyCargoStatus;
use App\Enums\OrdersTruckLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JourneyCargo extends Model
{
    use HasFactory;

    protected $casts = [
        'is_grounded' => 'boolean',
        'status' => JourneyCargoStatus::class,
    ];

    protected $fillable = [
        'cargo_id',
        'journey_id',
        'cargo_location',
        'is_grounded',
        'operation_mode',
        'warehouse_id',
        'download_sequence',
        'status',
    ];

    protected $appends = ['carrier'];

    public function getCarrierAttribute()
    {
        // Retrieve the journey with its vehicle and trailer in one query.
        $journey = $this->journey()->with(['vehicle', 'trailer'])->first();
        if (!$journey) {
            return null;
        }
        
        $truckLocation = $this->cargo_location;

        if ($truckLocation === OrdersTruckLocation::TRUCK_MOTRICE->value) {
            //return $this->journey ? $this->journey->vehicle : null;
            return [
                'is_vehicle' => true,
                'carrier_data'    => $journey->vehicle,
            ];
        } elseif ($truckLocation === OrdersTruckLocation::TRUCK_RIMORCHIO->value) {
            //return $this->journey ? $this->journey->trailer : null;
            return [
                'is_vehicle' => false,
                'carrier_data'    => $journey->trailer,
            ];
        }
        
        return null;
    }


    public function journey()
    {
        return $this->belongsTo(Journey::class, 'journey_id');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }


    /*
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    */

    public function items()
    {
        return $this->belongsToMany(OrderItem::class, 'journey_cargo_order_item')
                    ->withPivot('is_double_load', 'download_warehouse_id')
                    ->withTimestamps();
    }

    public function allocations()
    {
        return $this->hasMany(JourneyCargoAllocation::class);
    }

    public function mismatchDecisions()
    {
        return $this->hasMany(JourneyCargoMismatchDecision::class);
    }
    
    public function doubleLoadItems()
    {
        return $this->orderItems()->wherePivot('is_double_load', true);
    }



    /**
     * Get the other JourneyCargo associated with the same Journey.
     *
     * @return JourneyCargo|null
     */
    public function otherCargo()
    {
        return self::where('journey_id', $this->journey_id)
            ->where('id', '!=', $this->id)
            ->first();
    }

}
