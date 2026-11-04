<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneyOrderExecution extends Model
{
    protected $guarded = [];

    protected $casts = ['data' => 'array', 'documents_generated_at' => 'datetime', 'documents_generation_started_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(JourneyExecutionItem::class, 'order_execution_id');
    }

    public function holders()
    {
        return $this->hasMany(JourneyExecutionHolder::class, 'order_execution_id');
    }

    public function journey()
    {
        return $this->belongsTo(Journey::class);
    }
}
