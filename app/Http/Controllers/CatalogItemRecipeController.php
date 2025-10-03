<?php
// app/Http/Controllers/CatalogItemRecipeController.php
namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Recipe;
use Inertia\Inertia;

class CatalogItemRecipeController extends Controller
{
    public function editOrCreate(CatalogItem $item)
    {
        // CERCA per 'name' (unico) e AGGIORNA il catalog_item_id, altrimenti CREA.
        $recipe = Recipe::updateOrCreate(
            ['name' => $item->name],             // <- chiave di ricerca (vincolo UNIQUE)
            ['catalog_item_id' => $item->id]     // <- valori da aggiornare/settare
        );

        // carica l’ALBERO delle radici
        $recipe->load('rootNodes');

        // catalogo completo per l'autocomplete locale nell'editor
        $catalog = \App\Models\CatalogItem::select('id','name','type')
            ->orderBy('name')
            ->get();
        
        $from = request('from');
        $backUrl = match ($from) {
            'catalog' => route('catalog-items.index'),
            'recipes' => route('recipes.index'),
            default   => url()->previous(),
        };
        $backLabel = $from === 'recipes' ? 'Ricette' : 'Catalogo (materiali/componenti)';

        return Inertia::render('Admin/Recipes/Edit', [
            'recipe'  => $recipe,
            'nodes'   => $recipe->rootNodes,
            'catalog' => $catalog,
            'backUrl'   => $backUrl,
            'backLabel' => $backLabel,
        ]);
    }
}
