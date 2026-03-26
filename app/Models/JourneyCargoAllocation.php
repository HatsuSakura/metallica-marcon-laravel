<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JourneyCargoAllocation extends Model
{
    protected $fillable = [
        'journey_id',
        'journey_cargo_id',
        'order_item_id',
        'allocated_containers',
        'estimated_weight_kg',
        'source',
        'is_exception',
        'exception_reason',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'allocated_containers' => 'integer',
        'estimated_weight_kg' => 'decimal:2',
        'is_exception' => 'boolean',
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

    public function instructions(): HasMany
    {
        return $this->hasMany(JourneyCargoUnloadInstruction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}

