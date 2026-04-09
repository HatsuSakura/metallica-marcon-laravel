<?php

namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class OrderHolder extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, VersionableTrait, HasDomainAudit;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $fillable = [
        'order_id',
        'holder_id',
        'filled_holders_count',
        'empty_holders_count',
        'total_holders_count'
    ];

    protected $auditInclude = [
        'order_id',
        'holder_id',
        'filled_holders_count',
        'empty_holders_count',
        'total_holders_count',
    ];


    // Relationship to order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function holder()
    {
        return $this->belongsTo(Holder::class, 'holder_id');
    }
}
