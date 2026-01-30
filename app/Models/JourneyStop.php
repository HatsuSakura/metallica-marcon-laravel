<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JourneyStop extends Model
{
    protected $fillable = [
        'journey_id',
        'kind',
        'customer_id',
        'customer_visit_index',
        'technical_action_id',
        'planned_sequence',
        'sequence',
        'status',
        'location_lat',
        'location_lng',
        'address_text',
        'started_at',
        'completed_at',
        'reason_code',
        'reason_text',
        'notes',
    ];

    protected $casts = [
        'customer_visit_index' => 'integer',
        'planned_sequence' => 'integer',
        'sequence' => 'integer',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technicalAction(): BelongsTo
    {
        return $this->belongsTo(JourneyStopAction::class, 'technical_action_id');
    }

    public function stopOrders(): HasMany
    {
        return $this->hasMany(JourneyStopOrder::class, 'journey_stop_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(JourneyEvent::class, 'journey_stop_id');
    }
}
