<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdraw extends Model
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'withdraw_date',
        'percentuale_residua',
        'customer_id',
        'site_id',
        'user_id',
        'vehicle_id',
        'driver_id',
        'insManuale'
    ];

    public function driver(): BelongsTo{
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo{
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

}




