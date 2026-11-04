<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneyExecutionItem extends Model
{
    protected $guarded = [];

    protected $casts = ['data' => 'array', 'removed' => 'boolean', 'is_loaded' => 'boolean', 'actual_containers' => 'integer', 'actual_weight_kg' => 'decimal:3'];

    public function allocations()
    {
        return $this->hasMany(JourneyExecutionAllocation::class, 'execution_item_id')->orderBy('cargo_location');
    }

    public function orderExecution()
    {
        return $this->belongsTo(JourneyOrderExecution::class);
    }
}
