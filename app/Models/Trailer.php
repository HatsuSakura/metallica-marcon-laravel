<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Trailer extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $casts = [
        'is_front_cargo' => 'boolean',
    ];
    
    protected $fillable = [
        'name',
        'description',
        'plate',
        'is_front_cargo',
        'load_capacity',
    ];

    protected $auditInclude = [
        'name',
        'description',
        'plate',
        'is_front_cargo',
        'load_capacity',
    ];

    /**
     * Get the vehicles that prefer this trailer.
     */
    public function usualDriver()
    {
        return $this->hasOne(Vehicle::class, 'trailer_id');
    }

}
