<?php
//WarehouseManagerOrderController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Recipe;
use App\Enums\UserRole;
use App\Models\Warehouse;
use App\Enums\OrdersState;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use App\Services\OrderItemUpdater;
use App\Services\OrderItemImageUploader;
use App\Services\RecipeTreeService;

class WarehouseManagerOrderController extends Controller
{
    public function index(Request $request)
    {
        // Retrieve the logged in user's id
        $warehouseManagerId = $request->user()->id;
        
        // Retrieve orderItems assigned to this warehouse manager
        $orders = Order::query()
        ->where('state', OrdersState::STATE_EXECUTED->value) // for DEBUG PURPOSES ONLY
        //->where('state', OrdersState::STATE_DOWNLOADED->value)
        ->get();

        return inertia(
            'Worker/Order/Index',
            [
                'orders' => $orders
            ],
        );

    }

    public function show(Order $order)
    {

        $order->load([
            'customer',
            'customer.seller',
            'site',
            //'journeyCargo',
            //'journeyCargo.cargo',
            'items.cerCode',
            'items.holder',
            'items.warehouse',
            'items.journeyCargo.cargo',
            'items.images',
        ]);

        return inertia(
            'Worker/Order/Show',
            [
                'order' => $order,
            ],
        );
    }


    public function edit(Order $order)
    {
        $order->load([
            'journey',
            'journey.driver',
            'journey.journeyCargos',
            'journey.journeyCargos.cargo',
            'customer',
            'customer.seller',
            'site',
            'items.cerCode',
            'items.holder',
            'items.warehouse',
            'items.journeyCargos.cargo', /* with()/load() parlano solo con relazioni vere → usa items.journeyCargos.cargo.
                                            L’attributo journey_cargo è solo per l’output e viene popolato grazie all’eager load. */
            'items.images',
            'items.explosions.catalogItem'
        ]);
               
        $warehouseWorkers = User::query()
        ->where('role', UserRole::WAREHOUSE_WORKER)
        ->get();

        $warehouseManagers = User::query()
        ->where('role', UserRole::WAREHOUSE_MANAGER)
        ->get();

        $warehouseChiefs = User::query()
        ->where('role', UserRole::WAREHOUSE_CHIEF)
        ->get();

        // Catalogo per autocomplete (material|component)
        $catalog = CatalogItem::select('id','name','type')
            ->orderBy('name')
            ->get();

        /*
        // Ricette “leggere” solo (id, name, version)
        $recipes = Recipe::select('id','name','version')
            ->orderBy('name')
            ->get();
        */

        // Ricette base (magre) con rootNodes + catalogItem, poi idratiamo via service
        $recipesBase = Recipe::with(['rootNodes.catalogItem', 'catalogItem'])
        ->orderBy('name')
        ->get();
        
        // Idratazione (espansione ricorsiva dei component)
        $recipes = app(RecipeTreeService::class)->buildForRecipes($recipesBase);


        return inertia(
            'Worker/Order/Edit',
            [
                'order' => $order,
                'recipes' => $recipes,   // <-- ricette pronte: {id,name,version,catalog_item,nodes:[...]}
                'catalog' => $catalog,   // per autocomplete in ExplosionEditor
                'warehouses' => Warehouse::all(),
                'warehouseWorkers' => $warehouseWorkers,
                'warehouseManagers' => $warehouseManagers,
                'warehouseChiefs' => $warehouseChiefs,
            ],
        );
    }


    public function update(Request $request, Order $order, OrderItemUpdater $updater)
    {
        $validated = $request->validate([
            'state' => 'required|in:' . implode(',', OrdersState::getValues()),
        ]);

        foreach ($request->input('items', []) as $itemData) {
            if (!isset($itemData['id'])) continue;

            $item = $order->items()->find($itemData['id']);
            if ($item) {
                // Qui NON serve fare altro: update() gestisce anche explosions/images/…
                $updater->update($item, $itemData);
            }
        }

        $order->update($validated);

        return redirect()->route('warehouse-manager.orders.index');
    }

}
