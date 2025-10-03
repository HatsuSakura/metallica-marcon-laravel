<?php
// app/Services/ExplosionService.php
namespace App\Services;

use App\Models\OrderItem;
use App\Models\OrderItemExplosion;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExplosionService
{
    /**
     * Crea un nodo "ad hoc" per un order item (opz. sotto un parent_explosion).
     */
    public function createNode(array $data): OrderItemExplosion
    {
        return DB::transaction(function () use ($data) {
            /** @var \App\Models\OrderItem $orderItem */
            $orderItem = OrderItem::query()->findOrFail($data['order_item_id']);

            $node = OrderItemExplosion::create([
                'order_item_id'       => $orderItem->id,
                'parent_explosion_id' => $data['parent_explosion_id'] ?? null,
                'catalog_item_id'     => $data['catalog_item_id'],
                'explosion_source'    => 'ad_hoc',
                'weight_net'          => $data['weight_net'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'sort'                => $data['sort'] ?? 0,
            ]);

            $this->assertWeightBudget($orderItem->id);

            return $node->load(['catalogItem','children']);
        });
    }

    /**
     * Applica una ricetta: istanzia tutti i nodi della ricetta per l'order item.
     * Mantiene la gerarchia recipe_node -> parent_node via parent_explosion_id.
     */
    public function applyRecipe(int $orderItemId, int $recipeId): void
    {
        DB::transaction(function () use ($orderItemId, $recipeId) {
            $orderItem = OrderItem::findOrFail($orderItemId);
            $recipe    = Recipe::with('nodes.children')->findOrFail($recipeId);

            // mappa: recipe_node_id -> explosion_id creato
            $map = [];

            // prendi tutti i nodi in ordine (prima i roots, poi i figli…)
            $nodes = $recipe->nodes()->orderBy('parent_node_id')->orderBy('sort')->get();

            foreach ($nodes as $node) {
                $parentExplosionId = $node->parent_node_id
                    ? ($map[$node->parent_node_id] ?? null)
                    : null;

                $exp = OrderItemExplosion::create([
                    'order_item_id'       => $orderItem->id,
                    'parent_explosion_id' => $parentExplosionId,
                    'catalog_item_id'     => $node->catalog_item_id,
                    'explosion_source'    => 'recipe',
                    'recipe_id'           => $recipe->id,
                    'recipe_version'      => $recipe->version,
                    'sort'                => $node->sort,
                ]);

                $map[$node->id] = $exp->id;
            }

            $this->assertWeightBudget($orderItem->id);
        });
    }

    /**
     * Aggiorna un nodo (es. peso netto) e verifica budget pesi.
     */
    public function updateNode(int $explosionId, array $payload): OrderItemExplosion
    {
        return DB::transaction(function () use ($explosionId, $payload) {
            $node = OrderItemExplosion::findOrFail($explosionId);

            $node->fill([
                'catalog_item_id' => $payload['catalog_item_id'] ?? $node->catalog_item_id,
                'weight_net'      => array_key_exists('weight_net', $payload) ? $payload['weight_net'] : $node->weight_net,
                'notes'           => $payload['notes'] ?? $node->notes,
                'sort'            => $payload['sort'] ?? $node->sort,
            ])->save();

            $this->assertWeightBudget($node->order_item_id);

            return $node->load(['catalogItem','children']);
        });
    }

    /**
     * Elimina un nodo (con i figli, grazie a FK on delete? qui è nullOnDelete, quindi cancelliamo a mano).
     */
    public function deleteNode(int $explosionId): void
    {
        DB::transaction(function () use ($explosionId) {
            $node = OrderItemExplosion::with('children')->findOrFail($explosionId);

            // cancellazione profonda (DFS)
            $stack = [$node];
            while ($stack) {
                $n = array_pop($stack);
                $n->loadMissing('children');
                foreach ($n->children as $c) $stack[] = $c;
                $n->delete();
            }

            $this->assertWeightBudget($node->order_item_id);
        });
    }

    /**
     * Regola: somma pesi esplosi ≤ peso netto dell’order item (se il padre ha peso netto).
     */
    public function assertWeightBudget(int $orderItemId): void
    {
        $row = DB::table('order_items')->select('weight_net')->where('id', $orderItemId)->first();
        if (!$row) return;

        $parentNet = (float) ($row->weight_net ?? 0);
        if ($parentNet <= 0) {
            // se il padre non è pesato, non applichiamo il vincolo (oppure metti "== 0 allora somma figli deve essere 0")
            return;
        }

        $sum = (float) DB::table('order_item_explosions')
            ->where('order_item_id', $orderItemId)
            ->sum('weight_net');

        if ($sum > $parentNet + 1e-6) { // tolleranza
            throw ValidationException::withMessages([
                'weight_net' => "La somma dei pesi dei materiali esplosi ($sum) supera il peso netto dell'item ($parentNet).",
            ]);
        }
    }
}
