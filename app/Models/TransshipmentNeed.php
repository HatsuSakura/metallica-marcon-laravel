<?php

namespace App\Models;

use App\Enums\TranshipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransshipmentNeed extends Model
{
    protected $fillable = [
        'journey_id',
        'order_item_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'quantity_containers',
        'estimated_weight_kg',
        'status',
        'approved_by_user_id',
        'planned_journey_id',
        'notes',
    ];

    protected $casts = [
        'quantity_containers' => 'integer',
        'estimated_weight_kg' => 'decimal:2',
        'status' => TranshipmentStatus::class,
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function plannedJourney(): BelongsTo
    {
        return $this->belongsTo(Journey::class, 'planned_journey_id');
    }
}
