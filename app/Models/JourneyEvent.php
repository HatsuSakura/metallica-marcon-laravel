<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyEvent extends Model
{
    protected $fillable = [
        'journey_id',
        'journey_stop_id',
        'type',
        'payload',
        'created_by_user_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(JourneyStop::class, 'journey_stop_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
