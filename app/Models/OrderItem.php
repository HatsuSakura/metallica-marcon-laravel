<?php
// app/Models/OrderItem.php
namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Models\Concerns\HasDomainAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class OrderItem extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, VersionableTrait, HasDomainAudit;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $fillable = [
        'updated_by_user_id',
        'order_id',
        'cer_code_id',
        'order_item_group_id',
        'holder_id',
        'holder_quantity',
        'is_bulk',
        'custom_l_cm','custom_w_cm','custom_h_cm',
        'description',
        'weight_declared',
        'weight_gross',
        'weight_tare',
        'weight_net',
        'adr',
        'has_adr',
        'adr_un_code',
        'adr_hp',
        'adr_lotto',
        'adr_lot_code',
        'adr_volume',
        'warehouse_id',
        'warehouse_manager_id',
        'warehouse_notes',
        'is_holder_dirty',
        'total_dirty_holders',
        'is_holder_broken',
        'total_broken_holders',
        'is_warehouse_added',
        'has_non_conformity',
        'warehouse_non_conformity',
        'has_exploded_children',
        'warehouse_download_worker_id',
        'warehouse_download_at',
        'warehouse_weighing_worker_id',
        'warehouse_weighing_dt',
        'warehouse_selection_worker_id',
        'warehouse_selection_dt',
        'has_selection',
        'selection_duration_minutes',
        'is_crane_eligible',
        'machinery_time_fraction',
        'machinery_time_share',
        'is_machinery_time_manual',
        'is_transshipment',
        'recognized_price',
        'recognized_weight',
        'adr_totale',
        'is_adr_total',
        'adr_esenzione_totale',
        'has_adr_total_exemption',
        'adr_esenzione_parziale',
        'has_adr_partial_exemption',
        'status',
    ];

    protected $auditInclude = [
        'order_id',
        'cer_code_id',
        'order_item_group_id',
        'holder_id',
        'holder_quantity',
        'is_bulk',
        'custom_l_cm',
        'custom_w_cm',
        'custom_h_cm',
        'description',
        'weight_declared',
        'weight_gross',
        'weight_tare',
        'weight_net',
        'adr',
        'has_adr',
        'adr_un_code',
        'adr_hp',
        'adr_lot_code',
        'adr_volume',
        'warehouse_id',
        'warehouse_notes',
        'selection_duration_minutes',
        'machinery_time_share',
        'recognized_price',
        'recognized_weight',
        'is_adr_total',
        'has_adr_total_exemption',
        'has_adr_partial_exemption',
        'status',
    ];

    protected $appends = [
        'journey_cargo',
        'warehouse_download'
    ];
    // Serve solo per i calcoli di relazione interna ma non ha senso mostrarlo all'utente
    protected $hidden  = ['journeyCargos'];

    protected $casts = [
        'has_exploded_children' => 'boolean',
        'is_bulk' => 'boolean',
        'adr' => 'boolean',
        'has_adr' => 'boolean',
        'is_holder_dirty' => 'boolean',
        'is_holder_broken' => 'boolean',
        'is_warehouse_added' => 'boolean',
        'has_non_conformity' => 'boolean',
        'adr_totale' => 'boolean',
        'is_adr_total' => 'boolean',
        'adr_esenzione_totale' => 'boolean',
        'has_adr_total_exemption' => 'boolean',
        'adr_esenzione_parziale' => 'boolean',
        'has_adr_partial_exemption' => 'boolean',
        'has_selection' => 'boolean',
        'is_crane_eligible' => 'boolean',
        'is_machinery_time_manual' => 'boolean',
        'is_transshipment' => 'boolean',
        'is_not_found' => 'boolean',
        'warehouse_download_at' => 'datetime',
        'warehouse_weighing_dt' => 'datetime',
        'warehouse_selection_dt' => 'datetime',
        'holder_quantity' => 'integer',
        'total_dirty_holders' => 'integer',
        'total_broken_holders' => 'integer',
        'selection_duration_minutes' => 'integer',
        'custom_l_cm'   => 'decimal:2',
        'custom_w_cm'   => 'decimal:2',
        'custom_h_cm'   => 'decimal:2',
        'status' => OrderItemStatus::class,
    ];

    public function setAdrAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['adr'] = $normalized;
        $this->attributes['has_adr'] = $normalized;
    }

    public function setHasAdrAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['has_adr'] = $normalized;
        $this->attributes['adr'] = $normalized;
    }

    public function setAdrLottoAttribute($value): void
    {
        $normalized = $this->toNullableString($value);
        $this->attributes['adr_lotto'] = $normalized;
        $this->attributes['adr_lot_code'] = $normalized;
    }

    public function setAdrLotCodeAttribute($value): void
    {
        $normalized = $this->toNullableString($value);
        $this->attributes['adr_lot_code'] = $normalized;
        $this->attributes['adr_lotto'] = $normalized;
    }

    public function setAdrTotaleAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['adr_totale'] = $normalized;
        $this->attributes['is_adr_total'] = $normalized;
    }

    public function setIsAdrTotalAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['is_adr_total'] = $normalized;
        $this->attributes['adr_totale'] = $normalized;
    }

    public function setAdrEsenzioneTotaleAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['adr_esenzione_totale'] = $normalized;
        $this->attributes['has_adr_total_exemption'] = $normalized;
    }

    public function setHasAdrTotalExemptionAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['has_adr_total_exemption'] = $normalized;
        $this->attributes['adr_esenzione_totale'] = $normalized;
    }

    public function setAdrEsenzioneParzialeAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['adr_esenzione_parziale'] = $normalized;
        $this->attributes['has_adr_partial_exemption'] = $normalized;
    }

    public function setHasAdrPartialExemptionAttribute($value): void
    {
        $normalized = $this->toBool($value);
        $this->attributes['has_adr_partial_exemption'] = $normalized;
        $this->attributes['adr_esenzione_parziale'] = $normalized;
    }

    public function setMachineryTimeFractionAttribute($value): void
    {
        $normalized = $this->toNullableInt($value);
        $this->attributes['machinery_time_fraction'] = $normalized;
        $this->attributes['machinery_time_share'] = $normalized;
    }

    public function setMachineryTimeShareAttribute($value): void
    {
        $normalized = $this->toNullableInt($value);
        $this->attributes['machinery_time_share'] = $normalized;
        $this->attributes['machinery_time_fraction'] = $normalized;
    }

    private function toBool($value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    private function toNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    public function getWarehouseDownloadAttribute()
    {
        // 1) Se la relazione è già caricata, usa quella (veloce, no query extra)
        if ($this->relationLoaded('journeyCargos')) {
            $pivot = $this->getRelation('journeyCargos')
                ->sortByDesc(fn($jc) => optional($jc->pivot)->updated_at)
                ->first()?->pivot;
                
            $wid = $pivot?->download_warehouse_id;
        } else {
            // 2) Altrimenti leggi la pivot "corrente" direttamente (1 query)
            $wid = DB::table('journey_cargo_order_item')
                ->where('order_item_id', $this->id)
                ->orderByDesc('id')            // oppure created_at/updated_at se preferisci
                ->value('download_warehouse_id');

        }

        if (!$wid) return null;

        // 3) Ritorna solo ciò che serve alla UI
        $wh = Warehouse::select('id', 'name')->find($wid);
        if (!$wh) {
            return null;
        }

        return [
            'id' => $wh->id,
            'name' => $wh->name,
        ];
    }



    // Relationship to order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cerCode()
    {
        return $this->belongsTo(CerCode::class, 'cer_code_id');
    }

    public function orderItemGroup()
    {
        return $this->belongsTo(OrderItemGroup::class, 'order_item_group_id');
    }

    public function holder()
    {
        return $this->belongsTo(Holder::class, 'holder_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function warehouseManager()
    {
        return $this->belongsTo(User::class, 'warehouse_manager_id');
    }

    // 1) la relazione MANY-TO-MANY (più JourneyCargo per ogni OrderItem)
    public function journeyCargos()
    {
        return $this->belongsToMany(JourneyCargo::class, 'journey_cargo_order_item')
                    ->withPivot('is_double_load', 'download_warehouse_id')
                    ->withTimestamps();
    }

    public function loadCensusItems()
    {
        return $this->hasMany(JourneyLoadCensusItem::class);
    }

    public function cargoAllocations()
    {
        return $this->hasMany(JourneyCargoAllocation::class);
    }

    // 2) l’accessor che restituisce SEMPRE il primo (e unico) JourneyCargo
    public function getJourneyCargoAttribute()
    {
        if ($this->relationLoaded('journeyCargos')) {
            return $this->getRelation('journeyCargos')->first(); // nessuna query extra
        }
        return $this->journeyCargos()->first(); // 1 query
    }

    public function images()
    {
        return $this->hasMany(OrderItemImage::class);
    }
    
    public function explosions()
    {
        return $this->hasMany(OrderItemExplosion::class);
    }

    public function explosionsRoot()
    {
        return $this->explosions()->whereNull('parent_explosion_id');
    }

    // questo NON è un relation name “magico”, ma un builder riusabile
    public function explosionsTree()
    {
        return $this->explosionsRoot()->with(['childrenRecursive', 'catalogItem']);
    }

}
