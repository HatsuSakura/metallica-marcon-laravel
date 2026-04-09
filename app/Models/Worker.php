<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Worker extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $auditInclude = [
        'name',
        'surname',
    ];
}
