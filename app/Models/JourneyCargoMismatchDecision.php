<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyCargoMismatchDecision extends Model
{
    protected $fillable = [
        'journey_id',
        'journey_cargo_id',
        'order_item_id',
        'decision',
        'secondary_warehouse_id',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function journeyCargo(): BelongsTo
    {
        return $this->belongsTo(JourneyCargo::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function secondaryWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'secondary_warehouse_id');
    }
}

