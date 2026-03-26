<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyLoadCensusItem extends Model
{
    protected $fillable = [
        'journey_id',
        'order_item_id',
        'actual_containers',
        'total_weight_kg',
        'notes',
        'source',
        'reported_by_user_id',
    ];

    protected $casts = [
        'actual_containers' => 'integer',
        'total_weight_kg' => 'decimal:2',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }
}

