<?php

namespace App\Http\Controllers;

use App\Enums\JourneyCargosState;
use App\Models\User;
use App\Models\Holder;
use App\Enums\UserRole;
use App\Models\CerCode;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Enums\OrdersState;
use App\Models\JourneyCargo;
use App\Models\OrderItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Log;

class WorkerJourneyCargo extends Controller
{

    public function index(Request $request){

        $warehouseManagerId = $request->user()->id;

        $journeyCargos = JourneyCargo::whereHas('items', function ($query) use ($warehouseManagerId) {
            $query->where('warehouse_manager_id', $warehouseManagerId);
        })
        ->with(
            'items',
            'items.cerCode:id,code,description,is_dangerous',
            'items.holder:id,name,volume',
            'items.warehouse:id,denominazione',
            'items.order:id,customer_id',
            'items.order.customer:id,ragione_sociale'
        )
        ->with('cargo')
        ->with('journey')
        ->with('warehouse')
        ->with('warehouseChief')
        ->get();

        $holders = Holder::all();

        $warehouseWorkers = User::query()
        ->where('role', UserRole::WAREHOUSE_WORKER)
        ->get();


        return inertia(
            'Worker/JourneyCargo/Index',
            [
                //'filters' => $filters,
                'journeyCargos' => $journeyCargos,
                'holders' => $holders,
                'warehouseWorkers' => $warehouseWorkers,
            ]);
    }


