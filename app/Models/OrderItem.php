<?php
// app/Models/OrderItem.php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Mpociot\Versionable\VersionableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes, VersionableTrait;

    protected $keepOldVersions = true; // Keep all versions of the model

    protected $fillable = [
        'updated_by_user_id',
        'order_id',
        'journey_cargo_id',
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

    protected $appends = [
        'journey_cargo',
        'warehouse_download'
    ];
    // Serve solo per i calcoli di relazione interna ma non ha senso mostrarlo all'utente
    protected $hidden  = ['journeyCargos'];

    protected $casts = [
        'has_exploded_children' => 'boolean',
        'is_bulk' => 'boolean',
        'custom_l_cm'   => 'decimal:2',
        'custom_w_cm'   => 'decimal:2',
        'custom_h_cm'   => 'decimal:2',
    ];

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
