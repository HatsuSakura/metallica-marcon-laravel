<?php
// app/Models/Recipe.php
namespace App\Models;

use App\Models\Concerns\HasDomainAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Recipe extends Model implements AuditableContract
{
    use SoftDeletes, HasDomainAudit;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'version',
        'is_active',
        'catalog_item_id',
    ];

    protected $auditInclude = [
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
