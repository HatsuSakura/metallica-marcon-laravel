<?php
// app/Http/Controllers/CatalogItemController.php
namespace App\Http\Controllers;

use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CatalogItemController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', CatalogItem::class);

        $items = CatalogItem::orderBy('name')->paginate(20);
        return Inertia::render('Admin/CatalogItems/Index', [
            'items' => $items,
        ]);
    }

    public function create()
    {
        Gate::authorize('create', CatalogItem::class);

        return Inertia::render('Admin/CatalogItems/Form');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', CatalogItem::class);

        $data = $request->validate([
            'name' => 'required|string|unique:catalog_items',
            'type' => 'required|in:material,component',
        ]);

        CatalogItem::create($data);
        return redirect()->route('catalog-items.index')
            ->with('success', 'Elemento creato');
    }

    public function edit(CatalogItem $catalogItem)
    {
        Gate::authorize('view', $catalogItem);

        return Inertia::render('Admin/CatalogItems/Form', [
            'item' => $catalogItem,
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        Gate::authorize('update', $catalogItem);

        $data = $request->validate([
            'name' => 'required|string|unique:catalog_items,name,' . $catalogItem->id,
            'type' => 'required|in:material,component',
        ]);

        $catalogItem->update($data);
        return redirect()->route('catalog-items.index')
            ->with('success', 'Elemento aggiornato');
    }

    public function destroy(CatalogItem $catalogItem)
    {
        Gate::authorize('delete', $catalogItem);

        $catalogItem->delete();
        return back()->with('success', 'Elemento eliminato');
    }

    // API per autocomplete
    public function search(Request $request)
    {
        Gate::authorize('viewAny', CatalogItem::class);

        $q = $request->input('q', '');
        return CatalogItem::where('name', 'like', "%$q%")
            ->limit(10)
            ->get();
    }
}


