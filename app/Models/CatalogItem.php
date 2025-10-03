<?php
// app/Models/CatalogItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name','type','code','is_active','parent_catalog_item_id',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_catalog_item_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_catalog_item_id');
    }

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function defaultRecipe() {    // 1-a-1 opzionale
        return $this->hasOne(Recipe::class, 'catalog_item_id');
    }

}
