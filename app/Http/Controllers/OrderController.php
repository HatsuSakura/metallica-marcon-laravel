<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Site;
use App\Models\User;
use App\Models\Order;
use App\Models\Holder;
use App\Enums\UserRole;
use App\Models\CerCode;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\OrderItemGroupResolver;
use App\Services\OrderDocumentGenerationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{

    public function index(Request $request){

        $query = Order::query()
        //->alphabetic()
        //->withCount('orders')
        //->filter($filters)
        ->with('site')
        ->with('customer')
        ->with('items')
        ->with('holders')
        ->paginate(25)
        ->withQueryString();

        $holders = Holder::all();

        return inertia(
            'Order/Index',
            [
                //'filters' => $filters,
                'orders' => $query,
                'holders' => $holders,
            ]);
    }


        /**
        * Show the form for creating a new resource.
        */
    public function create(Request $request)
    {
        // 1) Recupera il site_id dalla query (?site=123)
        $siteId = $request->integer('site');

        if (!$siteId) {
            // Backward-compat: se proprio vuoi, puoi leggere da sessione/vecchio store qui
            // $siteId = session('current_site_id'); // opzionale
        }

        if (!$siteId) {
            return back()->with('error', 'Seleziona prima una sede (site) per creare un ordine.');
        } 
        
        // 2) Carica il Site con le relazioni utili
        $site = Site::with(['customer','timetable','internalContacts'])->find($siteId);
        if (!$site) {
            return back()->with('error', 'La sede selezionata non esiste.');
        }

        Gate::authorize('create', Order::class);

        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
        $cerList = CerCode::select('id', 'code', 'description', 'is_dangerous')->get();
        $warehouses = Warehouse::all();

        return inertia('Order/Create', [
            'site'     => $site,                // la sede selezionata
            'customer' => $site->customer,      // comodo in FE
            'vehicles' => $vehicles,
            'trailers' => $trailers,
            'holders' => $holders,
            'drivers' => $drivers,
            'cerList' => $cerList,
            'warehouses' => $warehouses
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        Request $request,
        OrderItemGroupResolver $groupResolver
    )
    {

        $CUSTOM_HOLDER_ID = 1;

        $validatedData = $request->validate([
            'is_urgent' => 'boolean',
            'requested_at' => 'required|date',
            'expected_withdraw_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'customer_id'=> 'required',
            'site_id'=> 'required',
            'logistics_user_id' => 'nullable',
            'post_action' => ['nullable', Rule::in(['create_exit', 'create_stay'])],
            // 'journey_id' => ALLA CREAZIONE, al momento soprattutto, non viene passato e prende il default NULL
            'items' => 'nullable|array', // Make items optional
            'items.*.cer_code_id' => 'required|exists:cer_codes,id',
            'items.*.order_item_group_id' => 'nullable|exists:order_item_groups,id',
            'items.*.order_item_group_label' => 'nullable|string|max:120',
            'items.*.is_bulk' => 'required|boolean',
            // se sfuso, ignoriamo il campo; se NON sfuso, è richiesto e min:1
            'items.*.holder_quantity'       => [
                'nullable',     // lo rendiamo ignorabile quando sfuso
                'integer',
                'required_unless:items.*.is_bulk,true',
                'min:0',
                'exclude_if:items.*.is_bulk,true',
            ],
            'items.*.holder_id' => 'nullable|integer|exists:holders,id|prohibited_if:is_bulk,true',

            'items.*.custom_l_cm' => [
                'nullable','numeric','gt:0',
                "required_if:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                "exclude_unless:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                'prohibited_if:items.*.is_bulk,1',
            ],
            'items.*.custom_w_cm' => [
                'nullable','numeric','gt:0',
                "required_if:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                "exclude_unless:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                'prohibited_if:items.*.is_bulk,1',
            ],
            'items.*.custom_h_cm' => [
                'nullable','numeric','gt:0',
                "required_if:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                "exclude_unless:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                'prohibited_if:items.*.is_bulk,1',
            ],

            'items.*.description' => 'nullable|string',
            'items.*.weight_declared' => 'required|numeric',
            'items.*.weight_gross' => 'nullable|numeric',
            'items.*.weight_tare' => 'nullable|numeric',
            'items.*.weight_net' => 'nullable|numeric',
            'items.*.adr' => 'nullable|boolean',
            'items.*.adr_un_code' => 'nullable|string',
            'items.*.adr_hp' => 'nullable|string',
            'items.*.adr_lotto' => 'nullable|string',
            'items.*.adr_volume' => 'nullable|numeric',	 
            'items.*.warehouse_id' => 'nullable|numeric',
            'items.*.warehouse_notes' => 'nullable|string',
            'items.*.worker_id' => 'nullable|numeric',
            'items.*.selection_duration_minutes' => 'nullable|numeric',
            'items.*.machinery_time_share' => 'nullable|numeric',
            'items.*.recognized_price' => 'nullable|numeric',
            'items.*.recognized_weight' => 'nullable|numeric',
            'items.*.adr_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_parziale' => 'nullable|boolean',
            'holders' => 'nullable|array', // Make holders optional
            'holders.*.holder_id' => 'required|exists:holders,id',
            'holders.*.filled_holders_count' => 'required|integer',
            'holders.*.empty_holders_count' => 'required|integer',
            'holders.*.total_holders_count' => 'required|integer',
        ]);


        $order = Order::create([
            'is_urgent' => $validatedData['is_urgent'] ?? false,
            'requested_at' => $validatedData['requested_at'],
            'expected_withdraw_at' => $validatedData['expected_withdraw_at'] ?? null,
            'notes' => $validatedData['notes'] ?? null,
            'customer_id' => $validatedData['customer_id'],
            'site_id' => $validatedData['site_id'],
            'logistics_user_id' => $validatedData['logistics_user_id'] ?? null,
        ]);

        // If there are items, attach them to the order
        if (!empty($validatedData['items'])) {
            $resolvedItems = $groupResolver->resolveForOrder($order, $validatedData['items']);
            foreach ($resolvedItems as $item) {
                $order->items()->create($item);
            }
        }

        // If there are holders, attach them to the order
        if (!empty($validatedData['holders'])) {
            foreach ($validatedData['holders'] as &$holder) {
                $order->holders()->create($holder);
            }
        }

        $postAction = $validatedData['post_action'] ?? 'create_exit';
        if ($postAction === 'create_stay') {
            return redirect()
                ->route('order.edit', ['order' => $order->id])
                ->with('success', 'Ordine inserito con successo!');
        }

        return redirect()
            ->route('customer.show', ['customer' => $order->customer_id])
            ->with('success', 'Ordine inserito con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        Gate::authorize('update', $order);

        $order_items = $order->items()->with('orderItemGroup:id,order_id,cer_code_id,label')->get();
        $order_holders = $order->holders()->get();
        $site = $order->site()->with('customer')->with('internalContacts')->with('timetable')->first();
        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
        $cerList = CerCode::select('id', 'code', 'description', 'is_dangerous')->get();
        $warehouses = Warehouse::all();
    

        return inertia(
            'Order/Edit', [
                'order' => $order,
                'order_items' => $order_items,
                'order_holders' => $order_holders,
                'site' => $site,
                'vehicles' => $vehicles,
                'trailers' => $trailers,
                'holders' => $holders,
                'drivers' => $drivers,
                'cerList' => $cerList,
                'warehouses' => $warehouses
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        Order $order,
        OrderItemGroupResolver $groupResolver,
        OrderDocumentGenerationService $documentsService
    )
    {
        Gate::authorize('redirectAfterUpdate', $order);

        $validatedData =$request->validate([
            'is_urgent' => 'boolean',
            'requested_at' => 'required|date',
            'expected_withdraw_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'customer_id'=> 'required',
            'site_id'=> 'required',
            'logistics_user_id' => 'nullable',
            'journey_id' => 'nullable',
            'post_action' => ['nullable', Rule::in(['save_exit', 'save_stay'])],
            'items' => 'nullable|array', // Make items optional
            'items.*.id' => 'required',
            'items.*.cer_code_id' => 'required|exists:cer_codes,id',
            'items.*.order_item_group_id' => 'nullable|exists:order_item_groups,id',
            'items.*.order_item_group_label' => 'nullable|string|max:120',
            'items.*.holder_id' => 'required|exists:holders,id',
            'items.*.holder_quantity' => 'required|integer|min:1',
            'items.*.description' => 'nullable|string',
            'items.*.weight_declared' => 'required|numeric',
            'items.*.weight_gross' => 'nullable|numeric',
            'items.*.weight_tare' => 'nullable|numeric',
            'items.*.weight_net' => 'nullable|numeric',
            'items.*.adr' => 'nullable|boolean',
            'items.*.adr_un_code' => 'nullable|string',
            'items.*.adr_hp' => 'nullable|string',
            'items.*.adr_lotto' => 'nullable|string',
            'items.*.adr_volume' => 'nullable|numeric',	 
            'items.*.warehouse_id' => 'required|numeric',
            'items.*.warehouse_notes' => 'nullable|string',
            'items.*.worker_id' => 'nullable|numeric',
            'items.*.selection_duration_minutes' => 'nullable|numeric',
            'items.*.machinery_time_share' => 'nullable|numeric',
            'items.*.recognized_price' => 'nullable|numeric',
            'items.*.recognized_weight' => 'nullable|numeric',
            'items.*.adr_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_totale' => 'nullable|boolean',
            'items.*.adr_esenzione_parziale' => 'nullable|boolean',
            'holders' => 'nullable|array', // Make holders optional
            'holders.*.holder_id' => 'required|exists:holders,id',
            'holders.*.filled_holders_count' => 'required|integer|min:0',
            'holders.*.empty_holders_count' => 'required|integer',
            'holders.*.total_holders_count' => 'required|integer',
        ]);
        
        $order->update(array_merge(
            $validatedData,
            [
                'expected_withdraw_at' => $validatedData['expected_withdraw_at'] ?? null,
                'logistics_user_id' => $validatedData['logistics_user_id'] ?? null,
            ]
        ));

        // If there are items, update them or create new ones
        if (!empty($validatedData['items'])) {
            $resolvedItems = $groupResolver->resolveForOrder($order, $validatedData['items']);
            $existingItemIds = $order->items()->pluck('id')->toArray();
            $newItemIds = array_column($resolvedItems, 'id');
            //Log::info($existingItemIds);
            //Log::info($newItemIds);

            // Delete items that are not in the new list
            $itemsToDelete = array_diff($existingItemIds, $newItemIds);
            $order->items()->whereIn('id', $itemsToDelete)->delete();

            foreach ($resolvedItems as $item) {
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

        $groupResolver->cleanupUnusedGroups($order);


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
        
        $documentsService->invalidateAfterReadyOrderEdit($order->refresh());

        $postAction = $validatedData['post_action'] ?? 'save_exit';
        if ($postAction === 'save_stay') {
            return redirect()
                ->route('order.edit', ['order' => $order->id])
                ->with('success', 'Ordine aggiornato con successo!');
        }

        return redirect()
            ->route('order.index')
            ->with('success', 'Ordine aggiornato con successo!');

        //return redirect()->route('order.index')->with('success', 'Ritiro modificato con successo!');
        //return redirect()->back()->with('success', 'Ritiro modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        Gate::authorize('delete', $order);
        $order->deleteOrFail();

        return redirect()->back()->with('success', 'Ordine cancellato con successo!');
    }

    public function restore(Order $order){
        $order->restore();
        return redirect()->back()->with('success', 'Ordine ripristinato con successo!');
    }


/*
    CUSTOM actions for Order life cycle 
*/


public function updateState(Order $order, Request $request)
{
    $newState = OrderStatus::from($request->new_state);

    if (!OrderStatus::from($order->status)->canTransitionTo($newState)) {
        abort(403, 'Invalid state transition.');
    }

    // Add lifecycle-specific logic
    switch ($newState) {
        case OrderStatus::STATUS_PLANNED:
            $order->planned_date = $request->planned_date;
            break;

        case OrderStatus::STATUS_EXECUTED:
            $order->executed_at = now();
            break;

        case OrderStatus::STATUS_DOWNLOADED:
            // Attachments or warehouse updates
            $order->downloaded_files = $request->file('attachments')->store('orders');
            break;
    }

    $order->status = $newState->value;
    $order->save();

    return redirect()->back()->with('success', "Order state updated to {$newState->value}.");
}



}





