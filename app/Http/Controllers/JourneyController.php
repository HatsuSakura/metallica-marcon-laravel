<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cargo;
use App\Models\Order;
use App\Models\Holder;
use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyStopAction;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Enums\OrdersState;
use App\Enums\JourneysState;
use Illuminate\Http\Request;
use App\Enums\OrdersTruckLocation;
use App\Models\JourneyStop;
use App\Models\JourneyStopOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class JourneyController extends Controller
{


    public function index(Request $request){

        $query = Journey::query()
        //->alphabetic()
        //->withCount('orders')
        //->filter($filters)
        ->with('driver')
        ->with('vehicle')
        ->with('trailer')
        ->paginate(25)
        ->withQueryString();


        return inertia(
            'Journey/Index',
            [
                //'filters' => $filters,
                'journeys' => $query
            ]);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Journey::class);

        // Fetch trucks and drivers (assuming Driver is a special type of User)
        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $cargos = Cargo::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
        $warehouses = Warehouse::all();
        $journeyStopActions = JourneyStopAction::query()
            ->where('is_active', true)
            ->get(['id', 'label']);
        $orders = Order::query()
            ->where('state', OrdersState::STATE_CREATED->value)
            ->with([
                'logistic:id,name',
                'customer:id,ragione_sociale', // Load only 'id' and 'ragione_sociale' from customers
                'site',   // Load 'id' 'indirizzo', 'lat', 'lng' from sites
                'items',
                'items.cerCode:id,code,is_dangerous',
                'items.holder:id,name,volume,is_custom'
            ])
            ->get();

        return inertia('Journey/Create', 
        [
            'vehicles' => $vehicles,
            'trailers' => $trailers,
            'cargos' => $cargos,
            'holders' => $holders,
            'journeyStopActions' => $journeyStopActions,
            'drivers' => $drivers,
            'warehouses' => $warehouses,
            'orders' => $orders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
/*    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dt_start'    => 'required|date',
            'dt_end'      => 'required|date',
            'vehicle_id'  => 'required',
            'trailer_id'  => 'nullable',
            'cargo_for_vehicle_id'  => 'required',
            'cargo_for_trailer_id'  => 'nullable',
            'driver_id'   => 'required',
            'logistic_id' => 'required',
            'orders_truck'    => 'nullable|array',
            'orders_trailer'  => 'nullable|array',
            'orders_fulfill' => 'nullable|array',

            'stops' => 'nullable|array',
            'stops.*.kind' => ['required_with:stops', Rule::in(['customer', 'technical'])],
            'stops.*.sequence' => 'required_with:stops|integer|min:1',
            'stops.*.planned_sequence' => 'nullable|integer|min:1',
            // customer stop
            'stops.*.customer_id' => 'nullable|integer|exists:customers,id',
            'stops.*.customer_visit_index' => 'nullable|integer|min:1',
            // technical stop
            'stops.*.technical_action_id' => 'nullable|integer|exists:journey_stop_actions,id',
            // orders inside stop
            'stops.*.orders' => 'required_with:stops|array',
            'stops.*.orders.*' => 'integer|exists:orders,id',

        ]);

        $journey = Journey::create($validated);

        // Update order states for orders_truck
        if (!empty($validated['orders_truck'])) {
            $orders = Order::whereIn('id', $validated['orders_truck'])->get();
            //Log::info("ORDERS: {$orders}");

            foreach ($orders as $order) {
                $currentState = $order->state;
                if ($currentState instanceof OrdersState ){
                    //Log::info("CURRENT STATE: {$order->state->value}");
                }
                //Log::info("Transition check from {$currentState->value} to " . OrdersState::STATE_PLANNED->value);

                $canTransition = $currentState->canTransitionTo(OrdersState::STATE_PLANNED);
                //Log::info("Can transition? " . ($canTransition ? 'Yes' : 'No'));


                if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED)) {
                    $order->state = OrdersState::STATE_PLANNED;
                    $order->truck_location = OrdersTruckLocation::TRUCK_MOTRICE->value;
                    $order->journey_id = $journey->id;
                    $order->save();
                } else {
                    Log::warning("Invalid state transition from {$currentState->value} for order ID {$order->id}");
                }
            }
        }



        // Repeat for orders_trailer
        if (!empty($validated['orders_trailer'])) {
            $orders = Order::whereIn('id', $validated['orders_trailer'])->get();

            foreach ($orders as $order) {
                $currentState = $order->state;
                if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED)) {
                    $order->state = OrdersState::STATE_PLANNED->value;
                    $order->truck_location = OrdersTruckLocation::TRUCK_RIMORCHIO->value;
                    $order->journey_id = $journey->id;
                    $order->save();
                } else {
                    Log::warning("Invalid state transition for order ID {$order->id}");
                }
            }
        }

        // Repeat for orders_fulfill
        if (!empty($validated['orders_fulfill'])) {
            $orders = Order::whereIn('id', $validated['orders_fulfill'])->get();

            foreach ($orders as $order) {
                $currentState = $order->state;
                if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED)) {
                    $order->state = OrdersState::STATE_PLANNED->value;
                    $order->truck_location = OrdersTruckLocation::TRUCK_RIEMPIMENTO->value;
                    $order->journey_id = $journey->id;
                    $order->save();
                } else {
                    Log::warning("Invalid state transition for order ID {$order->id}");
                }
            }
        }

        return redirect()->route('journey.index')->with('success', 'Viaggio inserito con successo!');
    }
*/
public function store(Request $request)
{
    Gate::authorize('create', Journey::class);

    $validated = $request->validate([
        'dt_start'    => 'required|date',
        'dt_end'      => 'required|date',
        'vehicle_id'  => 'required',
        'trailer_id'  => 'nullable',
        'cargo_for_vehicle_id'  => 'required',
        'cargo_for_trailer_id'  => 'nullable',
        'driver_id'   => 'required',
        'logistic_id' => 'required',

        'orders_truck'    => 'nullable|array',
        'orders_truck.*'  => 'integer|exists:orders,id',

        'orders_trailer'  => 'nullable|array',
        'orders_trailer.*'=> 'integer|exists:orders,id',

        'orders_fulfill' => 'nullable|array',
        'orders_fulfill.*'=> 'integer|exists:orders,id',

        // NEW (opzionale per retrocompatibilità)
        'stops' => 'nullable|array',

        'stops.*.kind' => ['required_with:stops', Rule::in(['customer', 'technical'])],
        'stops.*.sequence' => 'required_with:stops|integer|min:1',
        'stops.*.planned_sequence' => 'nullable|integer|min:1',

        // customer stop
        'stops.*.customer_id' => 'nullable|integer|exists:customers,id',
        'stops.*.customer_visit_index' => 'nullable|integer|min:1',

        // technical stop
        'stops.*.technical_action_id' => 'nullable|integer|exists:journey_stop_actions,id',

        // orders inside stop
        'stops.*.orders' => 'required_with:stops|array',
        'stops.*.orders.*' => 'integer|exists:orders,id',
    ]);

    return DB::transaction(function () use ($validated) {

        // 1) Create Journey (come prima)
        $journey = Journey::create([
            'dt_start' => $validated['dt_start'],
            'dt_end' => $validated['dt_end'],
            'vehicle_id' => $validated['vehicle_id'],
            'trailer_id' => $validated['trailer_id'] ?? null,
            'cargo_for_vehicle_id' => $validated['cargo_for_vehicle_id'],
            'cargo_for_trailer_id' => $validated['cargo_for_trailer_id'] ?? null,
            'driver_id' => $validated['driver_id'],
            'logistic_id' => $validated['logistic_id'],
            // status / plan_version se presenti nel model, altrimenti default DB
        ]);

        // 2) Helper: selezione ordini complessiva (compartments)
        $idsTruck = $validated['orders_truck'] ?? [];
        $idsTrailer = $validated['orders_trailer'] ?? [];
        $idsFulfill = $validated['orders_fulfill'] ?? [];

        $allSelectedIds = array_values(array_unique(array_merge($idsTruck, $idsTrailer, $idsFulfill)));

        // 3) Update Orders (come prima, ma DRY)
        $this->applyOrdersToJourney($journey, $idsTruck, OrdersTruckLocation::TRUCK_MOTRICE->value);
        $this->applyOrdersToJourney($journey, $idsTrailer, OrdersTruckLocation::TRUCK_RIMORCHIO->value);
        $this->applyOrdersToJourney($journey, $idsFulfill, OrdersTruckLocation::TRUCK_RIEMPIMENTO->value);

        // 4) Create Stops if provided
        if (!empty($validated['stops'])) {

            // Cache ordini selezionati (serve per validare customer_id)
            $ordersById = Order::query()
                ->whereIn('id', $allSelectedIds)
                ->get(['id', 'customer_id'])
                ->keyBy('id');

            // Valida che gli ordini dentro stops siano un subset degli ordini selezionati
            $stopOrderIdsFlat = [];
            foreach ($validated['stops'] as $s) {
                foreach (($s['orders'] ?? []) as $oid) $stopOrderIdsFlat[] = (int)$oid;
            }
            $stopOrderIdsFlat = array_values(array_unique($stopOrderIdsFlat));

            // (a) niente ordini “estranei”
            $diff = array_diff($stopOrderIdsFlat, $allSelectedIds);
            if (!empty($diff)) {
                abort(422, 'Gli stop contengono ordini non selezionati nel viaggio: ' . implode(',', $diff));
            }

            // (b) nessun ordine duplicato in 2 stop diversi
            // (a DB ci pensa con uniq (journey_id, order_id), ma meglio dare errore user-friendly)
            $seen = [];
            foreach ($validated['stops'] as $s) {
                foreach (($s['orders'] ?? []) as $oid) {
                    $oid = (int)$oid;
                    if (isset($seen[$oid])) {
                        abort(422, "Ordine {$oid} assegnato a più tappe.");
                    }
                    $seen[$oid] = true;
                }
            }

            // Crea gli stop in ordine di sequence (per stabilità)
            $stopsSorted = $validated['stops'];
            usort($stopsSorted, fn($a, $b) => ($a['sequence'] ?? 0) <=> ($b['sequence'] ?? 0));

            foreach ($stopsSorted as $stopPayload) {
                $kind = $stopPayload['kind'];

                // vincoli minimi coerenti col kind
                if ($kind === 'customer') {
                    if (empty($stopPayload['customer_id'])) abort(422, 'Stop customer senza customer_id');
                }
                if ($kind === 'technical') {
                    if (empty($stopPayload['technical_action_id'])) abort(422, 'Stop technical senza technical_action_id');
                }

                $stop = JourneyStop::create([
                    'journey_id' => $journey->id,
                    'kind' => $kind,
                    'customer_id' => $stopPayload['customer_id'] ?? null,
                    'customer_visit_index' => $stopPayload['customer_visit_index'] ?? null,
                    'technical_action_id' => $stopPayload['technical_action_id'] ?? null,

                    'sequence' => $stopPayload['sequence'],
                    'planned_sequence' => $stopPayload['planned_sequence'] ?? $stopPayload['sequence'],
                    'status' => $stopPayload['status'] ?? 'planned',
                    'notes' => $stopPayload['notes'] ?? null,
                ]);

                // Associa ordini
                foreach (($stopPayload['orders'] ?? []) as $orderId) {
                    $orderId = (int)$orderId;

                    // extra check: ordine deve appartenere al customer dello stop (solo per stop customer)
                    if ($kind === 'customer') {
                        $o = $ordersById->get($orderId);
                        if (!$o) abort(422, "Ordine {$orderId} non trovato tra quelli selezionati.");
                        if ((int)$o->customer_id !== (int)$stop->customer_id) {
                            abort(422, "Ordine {$orderId} non appartiene al customer dello stop (customer_id mismatch).");
                        }
                    }

                    JourneyStopOrder::create([
                        'journey_id' => $journey->id,
                        'journey_stop_id' => $stop->id,
                        'order_id' => $orderId,
                    ]);
                }
            }
        }

        // redirect nuova o legacy: per ora manteniamo legacy
        return redirect()
            ->route('journey.index')
            ->with('success', 'Viaggio inserito con successo!');
    });
}

/**
 * DRY helper: applica journey + truck_location + state agli ordini selezionati.
 */
private function applyOrdersToJourney(Journey $journey, array $orderIds, string $truckLocation): void
{
    if (empty($orderIds)) return;

    $orders = Order::whereIn('id', $orderIds)->get();

    foreach ($orders as $order) {
        $currentState = $order->state;

        if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED)) {
            $order->state = OrdersState::STATE_PLANNED->value; // uniformo a value
            $order->truck_location = $truckLocation;
            $order->journey_id = $journey->id;
            $order->save();
        } else {
            Log::warning("Invalid state transition from {$currentState->value} for order ID {$order->id}");
        }
    }
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Journey $journey)
    {
        Gate::authorize('update', $journey);
        return inertia(
            'Journey/Edit',
            [
                'journey' => $journey
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journey $journey)
    {
        $journey->update([
            $request->validate([
                'customer_id'=> 'required',
                'site_id'=> 'required',
                'logistic_id'=> 'required',
                'items' => 'nullable|array', // Make items optional
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ])
        ]);

        //return redirect()->route('journey.index')->with('success', 'Ritiro modificato con successo!');
        return redirect()->back()->with('success', 'Viaggio modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Journey $journey)
    {
        Gate::authorize('delete', $journey);


        // Retrieve all orders associated with the journey
        $orders = $journey->orders;

        // Update each order
        foreach ($orders as $order) {
            $order->update([
                'journey_id' => null,
                'state' => OrdersState::STATE_CREATED,
                'truck_location' => null,
            ]);
        }


        $journey->deleteOrFail();

        return redirect()->back()->with('success', 'Viaggio cancellato con successo!');
    }

    public function restore(Journey $journey){
        $journey->restore();
        return redirect()->back()->with('success', 'Viaggio ripristinato con successo!');
    }


/*
    CUSTOM actions for Journey life cycle 
*/


public function updateState(Journey $journey, Request $request)
{
    $newState = JourneysState::from($request->new_state);

    if (!JourneysState::from($journey->state)->canTransitionTo($newState)) {
        abort(403, 'Invalid state transition.');
    }

    // Add lifecycle-specific logic
    switch ($newState) {
        case JourneysState::STATE_CREATED:
            $journey->planned_date = $request->planned_date;
            break;

        case JourneysState::STATE_ACTIVE:
            $journey->executed_at = now();
            break;

        case JourneysState::STATE_EXECUTED:
            // Attachments or warehouse updates
            $journey->downloaded_files = $request->file('attachments')->store('journeys');
            break;
    }

    $journey->state = $newState->value;
    $journey->save();

    return redirect()->back()->with('success', "Journey state updated to {$newState->value}.");
}



}
