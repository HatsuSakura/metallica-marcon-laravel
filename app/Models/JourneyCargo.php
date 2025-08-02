<?php

namespace App\Models;

use App\Enums\OrdersTruckLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JourneyCargo extends Model
{
    use HasFactory;


    protected $fillable = [
        'cargo_id',
        'journey_id',
        'truck_location',
        'is_grounding',
        'warehouse_id',
        'download_sequence',
        'state',
    ];

    protected $appends = ['carrier'];

    public function getCarrierAttribute()
    {
        // Retrieve the journey with its vehicle and trailer in one query.
        $journey = $this->journey()->with(['vehicle', 'trailer'])->first();
        if (!$journey) {
            return null;
        }
        
        if ($this->truck_location === OrdersTruckLocation::TRUCK_MOTRICE->value) {
            //return $this->journey ? $this->journey->vehicle : null;
            return [
                'is_vehicle' => true,
                'carrier_data'    => $journey->vehicle,
            ];
        } elseif ($this->truck_location === OrdersTruckLocation::TRUCK_RIMORCHIO->value) {
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
                    ->withPivot('is_double_load', 'warehouse_download_id')
                    ->withTimestamps();
    }
    
    public function doubleLoadItems()
    {
        return $this->orderItems()->wherePivot('is_double_load', true);
    }



    /**
     * Get the carrier type and the associated carrier model instance.
     *
     * @return array|null Returns an associative array with keys:
     *                    - 'is_vehicle': boolean indicating if it's a vehicle.
     *                    - 'carrier': the carrier model instance (vehicle or trailer).
     *                    Returns null if the truck_location is not recognized.
     */
    /*
    public function carrier()
    {
        // Retrieve the journey with its vehicle and trailer in one query.
        $journey = $this->journey()->with(['vehicle', 'trailer'])->first();
        if (!$journey) {
            return null;
        }
        
        if ($this->truck_location === OrdersTruckLocation::TRUCK_MOTRICE->value) {
            return [
                'is_vehicle' => true,
                'carrier_data'    => $journey->vehicle,
            ];
        } elseif ($this->truck_location === OrdersTruckLocation::TRUCK_RIMORCHIO->value) {
            return [
                'is_vehicle' => false,
                'carrier_data'    => $journey->trailer,
            ];
        }
        
        return null;
    }
    
*/

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
