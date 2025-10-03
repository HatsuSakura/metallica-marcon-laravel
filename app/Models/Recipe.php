<?php
// app/Models/Recipe.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'version',
        'is_active',
        'catalog_item_id',
    ];

    public function nodes()
    {
        return $this->hasMany(RecipeNode::class)->with('catalogItem');
    }

    public function rootNodes() // ALBERO: solo radici + ricorsione
    {
        return $this->hasMany(RecipeNode::class)
            ->whereNull('parent_node_id')
            ->with(['catalogItem', 'childrenRecursive']);
    }

    public function catalogItem() {
        return $this->belongsTo(CatalogItem::class);
    }
}
