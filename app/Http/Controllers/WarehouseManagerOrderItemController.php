<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Holder;
use App\Models\CerCode;
use App\Models\OrderItem;
use App\Models\Warehouse;
use Illuminate\Support\Arr;
use App\Models\JourneyCargo;
use Illuminate\Http\Request;

class WarehouseManagerOrderItemController extends Controller
{
    public function index(Request $request)
    {
        // Retrieve the logged in user's id
        $warehouseManagerId = $request->user()->id;
        
        // Retrieve orderItems assigned to this warehouse manager
        $items = OrderItem::query()
        ->where('warehouse_manager_id', $warehouseManagerId)
        ->with(
            'cerCode:id,code,is_dangerous',
            'holder:id,name,volume',
            'warehouse:id,denominazione',
            'order:id,customer_id',
            'order.customer:id,ragione_sociale'
        )
        ->get();

        return inertia(
            'Warehouse/Item/Show',
            [
                'items' => $items,
            ],
        );

    }

    public function create(Order $order)
    {
        // eager-load direttamente i journeyCargos
        $order->load('journeyCargos','JourneyCargos.cargo');

        return inertia('Warehouse/Order/Item/Create', [
            'order' => $order,
            'cerCodes' => CerCode::all(),
            'holders' => Holder::all(),
            'warehouses' => Warehouse::all(),
            // altri dati
        ]);
    }

    public function store(Request $request, Order $order)
    {

        // 1) valida TUTTO
        $validated = $request->validate([
            'cer_code_id'       => 'required|exists:cer_codes,id',
            'holder_id'         => 'required|exists:holders,id',
            'holder_quantity'   => 'required|integer|min:1',
            'description'       => 'nullable|string|max:255',
            'warehouse_id'      => 'required|exists:warehouses,id',
            'journey_cargo_id'  => 'required|exists:journey_cargos,id',
        ]);

        // 2) estrai i soli campi per order_items
        $itemData = Arr::only($validated, [
            'cer_code_id',
            'holder_id',
            'holder_quantity',
            'description',
            'warehouse_id',
        ])
        + ['is_warehouse_added' => 1]
        ;

        // 3) crea l'order_item
        $item = $order->items()->create($itemData);

        // 4) aggancia la pivot su journey_cargo_order_item
        //    con `warehouse_download_id` preso da `warehouse_id`
        $item->journeyCargos()->attach(
            $validated['journey_cargo_id'],
            [
                'warehouse_download_id' => $validated['warehouse_id'],
                // 'is_double_load' => 0, // se vuoi forzare un default diverso dal valore di default in DB
            ]
        );
        

        return redirect()
            ->route('warehouse-manager.orders.edit', $order->id)
            ->with('success', 'Elemento aggiunto con successo');
    }


}
