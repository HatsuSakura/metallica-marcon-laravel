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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JourneyController extends Controller
{


    public function index(Request $request){

        $activeTab = $request->query('tab', 'tutti');

        $drivers = User::query()
            ->where('role', UserRole::DRIVER->value)
            ->orderBy('name')
            ->orderBy('surname')
            ->get(['id', 'name', 'surname']);

        $vehiclesForFilter = Vehicle::query()
            ->orderBy('plate')
            ->get(['id', 'plate', 'name']);

        $createdJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer')
            ->where('state', JourneysState::STATE_CREATED->value)
            ->orderByDesc('dt_start')
            ->get();

        $activeJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer')
            ->where('state', JourneysState::STATE_ACTIVE->value)
            ->orderByDesc('dt_start')
            ->get();

        $allJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer');
        $this->applyJourneyIndexFilters($allJourneys, $request);
        $allJourneys = $allJourneys
            ->orderByDesc('dt_start')
            ->paginate(25, ['*'], 'all_page')
            ->appends($request->query());

        $executedJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer')
            ->where('state', JourneysState::STATE_EXECUTED->value);
        $this->applyJourneyIndexFilters($executedJourneys, $request);
        $executedJourneys = $executedJourneys
            ->orderByDesc('dt_start')
            ->paginate(25, ['*'], 'executed_page')
            ->appends($request->query());

        $closedJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer')
            ->where('state', JourneysState::STATE_CLOSED->value);
        $this->applyJourneyIndexFilters($closedJourneys, $request);
        $closedJourneys = $closedJourneys
            ->orderByDesc('dt_start')
            ->paginate(25, ['*'], 'closed_page')
            ->appends($request->query());


        return inertia(
            'Journey/Index',
            [
                'createdJourneys' => $createdJourneys,
                'activeJourneys' => $activeJourneys,
                'allJourneys' => $allJourneys,
                'executedJourneys' => $executedJourneys,
                'closedJourneys' => $closedJourneys,
                'drivers' => $drivers,
                'vehiclesForFilter' => $vehiclesForFilter,
                'filters' => [
                    'date_from' => $request->query('date_from'),
                    'date_to' => $request->query('date_to'),
                    'driver_id' => $request->query('driver_id'),
                    'vehicle_id' => $request->query('vehicle_id'),
                ],
                'activeTab' => in_array(
                    $activeTab,
                    [
                        'tutti',
                        JourneysState::STATE_CREATED->value,
                        JourneysState::STATE_ACTIVE->value,
                        JourneysState::STATE_EXECUTED->value,
                        JourneysState::STATE_CLOSED->value,
                    ],
                    true
                ) ? $activeTab : 'tutti',
            ]);
    }

    private function applyJourneyIndexFilters(Builder $query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('dt_start', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('dt_start', '<=', $request->query('date_to'));
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', (int) $request->query('driver_id'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->query('vehicle_id'));
        }
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
            'stops.*.location_lat' => 'nullable|numeric',
            'stops.*.location_lng' => 'nullable|numeric',
            'stops.*.description' => 'nullable|string',
            'stops.*.address_text' => 'nullable|string',
            // orders inside stop (required only for customer stops)
            'stops.*.orders' => 'nullable|array',
            'stops.*.orders.*' => 'integer|exists:orders,id',

        ]);

        // Enforce orders only for customer stops
        if (!empty($validated['stops'])) {
            foreach ($validated['stops'] as $i => $s) {
                if (($s['kind'] ?? null) === 'customer' && empty($s['orders'])) {
                    throw ValidationException::withMessages([
                        "stops.$i.orders" => "The stops.$i.orders field is required when stop kind is customer.",
                    ]);
                }
            }
        }

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
        'stops.*.location_lat' => 'nullable|numeric',
        'stops.*.location_lng' => 'nullable|numeric',
        'stops.*.description' => 'nullable|string',
        'stops.*.address_text' => 'nullable|string',

        // orders inside stop (required only for customer stops)
        'stops.*.orders' => 'nullable|array',
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
                    'description' => $stopPayload['description'] ?? null,
                    'location_lat' => $stopPayload['location_lat'] ?? null,
                    'location_lng' => $stopPayload['location_lng'] ?? null,

                    'sequence' => $stopPayload['sequence'],
                    'planned_sequence' => $stopPayload['planned_sequence'] ?? $stopPayload['sequence'],
                    'status' => $stopPayload['status'] ?? 'planned',
                    'address_text' => $stopPayload['address_text'] ?? null,
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
private function applyOrdersToJourney(
    Journey $journey,
    array $orderIds,
    string $truckLocation,
    bool $allowAlreadyPlannedForCurrentJourney = false
): void
{
    if (empty($orderIds)) return;

    $orders = Order::whereIn('id', $orderIds)->get();

    foreach ($orders as $order) {
        $currentState = $order->state;

        $isPlannedInCurrentJourney = $allowAlreadyPlannedForCurrentJourney
            && $currentState === OrdersState::STATE_PLANNED
            && (int) $order->journey_id === (int) $journey->id;

        if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED) || $isPlannedInCurrentJourney) {
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

        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $cargos = Cargo::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value)->get();
        $warehouses = Warehouse::all();
        $journeyStopActions = JourneyStopAction::query()
            ->where('is_active', true)
            ->get(['id', 'label']);

        $orders = Order::query()
            ->where(function ($q) use ($journey) {
                $q->where('state', OrdersState::STATE_CREATED->value)
                    ->orWhere('journey_id', $journey->id);
            })
            ->with([
                'logistic:id,name',
                'customer:id,ragione_sociale',
                'site',
                'items',
                'items.cerCode:id,code,is_dangerous',
                'items.holder:id,name,volume,is_custom',
            ])
            ->get();

        $journey = $journey->load([
            'orders',
            'stops' => fn ($q) => $q->orderBy('sequence'),
            'stops.stopOrders',
        ]);

        return inertia(
            'Journey/Edit',
            [
                'journey' => $journey,
                'vehicles' => $vehicles,
                'trailers' => $trailers,
                'cargos' => $cargos,
                'holders' => $holders,
                'journeyStopActions' => $journeyStopActions,
                'drivers' => $drivers,
                'warehouses' => $warehouses,
                'orders' => $orders,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journey $journey)
    {
        Gate::authorize('update', $journey);

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

            'stops' => 'nullable|array',
            'stops.*.kind' => ['required_with:stops', Rule::in(['customer', 'technical'])],
            'stops.*.sequence' => 'required_with:stops|integer|min:1',
            'stops.*.planned_sequence' => 'nullable|integer|min:1',
            'stops.*.customer_id' => 'nullable|integer|exists:customers,id',
            'stops.*.customer_visit_index' => 'nullable|integer|min:1',
            'stops.*.technical_action_id' => 'nullable|integer|exists:journey_stop_actions,id',
            'stops.*.location_lat' => 'nullable|numeric',
            'stops.*.location_lng' => 'nullable|numeric',
            'stops.*.description' => 'nullable|string',
            'stops.*.address_text' => 'nullable|string',
            'stops.*.orders' => 'nullable|array',
            'stops.*.orders.*' => 'integer|exists:orders,id',
        ]);

        return DB::transaction(function () use ($validated, $journey) {
            $journey = Journey::query()
                ->whereKey($journey->id)
                ->lockForUpdate()
                ->firstOrFail();

            $journeyState = $journey->state instanceof JourneysState
                ? $journey->state
                : JourneysState::from((string) $journey->state);

            if ($journeyState !== JourneysState::STATE_CREATED) {
                abort(422, 'Il viaggio puo essere modificato solo quando e in stato creato.');
            }

            $journey->update([
                'dt_start' => $validated['dt_start'],
                'dt_end' => $validated['dt_end'],
                'vehicle_id' => $validated['vehicle_id'],
                'trailer_id' => $validated['trailer_id'] ?? null,
                'cargo_for_vehicle_id' => $validated['cargo_for_vehicle_id'],
                'cargo_for_trailer_id' => $validated['cargo_for_trailer_id'] ?? null,
                'driver_id' => $validated['driver_id'],
                'logistic_id' => $validated['logistic_id'],
            ]);

            $idsTruck = $validated['orders_truck'] ?? [];
            $idsTrailer = $validated['orders_trailer'] ?? [];
            $idsFulfill = $validated['orders_fulfill'] ?? [];

            $overlapTruckTrailer = array_intersect($idsTruck, $idsTrailer);
            $overlapTruckFulfill = array_intersect($idsTruck, $idsFulfill);
            $overlapTrailerFulfill = array_intersect($idsTrailer, $idsFulfill);
            if (!empty($overlapTruckTrailer) || !empty($overlapTruckFulfill) || !empty($overlapTrailerFulfill)) {
                abort(422, 'Un ordine non puo essere assegnato a piu comparti.');
            }

            $allSelectedIds = array_values(array_unique(array_merge($idsTruck, $idsTrailer, $idsFulfill)));

            $foreignAllocated = Order::query()
                ->whereIn('id', $allSelectedIds)
                ->whereNotNull('journey_id')
                ->where('journey_id', '!=', $journey->id)
                ->exists();
            if ($foreignAllocated) {
                abort(422, 'Alcuni ordini selezionati sono gia assegnati a un altro viaggio.');
            }

            $currentJourneyOrderIds = Order::query()
                ->where('journey_id', $journey->id)
                ->pluck('id')
                ->all();

            $toDetach = array_values(array_diff($currentJourneyOrderIds, $allSelectedIds));
            if (!empty($toDetach)) {
                Order::query()
                    ->whereIn('id', $toDetach)
                    ->update([
                        'journey_id' => null,
                        'truck_location' => null,
                        'state' => OrdersState::STATE_CREATED->value,
                    ]);
            }

            $this->applyOrdersToJourney($journey, $idsTruck, OrdersTruckLocation::TRUCK_MOTRICE->value, true);
            $this->applyOrdersToJourney($journey, $idsTrailer, OrdersTruckLocation::TRUCK_RIMORCHIO->value, true);
            $this->applyOrdersToJourney($journey, $idsFulfill, OrdersTruckLocation::TRUCK_RIEMPIMENTO->value, true);

            JourneyStopOrder::query()->where('journey_id', $journey->id)->delete();
            JourneyStop::query()->where('journey_id', $journey->id)->delete();

            if (!empty($validated['stops'])) {
                $ordersById = Order::query()
                    ->whereIn('id', $allSelectedIds)
                    ->get(['id', 'customer_id'])
                    ->keyBy('id');

                $stopOrderIdsFlat = [];
                foreach ($validated['stops'] as $s) {
                    foreach (($s['orders'] ?? []) as $oid) {
                        $stopOrderIdsFlat[] = (int) $oid;
                    }
                }
                $stopOrderIdsFlat = array_values(array_unique($stopOrderIdsFlat));

                $diff = array_diff($stopOrderIdsFlat, $allSelectedIds);
                if (!empty($diff)) {
                    abort(422, 'Gli stop contengono ordini non selezionati nel viaggio: ' . implode(',', $diff));
                }

                $seen = [];
                foreach ($validated['stops'] as $s) {
                    foreach (($s['orders'] ?? []) as $oid) {
                        $oid = (int) $oid;
                        if (isset($seen[$oid])) {
                            abort(422, "Ordine {$oid} assegnato a piu tappe.");
                        }
                        $seen[$oid] = true;
                    }
                }

                $stopsSorted = $validated['stops'];
                usort($stopsSorted, fn($a, $b) => ($a['sequence'] ?? 0) <=> ($b['sequence'] ?? 0));

                foreach ($stopsSorted as $stopPayload) {
                    $kind = $stopPayload['kind'];
                    if ($kind === 'customer' && empty($stopPayload['customer_id'])) {
                        abort(422, 'Stop customer senza customer_id');
                    }
                    if ($kind === 'technical' && empty($stopPayload['technical_action_id'])) {
                        abort(422, 'Stop technical senza technical_action_id');
                    }

                    $stop = JourneyStop::create([
                        'journey_id' => $journey->id,
                        'kind' => $kind,
                        'customer_id' => $stopPayload['customer_id'] ?? null,
                        'customer_visit_index' => $stopPayload['customer_visit_index'] ?? null,
                        'technical_action_id' => $stopPayload['technical_action_id'] ?? null,
                        'description' => $stopPayload['description'] ?? null,
                        'location_lat' => $stopPayload['location_lat'] ?? null,
                        'location_lng' => $stopPayload['location_lng'] ?? null,
                        'sequence' => $stopPayload['sequence'],
                        'planned_sequence' => $stopPayload['planned_sequence'] ?? $stopPayload['sequence'],
                        'status' => $stopPayload['status'] ?? 'planned',
                        'address_text' => $stopPayload['address_text'] ?? null,
                        'notes' => $stopPayload['notes'] ?? null,
                    ]);

                    foreach (($stopPayload['orders'] ?? []) as $orderId) {
                        $orderId = (int) $orderId;

                        if ($kind === 'customer') {
                            $o = $ordersById->get($orderId);
                            if (!$o) {
                                abort(422, "Ordine {$orderId} non trovato tra quelli selezionati.");
                            }
                            if ((int) $o->customer_id !== (int) $stop->customer_id) {
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

            return redirect()->route('journey.index')->with('success', 'Viaggio modificato con successo!');
        });
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
