<?php
// app/Http/Controllers/OrderItemExplosionController.php
namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\OrderItemExplosion;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderItemExplosionController extends Controller
{
    /**
     * Ritorna l’albero salvato per un OrderItem.
     */
    public function show(OrderItem $orderItem)
    {
        $roots = OrderItemExplosion::query()
            ->where('order_item_id', $orderItem->id)
            ->whereNull('parent_explosion_id')
            ->with(['catalogItem:id,name,type', 'children.catalogItem:id,name,type', 'children.children.catalogItem:id,name,type'])
            ->orderBy('sort')
            ->get();

        return response()->json($roots);
    }

    /**
     * Sincronizza l’albero dall’editor (REPLACE atomico).
     * Payload atteso:
     * {
     *   "nodes":[
     *     {
     *       "catalog_item_id": 7,
     *       "recipe_id": 3,              // opzionale (se hai applicato una ricetta a quel nodo component)
     *       "weight_net": null|number,   // valorizzato solo se MATERIAL
     *       "children":[ ... ricorsivo ... ]
     *     }
     *   ]
     * }
     */
    public function sync(OrderItem $orderItem, Request $request)
    {
        $data = $request->validate([
            'nodes'   => ['array'],
            'nodes.*' => ['array'],
            'nodes.*.catalog_item_id' => ['required','integer','exists:catalog_items,id'],
            'nodes.*.recipe_id'       => ['nullable','integer','exists:recipes,id'],
            'nodes.*.weight_net'      => ['nullable','numeric','min:0'],
            'nodes.*.children'        => ['array'],
        ]);

        $nodes = $data['nodes'] ?? [];

        // Validazione ricorsiva: i MATERIAL devono avere/unico il peso, i COMPONENT no (peso derivato)
        $catalogTypes = CatalogItem::query()
            ->whereIn('id', $this->collectCatalogIds($nodes))
            ->pluck('type','id'); // [id => 'material'|'component']

        $sumMaterials = 0.0;
        $this->validateTree($nodes, $catalogTypes, $sumMaterials);

        // (opzionale) Budget: la somma dei MATERIAL non deve superare il netto dell'OrderItem
        if (is_numeric($orderItem->weight_net) && $orderItem->weight_net > 0) {
            if ($sumMaterials > (float)$orderItem->weight_net + 1e-6) {
                throw ValidationException::withMessages([
                    'nodes' => ['La somma dei pesi dei materiali supera il peso netto dell’item.']
                ]);
            }
        }

        // Persistenza: replace atomico
        DB::transaction(function () use ($orderItem, $nodes) {
            OrderItemExplosion::where('order_item_id', $orderItem->id)->delete();
            $this->persistTree($orderItem->id, $nodes, null);
        });

        // risposta fresca
        return $this->show($orderItem);
    }

    /* ----------------- helpers ----------------- */

    private function collectCatalogIds(array $nodes): array {
        $out = [];
        $walk = function($arr) use (&$walk, &$out) {
            foreach ($arr as $n) {
                if (!empty($n['catalog_item_id'])) $out[] = (int)$n['catalog_item_id'];
                if (!empty($n['children'])) $walk($n['children']);
            }
        };
        $walk($nodes);
        return array_values(array_unique($out));
    }

    private function validateTree(array $nodes, $catalogTypes, float &$sumMaterials, string $path = 'nodes'): void
    {
        foreach ($nodes as $i => $n) {
            $ci = (int)($n['catalog_item_id'] ?? 0);
            $type = $catalogTypes[$ci] ?? null;
            if (!$type) {
                throw ValidationException::withMessages([
                    "$path.$i.catalog_item_id" => ["Catalog item non valido."]
                ]);
            }

            $weight = $n['weight_net'] ?? null;

            if ($type === 'material') {
                // foglia: accetto peso numerico >= 0
                if ($weight !== null && !is_numeric($weight)) {
                    throw ValidationException::withMessages([
                        "$path.$i.weight_net" => ["Peso non valido."]
                    ]);
                }
                $sumMaterials += (float)($weight ?? 0);
                // Se vuoi forzare "material deve avere peso" togli il ?? 0 ed aggiungi required
            } else {
                // component: ignora/azzera peso se passato
                if ($weight !== null && (float)$weight > 0) {
                    // non errore fatale: semplicemente non lo useremo
                }
                $children = $n['children'] ?? [];
                $this->validateTree($children, $catalogTypes, $sumMaterials, "$path.$i.children");
            }
        }
    }

    private function persistTree(int $orderItemId, array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $sort => $n) {
            $row = OrderItemExplosion::create([
                'order_item_id'       => $orderItemId,
                'parent_explosion_id' => $parentId,
                'catalog_item_id'     => (int)$n['catalog_item_id'],
                'recipe_id'           => isset($n['recipe_id']) ? (int)$n['recipe_id'] : null,
                'weight_net'          => isset($n['weight_net']) ? (float)$n['weight_net'] : null,
                'notes'               => $n['notes'] ?? null,
                'sort'                => $sort + 1,
            ]);

            $children = $n['children'] ?? [];
            if (!empty($children)) {
                $this->persistTree($orderItemId, $children, $row->id);
            }
        }
    }
}
