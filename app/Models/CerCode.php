<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CerCode extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $casts = [
        'is_dangerous' => 'boolean',
    ];

    protected $fillable = [
        'code',
        'description',
        'is_dangerous',
    ];

    protected $auditInclude = [
        'code',
        'description',
        'is_dangerous',
    ];
}
