<?php

namespace App\Models;

use App\Enums\OrderDocumentsStatus;
use App\Enums\OrderStatus;
use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Order extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, VersionableTrait, HasDomainAudit;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $casts = [
        'status' => OrderStatus::class,
        'documents_status' => OrderDocumentsStatus::class,
        'is_urgent' => 'boolean',
        'has_crane' => 'boolean',
        'requested_at' => 'datetime',
        'expected_withdraw_at' => 'datetime',
        'fixed_withdraw_at' => 'datetime',
        'actual_withdraw_at' => 'datetime',
        'documents_generated_at' => 'datetime',
    ];
    
    protected $fillable = [
        'legacy_code',
        'is_urgent',
        'requested_at',
        'customer_id',
        'site_id',
        'logistics_user_id',
        'journey_id',
        'status',
        'cargo_location',
        'expected_withdraw_at',
        'fixed_withdraw_at',
        'actual_withdraw_at',
        'worker_id',
        'has_crane',
        'crane_operator_user_id',
        'machinery_time_minutes',
        'notes',
        'documents_status',
        'documents_generated_at',
        'documents_error',
        'documents_version',
    ]; 

    protected $auditInclude = [
        'customer_id',
        'site_id',
        'logistics_user_id',
        'journey_id',
        'status',
        'documents_status',
        'requested_at',
        'expected_withdraw_at',
        'fixed_withdraw_at',
        'actual_withdraw_at',
        'is_urgent',
        'cargo_location',
        'has_crane',
        'crane_operator_user_id',
        'machinery_time_minutes',
        'notes',
    ];

protected static function booted()
    {
        static::creating(function ($order) {
            if (blank($order->status)) {
                $order->status = OrderStatus::STATUS_CREATED->value;
            }

            $year_2  = now()->format('y');                     // es. "25"
            $year_4  = (int) now()->format('Y');               // es. "2025"
            $month   = now()->format('m');                     // es. "06"
            $initial = $order->customer->seller->user_code;    // es. "MMM"

            // operazione atomica in transazione
            $counter = DB::transaction(function () use ($year_4) {
                $row = DB::table('order_counters')
                         ->where('year', $year_4)
                         ->lockForUpdate()
                         ->first();

                if ($row) {
                    DB::table('order_counters')
                      ->where('year', $year_4)
                      ->increment('counter');
                    return $row->counter + 1;
                }

                DB::table('order_counters')->insert([
                    'year'       => $year_4,
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
                $year_2,
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

    public function itemGroups()
    {
        return $this->hasMany(OrderItemGroup::class);
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
        return $this->belongsTo(User::class, 'logistics_user_id');
    }

    public function logisticsUser()
    {
        return $this->belongsTo(User::class, 'logistics_user_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

}
