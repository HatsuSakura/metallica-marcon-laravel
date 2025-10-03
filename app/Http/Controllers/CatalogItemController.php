<?php
// app/Http/Controllers/CatalogItemController.php
namespace App\Http\Controllers;

use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogItemController extends Controller
{
    public function index()
    {
        $items = CatalogItem::orderBy('name')->paginate(20);
        return Inertia::render('Admin/CatalogItems/Index', [
            'items' => $items,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CatalogItems/Form');
    }

    public function store(Request $request)
    {
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
        return Inertia::render('Admin/CatalogItems/Form', [
            'item' => $catalogItem,
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
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
        $catalogItem->delete();
        return back()->with('success', 'Elemento eliminato');
    }

    // API per autocomplete
    public function search(Request $request)
    {
        $q = $request->input('q', '');
        return CatalogItem::where('name', 'like', "%$q%")
            ->limit(10)
            ->get();
    }
}
