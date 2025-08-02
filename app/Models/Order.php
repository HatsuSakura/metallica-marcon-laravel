<?php

namespace App\Models;

use App\Enums\OrdersState;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes, VersionableTrait;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $casts = [
        'state' => OrdersState::class,
    ];
    
    protected $fillable = [
        'legacy_code',
        'is_urgent',
        'requested_at',
        'customer_id',
        'site_id',
        'logistic_id',
        'journey_id',
        'state',
        'truck_location',
        'expected_withdraw_dt',
        'real_withdraw_dt',
        'worker_id',
        'has_ragno',
        'ragnista_id',
        'machinery_time',
    ]; 

protected static function booted()
    {
        static::creating(function ($order) {
            $year    = now()->format('y');                     // es. "25"
            $month   = now()->format('m');                     // es. "06"
            $initial = $order->customer->seller->user_code;    // es. "MMM"

            // operazione atomica in transazione
            $counter = DB::transaction(function () use ($year) {
                $row = DB::table('order_counters')
                         ->where('year', $year)
                         ->lockForUpdate()
                         ->first();

                if ($row) {
                    DB::table('order_counters')
                      ->where('year', $year)
                      ->increment('counter');
                    return $row->counter + 1;
                }

                DB::table('order_counters')->insert([
                    'year'       => $year,
                    'counter'    => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 1;
            });

            // qui popoli legacy_code invece di id_ordine
            $order->legacy_code = sprintf(
                '%s_%d%s_%04d',
                $initial,
                $year,
                $month,
                $counter
            );
        });
    }


    // Relationship to order items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function holders()
    {
        return $this->hasMany(OrderHolder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function journey()
    {
        return $this->belongsTo(Journey::class);
    }

    public function journeyCargos()
    {
        return $this->hasManyThrough(
            JourneyCargo::class,  // il modello finale
            Journey::class,       // il “through” model
            'id',                 // FK sul through (journeys.id) che matcha orders.journey_id
            'journey_id',         // FK sul final (journey_cargos.journey_id)
            'journey_id',         // PK sul parent (orders.journey_id)
            'id'                  // PK sul through (journeys.id)
        );
    }

    public function logistic()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
