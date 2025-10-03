<?php
// app/Models/OrderItemExplosion.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemExplosion extends Model
{
    protected $fillable = [
        'order_item_id',
        'parent_explosion_id',
        'catalog_item_id',
        'explosion_source',
        'recipe_id',
        'recipe_version',
        'weight_net',
        'notes',
        'sort',
    ];

    protected $casts = [
        'order_item_id'        => 'int',
        'parent_explosion_id'  => 'int',
        'catalog_item_id'      => 'int',
        'recipe_id'            => 'int',
        'recipe_version'       => 'int',
        'weight_net'           => 'decimal:3', // conserva 3 decimali
        'sort'                 => 'int',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_explosion_id');
    }

    /*
    public function children()
    {
        return $this->hasMany(self::class, 'parent_explosion_id')
            ->orderBy('sort')
            ->with(['catalogItem', 'children']); // comodo per il tree
    }
    */

    public function children()
    {
        return $this->hasMany(self::class, 'parent_explosion_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with(['childrenRecursive', 'catalogItem']);
    }

    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function recipe() {
        return $this->belongsTo(Recipe::class);
    }

    // utility
    public function getIsComponentAttribute(): bool {
        return optional($this->catalogItem)->type === 'component';
    }
    public function getIsMaterialAttribute(): bool {
        return optional($this->catalogItem)->type === 'material';
    }

    /* helpers */
    public function scopeRoots($q)
    {
        return $q->whereNull('parent_explosion_id')->orderBy('sort');
    }
}
