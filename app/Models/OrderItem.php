<?php

namespace App\Models;

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
        'holder_id',
        'holder_quantity',
        'description',
        'weight_declared',
        'weight_gross',
        'weight_tare',
        'weight_net',
        'adr',
        'adr_onu_code',
        'adr_hp',
        'adr_lotto',
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
        'warehouse_downaload_worker_id',
        'warehouse_downaload_dt',
        'warehouse_weighing_worker_id',
        'warehouse_weighing_dt',
        'warehouse_selection_worker_id',
        'warehouse_selection_dt',
        'has_selection',
        'selection_time',
        'is_ragnabile',
        'machinery_time_fraction',
        'is_machinery_time_manual',
        'is_transshipment',
        'recognized_price',
        'recognized_weight',
        'adr_totale',
        'adr_esenzione_totale',
        'adr_esenzione_parziale',
    ];

    protected $appends = [
        'journey_cargo',
        'warehouse_download'
    ];
    // Serve solo per i calcoli di relazione interna ma non ha senso mostrarlo all'utente
    protected $hidden  = ['journeyCargos'];


    public function getWarehouseDownloadAttribute()
    {
        // 1) Se la relazione è già caricata, usa quella (veloce, no query extra)
        if ($this->relationLoaded('journeyCargos')) {
            $pivot = $this->journeyCargos
                ->sortByDesc(fn($jc) => optional($jc->pivot)->updated_at)
                ->first()?->pivot;
            $wid = $pivot?->warehouse_download_id;
        } else {
            // 2) Altrimenti leggi la pivot "corrente" direttamente (1 query)
            $wid = DB::table('journey_cargo_order_item')
                ->where('order_item_id', $this->id)
                ->orderByDesc('id')            // oppure created_at/updated_at se preferisci
                ->value('warehouse_download_id');
        }

        if (!$wid) return null;

        // 3) Ritorna solo ciò che serve alla UI
        $wh = Warehouse::select('id', 'denominazione')->find($wid);
        return $wh ? ['id' => $wh->id, 'denominazione' => $wh->denominazione] : null;
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

    public function journeyCargos()
    {
        return $this->belongsToMany(JourneyCargo::class, 'journey_cargo_order_item')
                    ->withPivot('is_double_load', 'warehouse_download_id')
                    ->withTimestamps();
    }

    // 3) l’accessor che restituisce SEMPRE il primo (e unico) JourneyCargo
    public function getJourneyCargoAttribute()
    {
        return $this->journeyCargos->first();
    }

    public function images()
    {
        return $this->hasMany(OrderItemImage::class);
    }


}
