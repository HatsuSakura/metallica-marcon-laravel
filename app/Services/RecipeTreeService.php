<?php
// app/Services/RecipeTreeService.php
namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeNode;
use App\Models\CatalogItem;

class RecipeTreeService
{
    /** @var array<int, array> cache children espansi per component (key = catalog_item_id) */
    protected array $expandedComponentCache = [];

    /** @var array<int, Recipe|null> cache active recipe per component (key = catalog_item_id) */
    protected array $activeRecipeCache = [];

    /**
     * Costruisce l'albero "idrattato" per più ricette.
     * @param \Illuminate\Support\Collection<Recipe> $recipes
     * @return array<int, array>  // array di ricette pronte per il frontend
     */
    public function buildForRecipes($recipes): array
    {
        // assicuriamoci di avere rootNodes + catalogItem
        $recipes->loadMissing(['rootNodes.catalogItem', 'catalogItem']);

        $out = [];
        foreach ($recipes as $r) {
            $out[] = $this->buildForRecipe($r);
        }
        return $out;
    }

    /**
     * Costruisce l'albero "idrattato" per una singola ricetta.
     */
    public function buildForRecipe(Recipe $recipe): array
    {
        $visited = []; // insieme di catalog_item_id dei component già visti lungo il ramo
        $nodes = [];
        foreach ($recipe->rootNodes as $rn) {
            $nodes[] = $this->expandNode($rn, $visited);
        }

        return [
            'id'           => $recipe->id,
            'name'         => $recipe->name,
            'version'      => $recipe->version,
            'catalog_item' => $recipe->catalogItem?->only(['id','name','type']),
            'nodes'        => $nodes,
        ];
    }

    /**
     * Espande un nodo: se material => foglia; se component => children = active recipe del component.
     * Ritorna array pronto per il FE (no istanze Eloquent).
     */
    protected function expandNode(RecipeNode $node, array $visited): array
    {
        $ci = $node->catalogItem; // deve essere eager-loaded
        $base = [
            'id'              => $node->id,
            'catalog_item_id' => $node->catalog_item_id,
            'catalog_item'    => $ci ? $ci->only(['id','name','type']) : null,
            'sort'            => $node->sort,
            'suggested_ratio' => $node->suggested_ratio,
            'is_override'     => (bool) $node->is_override,
            'children'        => [],
        ];

        if (!$ci || $ci->type !== 'component') {
            // MATERIAL o catalog item assente → foglia
            return $base;
        }

        // Protezione contro cicli
        if (in_array($ci->id, $visited, true)) {
            $base['children'] = []; // oppure potresti mettere un flag per indicare ciclo
            $base['cycle_protected'] = true;
            return $base;
        }

        // Espansione component: usa figli dalla sua active recipe
        $children = $this->expandedChildrenForComponent($ci->id, [...$visited, $ci->id]);
        $base['children'] = $children;

        return $base;
    }

    /**
     * Restituisce i figli espansi per un component (catalog_item_id).
     * Usa cache; costruisce prendendo la ACTIVE recipe del component (se presente).
     *
     * @param int   $componentCatalogItemId
     * @param array $visited  // per propagare protezione ciclo ai figli
     * @return array  // array di nodi (children) già espansi
     */
    protected function expandedChildrenForComponent(int $componentCatalogItemId, array $visited): array
    {
        if (array_key_exists($componentCatalogItemId, $this->expandedComponentCache)) {
            return $this->expandedComponentCache[$componentCatalogItemId];
        }

        $active = $this->getActiveRecipeForComponent($componentCatalogItemId);
        if (!$active) {
            // nessuna recipe → component considerato foglia
            return $this->expandedComponentCache[$componentCatalogItemId] = [];
        }

        // rootNodes della recipe del component
        $active->loadMissing(['rootNodes.catalogItem']);
        $children = [];
        foreach ($active->rootNodes as $rn) {
            $children[] = $this->expandNode($rn, $visited);
        }

        return $this->expandedComponentCache[$componentCatalogItemId] = $children;
    }

    /**
     * Trova la ACTIVE recipe per un component (catalog_item_id).
     * Strategia: is_active = 1, e in caso di più versioni, la più recente.
     * (Se in futuro avrai active_recipe_id sul CatalogItem, puoi usare quello).
     */
    protected function getActiveRecipeForComponent(int $componentCatalogItemId): ?Recipe
    {
        if (array_key_exists($componentCatalogItemId, $this->activeRecipeCache)) {
            return $this->activeRecipeCache[$componentCatalogItemId];
        }

        $recipe = Recipe::query()
            ->where('catalog_item_id', $componentCatalogItemId)
            ->where('is_active', 1)
            ->orderByDesc('version')
            ->first();

        return $this->activeRecipeCache[$componentCatalogItemId] = $recipe ?: null;
    }
}
