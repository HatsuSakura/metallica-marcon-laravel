<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Area extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $auditInclude = [
        'name',
        'polygon',
    ];


    public function sites()
    {
        return $this->belongsToMany(Site::class)
                    ->withPivot('is_preferred')
                    ->withTimestamps();
    }



}
