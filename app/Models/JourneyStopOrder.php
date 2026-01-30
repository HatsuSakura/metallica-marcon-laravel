<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyStopOrder extends Model
{
    protected $table = 'journey_stop_orders';

    protected $fillable = [
        'journey_id',
        'journey_stop_id',
        'order_id',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(JourneyStop::class, 'journey_stop_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
