<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Enums\UserRole;
use App\Models\Warehouse;
use App\Enums\OrdersState;
use Illuminate\Http\Request;
use App\Services\OrderItemUpdater;
use App\Services\OrderItemImageUploader;

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
            //'journeyCargos',
            //'journeyCargos.cargo',
            'items.cerCode',
            'items.holder',
            'items.warehouse',
            'items.journeyCargos.cargo',
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
            'journey.journeyCargos',
            'customer',
            'customer.seller',
            'site',
            'items.cerCode',
            'items.holder',
            'items.warehouse',
            'items.journeyCargos.cargo',
            'items.images',
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


        return inertia(
            'Worker/Order/Edit',
            [
                'order' => $order,
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
            $item = $order->items()->find($itemData['id']);

            if ($item) {
                $updater->update($item, $itemData);
            }
        }

        $order->update($validated);

        return redirect()->route('warehouse-manager.orders.index');
    }

}
