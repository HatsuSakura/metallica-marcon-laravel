<?php
// app/Models/RecipeNode.php
namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RecipeNode extends Model implements AuditableContract
{
    use HasDomainAudit;

    protected $casts = [
        'is_override' => 'boolean',
    ];

    protected $fillable = [
        'recipe_id',
        'parent_node_id',
        'catalog_item_id',
        'sort',
        'suggested_ratio',
        'is_override'
        //'percentage'
    ];

    protected $auditInclude = [
        'recipe_id',
        'parent_node_id',
        'catalog_item_id',
        'sort',
        'suggested_ratio',
        'is_override',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_node_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_node_id')->orderBy('sort');
    }

    public function childrenRecursive()
    {
        return $this->hasMany(self::class, 'parent_node_id')
            ->with(['catalogItem', 'childrenRecursive']) // ricorsiva
            ->orderBy('sort');
    }

    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class);
    }
}
