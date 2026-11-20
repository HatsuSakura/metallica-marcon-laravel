<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneyExecutionUpdate extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['operations' => 'array', 'snapshot' => 'array', 'payload' => 'array', 'created_at' => 'datetime'];
}
