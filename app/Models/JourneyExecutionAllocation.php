<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneyExecutionAllocation extends Model
{
    protected $guarded = [];

    protected $casts = ['containers' => 'integer'];
}
