<?php

namespace App\Models;

use App\Enums\InternalContactRole;
use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class InternalContact extends Model implements AuditableContract
{
    use HasFactory, HasDomainAudit;

    protected $fillable = [
        'name',
        'surname',
        'phone',
        'mobile',
        'email',
        'role',
        'site_id',
    ];

    protected $auditInclude = [
        'name',
        'surname',
        'phone',
        'mobile',
        'email',
        'role',
        'site_id',
    ];

    /*
    It ensures that whenever you access the role field, it will return an instance of the SiteTipologia enum rather than just a string (e.g., 'worker').
    if ($user->role === UserRole::WORKER)
    */
    protected $casts = [
        'role' => InternalContactRole::class,
    ];

    public function site(): BelongsTo{
        return $this->belongsTo(Site::class, 'site_id');
    }



}
