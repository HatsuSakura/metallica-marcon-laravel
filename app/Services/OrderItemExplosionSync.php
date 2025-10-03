<?php
// app/Services/OrderItemExplosionSync.php
namespace App\Services;

use App\Models\OrderItem;
use App\Models\OrderItemExplosion;
use Illuminate\Support\Facades\DB;

class OrderItemExplosionSync
{
    /**
     * @param int   $orderItemId
     * @param array $nodes   // [{ catalog_item_id, weight_net?, notes?, recipe_id?, children:[...] }, ...]
     */
    public function sync(int|OrderItem $orderItem, array $nodes): void
    {
         $orderItemId = $orderItem instanceof OrderItem ? $orderItem->id : $orderItem;
        
        // Semplice strategy: DELETE + INSERT (alberi piccoli -> ok)
        OrderItemExplosion::where('order_item_id', $orderItemId)->delete();

        $sort = 1;
        foreach ($nodes as $n) {
            $this->insertNodeRecursive($orderItemId, null, $n, $sort++);
        }
    }

    protected function insertNodeRecursive(int $orderItemId, ?int $parentId, array $node, int $sort): int
    {
        $ciId   = $node['catalog_item_id'] ?? null;
        $notes  = $node['notes'] ?? null;
        $weight = $node['weight_net'] ?? null; // verrà ignorato per i component (ok)
        $rid    = $node['recipe_id'] ?? null;

        $explosion = OrderItemExplosion::create([
            'order_item_id'        => $orderItemId,
            'parent_explosion_id'  => $parentId,
            'catalog_item_id'      => $ciId,
            'explosion_source'     => $rid ? 'recipe' : 'ad_hoc',
            'recipe_id'            => $rid,
            'recipe_version'       => null, // se vuoi puoi valorizzarla
            'weight_net'           => $weight,
            'notes'                => $notes,
            'sort'                 => $sort,
        ]);

        // figli
        $children = $node['children'] ?? [];
        $childSort = 1;
        foreach ($children as $c) {
            $this->insertNodeRecursive($orderItemId, $explosion->id, $c, $childSort++);
        }

        return $explosion->id;
    }
}
