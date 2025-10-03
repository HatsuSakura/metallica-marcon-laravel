<?php
// app/Http/Controllers/RecipeNodeController.php
namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeNode;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeNodeController extends Controller
{
    public function sync(Recipe $recipe, Request $request)
    {
        $data = $request->validate([
            'nodes'                   => ['required', 'array'],
            'nodes.*.catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],

            // opzionali (se in futuro manderai anche i figli)
            'nodes.*.sort'                 => ['nullable', 'integer'],
            'nodes.*.suggested_ratio'      => ['nullable', 'numeric'],
            'nodes.*.is_override'          => ['nullable', 'boolean'],
            'nodes.*.children'             => ['nullable', 'array'],
            'nodes.*.children.*.catalog_item_id' => ['required_with:nodes.*.children', 'integer', 'exists:catalog_items,id'],
        ]);

        DB::transaction(function () use ($recipe, $data) {
            RecipeNode::where('recipe_id', $recipe->id)->delete();

            $sort = 1;
            foreach ($data['nodes'] as $node) {
                // con payload attuale hai solo {catalog_item_id} (niente children) → ok
                $this->storeNodeRecursive(
                    recipeId:      $recipe->id,
                    parentNodeId:  null,
                    nodeData:      $node,
                    sort:          $node['sort'] ?? $sort++
                );
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Inserisce un nodo e i suoi figli (se presenti) in modo ricorsivo.
     * Ritorna SEMPRE un RecipeNode.
     */
    private function storeNodeRecursive(
        int $recipeId,
        ?int $parentNodeId,
        array $nodeData,
        int $sort = 1
    ): RecipeNode {
        // Se manca catalog_item_id fermiamoci e creiamo comunque un nodo? NO: qui lanciamo eccezione così non si "perde" il return.
        if (!isset($nodeData['catalog_item_id'])) {
            throw new \InvalidArgumentException('catalog_item_id mancante nel nodo della ricetta.');
        }

        $rn = RecipeNode::create([
            'recipe_id'       => $recipeId,
            'parent_node_id'  => $parentNodeId,
            'catalog_item_id' => $nodeData['catalog_item_id'],
            'is_override'     => isset($nodeData['is_override']) ? (int)$nodeData['is_override'] : 0,
            'sort'            => $sort,
            'suggested_ratio' => $nodeData['suggested_ratio'] ?? null,
        ]);

        // Se hai figli nel payload, salvali
        if (!empty($nodeData['children']) && is_array($nodeData['children'])) {
            $childSort = 1;
            foreach ($nodeData['children'] as $child) {
                // Se un figlio è malformato, lo saltiamo in sicurezza SENZA interrompere la catena di return
                if (!isset($child['catalog_item_id'])) {
                    continue;
                }
                $this->storeNodeRecursive(
                    recipeId:     $recipeId,
                    parentNodeId: $rn->id,
                    nodeData:     $child,
                    sort:         $child['sort'] ?? $childSort++
                );
            }
        }

        // 👈 RETURN GARANTITO in ogni circostanza
        return $rn;
    }
}
