<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Vehicle extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $casts = [
        'has_trailer' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'description',
        'plate',
        'type',
        'has_trailer',
        'load_capacity',
        'driver_id',
        'trailer_id',
    ];

    protected $auditInclude = [
        'name',
        'description',
        'plate',
        'type',
        'has_trailer',
        'load_capacity',
        'driver_id',
        'trailer_id',
    ];

    public function usualDriver()
    {
        return $this->belongsTo(User::class, 'driver_id')->where('role', 'driver');
    }

    public function preferredTrailer()
    {
        return $this->belongsTo(Trailer::class, 'trailer_id');
    }

}
