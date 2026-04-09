<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Holder extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'description',
        'volume',
        'is_custom',
        'equivalent_holder_id',
        'equivalent_units',
    ];

    protected $auditInclude = [
        'name',
        'description',
        'volume',
        'is_custom',
        'equivalent_holder_id',
        'equivalent_units',
    ];

    public function equivalentHolder()
    {
        return $this->belongsTo(self::class, 'equivalent_holder_id');
    }
}