    public function show(){

        $items = OrderItem::query()
        ->where('warehouse_manager_id', auth()->user->id)
        ->get();


        return inertia(
            'Worker/Item/Show',
            [
                'items' => $items,
            ],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Order::class);

        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $holders = Holder::all();
        $workers = User::where('role', UserRole::DRIVER->value )->get();
        $cerList = CerCode::select('id', 'code', 'description', 'is_dangerous')->get();
        $warehouses = Warehouse::all();

        return inertia('Worker/JourneyCargo/Create', [
            'vehicles' => $vehicles,
            'trailers' => $trailers,
            'holders' => $holders,
            'workers' => $workers,
            'cerList' => $cerList,
            'warehouses' => $warehouses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'is_urgent' => 'boolean',
            'requested_at' => 'required|date',
            'expected_withdraw_dt' => 'nullable|date',
            'customer_id'=> 'required',
            'site_id'=> 'required',
            'logistic_id'=> 'required',
            // 'journey_id' => ALLA CREAZIONE, al momento soprattutto, non viene passato e prende il default NULL
            'items' => 'nullable|array', // Make items optional
            'items.*.cer_code_id' => 'required|exists:cer_codes,id',
            'items.*.holder_id' => 'required|exists:holders,id',
            'items.*.holder_quantity' => 'required|integer|min:1',
            'items.*.description' => 'nullable|string',
            'items.*.weight_declared' => 'required|numeric',
            'items.*.weight_gross' => 'required|numeric',
            'items.*.weight_tare' => 'required|numeric',
            'items.*.weight_net' => 'required|numeric',
            'items.*.adr' => 'nullable|boolean',
            'items.*.adr_onu_code' => 'nullable|string',
            'items.*.adr_hp' => 'nullable|string',
            'items.*.adr_lotto' => 'nullable|string',
            'items.*.adr_volume' => 'nullable|numeric',	 
            'items.*.warehouse_id' => 'nullable|numeric',
            'items.*.warehouse_notes' => 'nullable|string',
            'items.*.worker_id' => 'nullable|numeric',
            'items.*.selection_time' => 'nullable|numeric',
            'items.*.machinery_time' => 'nullable|numeric',
            'items.*.recognized_price' => 'nullable|numeric',
            'items.*.recognized_weight' => 'nullable|numeric',
            'items.*.adr_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_parziale' => 'nullable|boolean',
            'holders' => 'nullable|array', // Make holders optional
            'holders.*.holder_id' => 'required|exists:holders,id',
            'holders.*.holder_piene' => 'required|integer',
            'holders.*.holder_vuote' => 'required|integer',
            'holders.*.holder_totale' => 'required|integer',
        ]);


        $order = Order::create($request->only([
            'is_urgent',
            'requested_at',
            'customer_id',
            'site_id',
            'logistic_id',
        ]));

        // If there are items, attach them to the order
        if (!empty($validatedData['items'])) {
            foreach ($validatedData['items'] as $item) {
                $order->items()->create($item);
            }
        }

        // If there are holders, attach them to the order
        if (!empty($validatedData['holders'])) {
            foreach ($validatedData['holders'] as &$holder) {
                $order->holders()->create($holder);
            }
        }



        /*
        return response()->json([
            'message' => 'Order created successfully!',
            'order' => $order->load('items'),
        ]);
        */

        //return redirect()->route('relator.order.index')->with('success', 'Ritiro inserito con successo!');
        //return redirect()->back()->with('success', 'Ordine inserito con successo!');
        return redirect()->route('relator.customer.show', ['customer' => $request->customer_id])
                 ->with('success', 'Ordine inserito con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JourneyCargo $journeyCargo)
    {
       // Gate::authorize('update', $order);

       $otherJourneyCargo = $journeyCargo->otherCargo();

       $warehouseWorkers = User::query()
       ->where('role', UserRole::WAREHOUSE_WORKER)
       ->get();

       $warehouseManagers = User::query()
       ->where('role', UserRole::WAREHOUSE_MANAGER)
       ->get();
       
       $orders = Order::query()
       ->where('journey_id', $journeyCargo->journey_id)
       ->with([
           'logistic:id,name',
           'customer:id,ragione_sociale', // Load only 'id' and 'ragione_sociale' from customers
           'site',   // Load 'id' 'indirizzo', 'lat', 'lng' from sites
           'items',
           'items.cerCode:id,code,is_dangerous',
           'items.holder:id,name,volume',
           'items.warehouse:id,denominazione',
           'items.order:id,customer_id',
           'items.order.customer:id,ragione_sociale'
       ])
       ->get();

       return inertia(
           'Worker/JourneyCargo/Edit',
           [
               'journeyCargo' => $journeyCargo->load('items', 'items.holder', 'items.warehouse', 'items.cerCode', 'cargo', 'journey', 'warehouse'),
               'orders' => $orders,
               'otherJourneyCargo' => $otherJourneyCargo->load('items', 'items.holder', 'items.warehouse', 'items.cerCode', 'cargo', 'journey', 'warehouse'),
               'warehouseWorkers' => $warehouseWorkers,
               'warehouseManagers' => $warehouseManagers,
           ]
       );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        Gate::authorize('redirectAfterUpdate', $order);

        $validatedData =$request->validate([
            'is_urgent' => 'boolean',
            'requested_at' => 'required|date',
            'expected_withdraw_dt' => 'nullable|date',
            'customer_id'=> 'required|numeric',
            'site_id'=> 'required|numeric',
            'logistic_id'=> 'required|numeric',
            'journey_id' => 'nullable|numeric',
            'real_withdraw_dt' => 'nullable|date',
            'worker_id' => 'nullable|numeric',
            'has_ragno' => 'nullable|boolean',
            'ragnista_id' => 'nullable|numeric',
            'machinery_time' => 'nullable|numeric',
            // ITEMS
            'items' => 'nullable|array', // Make items optional
            'items.*.id' => 'required',
            'items.*.cer_code_id' => 'required|exists:cer_codes,id',
            'items.*.holder_id' => 'required|exists:holders,id',
            'items.*.holder_quantity' => 'required|integer|min:1',
            'items.*.description' => 'nullable|string',
            'items.*.weight_declared' => 'required|numeric',
            'items.*.weight_gross' => 'nullable|numeric',
            'items.*.weight_tare' => 'nullable|numeric',
            'items.*.weight_net' => 'nullable|numeric',
            'items.*.adr' => 'nullable|boolean',
            'items.*.adr_onu_code' => 'nullable|string',
            'items.*.adr_hp' => 'nullable|string',
            'items.*.adr_lotto' => 'nullable|string',
            'items.*.adr_volume' => 'nullable|numeric',	 
            'items.*.warehouse_id' => 'required|numeric',
            'items.*.warehouse_notes' => 'nullable|string',
            'items.*.worker_id' => 'nullable|numeric',
            'items.*.selection_time' => 'nullable|numeric',
            'items.*.machinery_time' => 'nullable|numeric',
            'items.*.recognized_price' => 'nullable|numeric',
            'items.*.recognized_weight' => 'nullable|numeric',
            'items.*.adr_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_parziale' => 'nullable|boolean',
            // HOLDERS
            'holders' => 'nullable|array', // Make holders optional
            'holders.*.holder_id' => 'required|exists:holders,id',
            'holders.*.holder_piene' => 'required|integer',
            'holders.*.holder_vuote' => 'required|integer',
            'holders.*.holder_totale' => 'required|integer',
        ]);
        
        Log::info($order);
        Log::info($validatedData);
        $order->update(
            $validatedData
        );
        Log::info($order);

        // If there are items, update them or create new ones
        if (!empty($validatedData['items'])) {
            $existingItemIds = $order->items()->pluck('id')->toArray();
            $newItemIds = array_column($validatedData['items'], 'id');
            //Log::info($existingItemIds);
            //Log::info($newItemIds);

            // Delete items that are not in the new list
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            $order->items()->whereIn('id', $itemsToDelete)->delete();

            foreach ($validatedData['items'] as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                    // Update existing item
                    $order->items()->where('id', $item['id'])->update($item);
                } else {
                    // Create new item
                    $order->items()->create($item);
                }
            }
        } else {
            // If no items are provided, delete all existing items
            $order->items()->delete();
        }


        // If there are holders, update them or create new ones
        if (!empty($validatedData['holders'])) {
            $existingHolders = $order->holders()->get();
            $newHolders = $validatedData['holders'];
            //Log::info($existingHolders);
            //Log::info($newHolders);

            foreach ($newHolders as $index => $holderData) {
                //Log::info($index);
                //Log::info($holderData);

                if (isset($existingHolders[$index])) {
                    // Update existing holder
                    $existingHolders[$index]->update($holderData);
                } else {
                    // Create new holder
                    $order->holders()->create($holderData);
                }
            }

            // Delete holders that exceed the new list
            if (count($existingHolders) > count($newHolders)) {
                $holdersToDelete = $existingHolders->slice(count($newHolders));
                foreach ($holdersToDelete as $holder) {
                    $holder->delete();
                }
            }
        } else {
            // If no holders are provided, delete all existing holders
            $order->holders()->delete();
        }
        
        $redirectRoute = route('worker.order.index');

        return redirect($redirectRoute)->with('success', 'Ordine aggiornato con successo!');

        //return redirect()->route('relator.order.index')->with('success', 'Ritiro modificato con successo!');
        //return redirect()->back()->with('success', 'Ritiro modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        Gate::authorize('delete', $order);
        $order->deleteOrFail();

        return redirect()->back()->with('success', 'Ritiro cancellato con successo!');
    }

    public function restore(Order $order){
        $order->restore();
        return redirect()->back()->with('success', 'Ritiro ripristinato con successo!');
    }


/*
    CUSTOM actions for Order life cycle 
*/


public function updateState(Order $order, Request $request)
{
    $newState = OrdersState::from($request->new_state);

    if (!OrdersState::from($order->state)->canTransitionTo($newState)) {
        abort(403, 'Invalid state transition.');
    }

    // Add lifecycle-specific logic
    switch ($newState) {
        case OrdersState::STATE_PLANNED:
            $order->planned_date = $request->planned_date;
            break;

        case OrdersState::STATE_EXECUTED:
            $order->executed_at = now();
            break;

        case OrdersState::STATE_DOWNLOADED:
            // Attachments or warehouse updates
            $order->downloaded_files = $request->file('attachments')->store('orders');
            break;
    }

    $order->state = $newState->value;
    $order->save();

    return redirect()->back()->with('success', "Order state updated to {$newState->value}.");
}



}
