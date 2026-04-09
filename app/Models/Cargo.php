<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Cargo extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $casts = [
        'is_cargo' => 'boolean',
        'is_long' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'description',
        'is_cargo',
        'is_long',
        'length',
        'casse',
        'spazi_bancale',
        'spazi_casse',
        'total_count'
    ];

    protected $auditInclude = [
        'name',
        'description',
        'is_cargo',
        'is_long',
        'length',
        'casse',
        'spazi_bancale',
        'spazi_casse',
        'total_count',
    ];
}
