<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JourneyStopAction extends Model
{
    protected $table = 'journey_stop_actions';

    protected $fillable = [
        'code',
        'label',
        'requires_location',
        'is_active',
    ];

    protected $casts = [
        'requires_location' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function stops(): HasMany
    {
        return $this->hasMany(JourneyStop::class, 'technical_action_id');
    }
}
