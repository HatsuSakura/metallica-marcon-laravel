<?php
// app/Http/Controllers/RecipeController.php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Recipe;
use App\Models\CatalogItem;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with('nodes.catalogItem')->paginate(20);
        return Inertia::render('Admin/Recipes/Index', [
            'recipes' => $recipes,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Recipes/Edit');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'version' => 'nullable|integer|min:1'
        ]);

        $recipe = Recipe::create($data);
        return redirect()->route('recipes.edit', $recipe)->with('success', 'Ricetta inserita');
    }

    public function edit(Recipe $recipe)
    {
            // carica radici + ricorsione + catalog item ovunque
            $recipe->load([
                'rootNodes.childrenRecursive.catalogItem',
                'rootNodes.catalogItem',
                'catalogItem',
            ]);

        $catalog = CatalogItem::select('id','name','type') // type: 'material'|'component'
                    ->orderBy('name')->get();

        $from = request('from');
        $backUrl = match ($from) {
            'catalog' => route('catalog-items.index'),
            'recipes' => route('recipes.index'),
            default   => url()->previous(),
        };
        $backLabel = $from === 'catalog' ? 'Catalogo (materiali/componenti)' : 'Ricette';

        return Inertia::render('Admin/Recipes/Edit', [
            'recipe'  => $recipe,
            'nodes'  => $recipe->rootNodes,
            'catalog' => $catalog,
            'backUrl'   => $backUrl,
            'backLabel' => $backLabel,
        ]);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'version' => 'nullable|integer|min:1'
        ]);

        $recipe->update($data);
        /*
        return redirect()->route('recipes.index')
            ->with('success', 'Ricetta aggiornata');
        */
        return redirect()->route('recipes.edit', $recipe)->with('success', 'Ricetta aggiornata');
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return back()->with('success', 'Ricetta eliminata');
    }
}
