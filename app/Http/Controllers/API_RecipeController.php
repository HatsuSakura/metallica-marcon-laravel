<?php
// app/Http/Controllers/API_RecipeController.php
namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Recipe;
use App\Models\RecipeNode;

class API_RecipeController extends Controller
{
    /**
     * Ritorna l’albero “risolto” della ricetta per un CatalogItem (component).
     * Regole:
     * - carica la ricetta del catalog item (radici + figli diretti)
     * - per ogni figlio:
     *    - se è MATERIAL -> foglia
     *    - se è COMPONENT:
     *        - se is_override=1: usa i figli salvati su questo nodo (ricorsivo)
     *        - se is_override=0|NULL: espandi la ricetta del component figlio (ricorsivo, by reference)
     * - niente scritture a DB; solo read/merge a runtime
     */
    public function defaultTree()
    {
        $catalogItemId = (int) request('catalog_item_id');

        // Carica il catalog item per sicurezza (e per eventuali check type)
        $item = CatalogItem::select('id','name','type')->findOrFail($catalogItemId);

        // Memoization per evitare N+1 (cache per ricette già viste durante la risoluzione)
        $memoRecipesByCatalogId = [];
        // Guard per evitare loop (es. ricette cicliche, non dovrebbero esistere ma…)
        $visitedCatalogIds = [];

        // Risolvi l’albero “completo” per questo item
        $resolved = $this->resolveRecipeForCatalogItem(
            $item->id,
            $memoRecipesByCatalogId,
            $visitedCatalogIds
        );

        // Ritorna sempre array (radici risolte)
        return response()->json(array_values($resolved));
    }

    /**
     * Risolve la ricetta di un CatalogItem (presume COMPONENT) in un array di nodi serializzati.
     * @param int   $catalogItemId
     * @param array $memoRecipesByCatalogId  memo per ricette già caricate (riduce query)
     * @param array $visitedCatalogIds       set di catalog_item_id visitati (evita loop)
     * @return array[]  nodi “serializzati”
     */
    protected function resolveRecipeForCatalogItem(int $catalogItemId, array &$memoRecipesByCatalogId, array &$visitedCatalogIds): array
    {
        // Anti-loop
        if (isset($visitedCatalogIds[$catalogItemId])) {
            return []; // o lancia eccezione se preferisci
        }
        $visitedCatalogIds[$catalogItemId] = true;

        // Carica (o riusa) la ricetta di questo component:
        $recipe = $memoRecipesByCatalogId[$catalogItemId] ??= Recipe::with([
                // solo radici + figli diretti + catalogItem per ciascun nodo
                'rootNodes' => function ($q) {
                    $q->with([
                        'catalogItem:id,name,type',
                        'children' => function ($cq) {
                            $cq->orderBy('sort')
                               ->with(['catalogItem:id,name,type']);
                        }
                    ])->orderBy('sort');
                },
            ])
            ->where('catalog_item_id', $catalogItemId)
            ->first();

        if (!$recipe) {
            // Nessuna ricetta definita per questo component: ritorna vuoto
            return [];
        }

        $out = [];
        foreach ($recipe->rootNodes as $root) {
            $out[] = $this->resolveNode(
                $root,
                $memoRecipesByCatalogId,
                $visitedCatalogIds
            );
        }
        return $out;
    }

    /**
     * Risolve un singolo nodo:
     * - material  => foglia
     * - component => se is_override=1 usa i suoi children; altrimenti espandi ricetta del component collegato
     * Ritorna array “serializzato” con (catalog_item_id, catalog_item{…}, children[...])
     */
    protected function resolveNode(RecipeNode $node, array &$memoRecipesByCatalogId, array &$visitedCatalogIds): array
    {
        $ci = $node->catalogItem; // eager loaded
        $base = [
            'id'              => $node->id,
            'catalog_item_id' => $node->catalog_item_id,
            'catalog_item'    => $ci ? [
                'id'   => $ci->id,
                'name' => $ci->name,
                'type' => $ci->type,
            ] : null,
            'children'        => [],
        ];

        if (!$ci) {
            return $base; // nodo “orfano” (non dovrebbe succedere), resta senza figli
        }

        if ($ci->type === 'material') {
            // Foglia
            return $base;
        }

        // COMPONENT
        $isOverride = (bool) $node->is_override;

        if ($isOverride) {
            // Usa i figli salvati SU QUESTO NODO (override), ricorsivo
            $node->loadMissing([
                'children' => function ($q) {
                    $q->orderBy('sort')->with(['catalogItem:id,name,type']);
                }
            ]);

            $children = [];
            foreach ($node->children as $child) {
                $children[] = $this->resolveNode($child, $memoRecipesByCatalogId, $visitedCatalogIds);
            }
            $base['children'] = $children;
            return $base;
        }

        // Reference: espandi la ricetta del component figlio
        $resolvedChildRoots = $this->resolveRecipeForCatalogItem(
            $ci->id,
            $memoRecipesByCatalogId,
            $visitedCatalogIds
        );
        $base['children'] = $resolvedChildRoots; // attacca direttamente le radici della ricetta del component
        return $base;
    }

    /*
     * ExplosionEditor – “Applica ricetta” per riga (utente sceglie per ID dalla tendina): usa GET /api/recipes/{recipe}/tree.
     * RecipeNodeEditor – “Importa figli predefiniti” di un componente: usa GET /api/recipes/default-tree?catalog_item_id=.... 
     */
    public function recipeTree(Recipe $recipe)
    {
        // se questa ricetta non è legata a un component puoi anche decidere di tornare []:
        if (!$recipe->catalog_item_id) {
            return response()->json([]);
        }

        // carica radici + figli diretti + catalogItem (come fai in defaultTree)
        $recipe->load([
            'rootNodes' => function ($q) {
                $q->with([
                    'catalogItem:id,name,type',
                    'children' => fn($cq) => $cq->orderBy('sort')->with(['catalogItem:id,name,type']),
                ])->orderBy('sort');
            }
        ]);

        // memo & guard come in defaultTree:
        $memoRecipesByCatalogId = [];
        $visitedCatalogIds = [];

        $out = [];
        foreach ($recipe->rootNodes as $root) {
            $out[] = $this->resolveNode($root, $memoRecipesByCatalogId, $visitedCatalogIds);
        }

        return response()->json($out);
    }



}
