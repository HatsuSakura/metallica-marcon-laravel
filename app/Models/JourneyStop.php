<?php

namespace App\Models;

use App\Enums\JourneyStopKind;
use App\Enums\JourneyStopStatus;
use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class JourneyStop extends Model implements AuditableContract
{
    use HasDomainAudit;

    protected $fillable = [
        'journey_id',
        'kind',
        'customer_id',
        'customer_visit_index',
        'technical_action_id',
        'description',
        'planned_sequence',
        'sequence',
        'status',
        'location_lat',
        'location_lng',
        'address_text',
        'started_at',
        'completed_at',
        'reason_code',
        'reason_text',
        'driver_notes',
        'notes',
    ];

    protected $auditInclude = [
        'journey_id',
        'kind',
        'sequence',
        'planned_sequence',
        'customer_id',
        'customer_visit_index',
        'technical_action_id',
        'location_lat',
        'location_lng',
        'address_text',
        'notes',
        'status',
    ];

    protected $casts = [
        'customer_visit_index' => 'integer',
        'planned_sequence' => 'integer',
        'sequence' => 'integer',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'kind' => JourneyStopKind::class,
        'status' => JourneyStopStatus::class,
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technicalAction(): BelongsTo
    {
        return $this->belongsTo(JourneyStopAction::class, 'technical_action_id');
    }

    public function stopOrders(): HasMany
    {
        return $this->hasMany(JourneyStopOrder::class, 'journey_stop_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(JourneyEvent::class, 'journey_stop_id');
    }
}
