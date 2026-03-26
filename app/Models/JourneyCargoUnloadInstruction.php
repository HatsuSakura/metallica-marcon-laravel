<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyCargoUnloadInstruction extends Model
{
    protected $fillable = [
        'journey_cargo_allocation_id',
        'target_warehouse_id',
        'unload_sequence',
        'instruction_type',
        'planned_target_warehouse_id',
        'proposed_for_transshipment',
        'transshipment_reason',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'unload_sequence' => 'integer',
        'proposed_for_transshipment' => 'boolean',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(JourneyCargoAllocation::class, 'journey_cargo_allocation_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function plannedTargetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'planned_target_warehouse_id');
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

