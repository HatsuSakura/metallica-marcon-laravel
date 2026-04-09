<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Withdraw extends Model implements AuditableContract
{
    use SoftDeletes, HasDomainAudit;
    
    protected $dates = ['deleted_at'];
    protected $casts = [
        'withdrawn_at' => 'datetime',
        'is_manual_entry' => 'boolean',
    ];
    protected $fillable = [
        'withdrawn_at',
        'residue_percentage',
        'customer_id',
        'site_id',
        'created_by_user_id',
        'vehicle_id',
        'driver_id',
        'is_manual_entry',
    ];

    protected $auditInclude = [
        'withdrawn_at',
        'residue_percentage',
        'customer_id',
        'site_id',
        'vehicle_id',
        'driver_id',
        'is_manual_entry',
    ];

    public function driver(): BelongsTo{
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo{
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function site(): BelongsTo{
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function customer(): BelongsTo{
        return $this->belongsTo(Customer::class, 'customer_id');
    }

}
