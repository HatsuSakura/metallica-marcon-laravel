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
use App\Enums\OrderDocumentsStatus;
use App\Enums\OrderStatus;
use App\Enums\JourneyStatus;
use Illuminate\Http\Request;
use App\Enums\OrdersTruckLocation;
use App\Models\JourneyStop;
use App\Models\JourneyStopOrder;
use App\Services\OrderDocumentGenerationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

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
            ->where('status', JourneyStatus::STATUS_CREATED->value)
            ->orderByDesc('planned_start_at')
            ->get();

        $activeJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer')
            ->where('status', JourneyStatus::STATUS_ACTIVE->value)
            ->orderByDesc('planned_start_at')
            ->get();

        $allJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer');
        $this->applyJourneyIndexFilters($allJourneys, $request);
        $allJourneys = $allJourneys
            ->orderByDesc('planned_start_at')
            ->paginate(25, ['*'], 'all_page')
            ->appends($request->query());

        $executedJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer')
            ->where('status', JourneyStatus::STATUS_EXECUTED->value);
        $this->applyJourneyIndexFilters($executedJourneys, $request);
        $executedJourneys = $executedJourneys
            ->orderByDesc('planned_start_at')
            ->paginate(25, ['*'], 'executed_page')
            ->appends($request->query());

        $closedJourneys = Journey::query()
            ->with('driver')
            ->with('vehicle')
            ->with('trailer')
            ->where('status', JourneyStatus::STATUS_CLOSED->value);
        $this->applyJourneyIndexFilters($closedJourneys, $request);
        $closedJourneys = $closedJourneys
            ->orderByDesc('planned_start_at')
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
                        JourneyStatus::STATUS_CREATED->value,
                        JourneyStatus::STATUS_ACTIVE->value,
                        JourneyStatus::STATUS_EXECUTED->value,
                        JourneyStatus::STATUS_CLOSED->value,
                    ],
                    true
                ) ? $activeTab : 'tutti',
            ]);
    }

    private function applyJourneyIndexFilters(Builder $query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('planned_start_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('planned_start_at', '<=', $request->query('date_to'));
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', (int) $request->query('driver_id'));
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->query('vehicle_id'));
        }
    }

    public function show(Journey $journey)
    {
        Gate::authorize('view', $journey);

        $returnTo = request()->query('return_to');
        if (!is_string($returnTo) || trim($returnTo) === '') {
            $returnTo = url()->previous();
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $isSafeReturnTo = Str::startsWith($returnTo, '/')
            || ($appUrl !== '' && Str::startsWith($returnTo, $appUrl));

        if (!$isSafeReturnTo) {
            $returnTo = route('journey.index');
        }

        $journey = $journey->load(
            'vehicle',
            'trailer',
            'driver',
            'orders.customer',
            'orders.site',
            'orders.items',
            'orders.items.cerCode',
            'orders.holders',
            'stops.customer',
            'stops.technicalAction',
            'stops.stopOrders.order.site',
            'stops.stopOrders.order.customer',
            'stops.stopOrders.order.items',
            'stops.stopOrders.order.items.cerCode'
        )->loadCount('stops');

        $documentsService = app(OrderDocumentGenerationService::class);
        $documentsByOrderId = [];

        foreach ($journey->orders as $order) {
            $documentsByOrderId[$order->id] = $documentsService->listDocuments($order);
            $order->setAttribute('documents', $documentsByOrderId[$order->id]);
        }

        foreach ($journey->stops as $stop) {
            foreach ($stop->stopOrders as $stopOrder) {
                $order = $stopOrder->order;
                if (!$order) {
                    continue;
                }

                $documents = $documentsByOrderId[$order->id] ?? $documentsService->listDocuments($order);
                $documentsByOrderId[$order->id] = $documents;
                $order->setAttribute('documents', $documents);
            }
        }

        return inertia(
            'Journey/Show',
            [
                'journey' => $journey,
                'returnTo' => $returnTo,
            ]
        );
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
            ->whereNull('journey_id')
            ->whereIn('status', [
                OrderStatus::STATUS_CREATED->value,
                OrderStatus::STATUS_READY->value,
            ])
            ->with([
                'logistic:id,name',
                'customer:id,company_name',
                'site',
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
public function store(Request $request)
{
    Gate::authorize('create', Journey::class);

    $validated = $request->validate([
        'planned_start_at'    => 'required|date',
        'planned_end_at'      => 'required|date',
        'notes'               => 'nullable|string',
        'vehicle_id'  => 'required',
        'trailer_id'  => 'nullable',
        'vehicle_cargo_id'  => 'required',
        'trailer_cargo_id'  => 'nullable',
        'driver_id'   => 'required',
        'logistics_user_id' => 'nullable',

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
            'planned_start_at' => $validated['planned_start_at'],
            'planned_end_at' => $validated['planned_end_at'],
            'notes' => $validated['notes'] ?? null,
            'vehicle_id' => $validated['vehicle_id'],
            'trailer_id' => $validated['trailer_id'] ?? null,
            'vehicle_cargo_id' => $validated['vehicle_cargo_id'],
            'trailer_cargo_id' => $validated['trailer_cargo_id'] ?? null,
            'driver_id' => $validated['driver_id'],
            'logistics_user_id' => $validated['logistics_user_id'] ?? null,
            'status' => JourneyStatus::STATUS_CREATED->value,
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
                ->route('journey.edit', ['journey' => $journey->id])
                ->with('success', 'Viaggio inserito con successo! Genera ora i documenti ordini dal pannello Journey.');
    });
}

/**
 * DRY helper: applica journey + cargo_location + status agli ordini selezionati.
 */
private function applyOrdersToJourney(
    Journey $journey,
    array $orderIds,
    string $truckLocation
): void
{
    if (empty($orderIds)) return;

    $orders = Order::whereIn('id', $orderIds)->get();

    foreach ($orders as $order) {
        $alreadyInJourney = (int) $order->journey_id === (int) $journey->id;
        $order->cargo_location = $truckLocation;
        $order->journey_id = $journey->id;

        if (!$alreadyInJourney) {
            $this->invalidateOrderReadyState($order);
        }

        $order->save();
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
                $q->where(function ($available) {
                    $available->whereNull('journey_id')
                        ->whereIn('status', [
                            OrderStatus::STATUS_CREATED->value,
                            OrderStatus::STATUS_READY->value,
                        ]);
                })
                    ->orWhere('journey_id', $journey->id);
            })
            ->with([
                'logistic:id,name',
                'customer:id,company_name',
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
            'planned_start_at'    => 'required|date',
            'planned_end_at'      => 'required|date',
            'notes'               => 'nullable|string',
            'vehicle_id'  => 'required',
            'trailer_id'  => 'nullable',
            'vehicle_cargo_id'  => 'required',
            'trailer_cargo_id'  => 'nullable',
            'driver_id'   => 'required',
            'logistics_user_id' => 'nullable',

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

            $journeyStatusRaw = $journey->getRawOriginal('status');
            if ($journeyStatusRaw === null || $journeyStatusRaw === '') {
                $journeyStatusRaw = JourneyStatus::STATUS_CREATED->value;
            }

            try {
                $journeyState = $journey->status instanceof JourneyStatus
                    ? $journey->status
                    : JourneyStatus::fromMixed($journeyStatusRaw);
            } catch (\ValueError $e) {
                $journeyState = JourneyStatus::STATUS_CREATED;
            }

            if ($journeyState !== JourneyStatus::STATUS_CREATED) {
                abort(422, 'Il viaggio puo essere modificato solo quando e in stato creato.');
            }

            $before = [
                'planned_start_at' => optional($journey->planned_start_at)->toDateTimeString(),
                'planned_end_at' => optional($journey->planned_end_at)->toDateTimeString(),
                'vehicle_id' => (int) $journey->vehicle_id,
                'trailer_id' => $journey->trailer_id !== null ? (int) $journey->trailer_id : null,
                'vehicle_cargo_id' => (int) $journey->vehicle_cargo_id,
                'trailer_cargo_id' => $journey->trailer_cargo_id !== null ? (int) $journey->trailer_cargo_id : null,
                'driver_id' => (int) $journey->driver_id,
            ];

            $journey->update([
                'planned_start_at' => $validated['planned_start_at'],
                'planned_end_at' => $validated['planned_end_at'],
                'notes' => $validated['notes'] ?? null,
                'vehicle_id' => $validated['vehicle_id'],
                'trailer_id' => $validated['trailer_id'] ?? null,
                'vehicle_cargo_id' => $validated['vehicle_cargo_id'],
                'trailer_cargo_id' => $validated['trailer_cargo_id'] ?? null,
                'driver_id' => $validated['driver_id'],
                'logistics_user_id' => $validated['logistics_user_id'] ?? null,
            ]);

            $after = [
                'planned_start_at' => optional($journey->planned_start_at)->toDateTimeString(),
                'planned_end_at' => optional($journey->planned_end_at)->toDateTimeString(),
                'vehicle_id' => (int) $journey->vehicle_id,
                'trailer_id' => $journey->trailer_id !== null ? (int) $journey->trailer_id : null,
                'vehicle_cargo_id' => (int) $journey->vehicle_cargo_id,
                'trailer_cargo_id' => $journey->trailer_cargo_id !== null ? (int) $journey->trailer_cargo_id : null,
                'driver_id' => (int) $journey->driver_id,
            ];
            $journeyExecutionContextChanged = $before !== $after;

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
                $ordersToDetach = Order::query()->whereIn('id', $toDetach)->get();
                foreach ($ordersToDetach as $orderToDetach) {
                    $orderToDetach->update([
                        'journey_id' => null,
                        'cargo_location' => null,
                        'status' => $this->statusAfterJourneyDetach($orderToDetach),
                        'documents_status' => OrderDocumentsStatus::NOT_GENERATED->value,
                        'documents_generated_at' => null,
                        'documents_error' => null,
                    ]);
                }
            }

            $this->applyOrdersToJourney($journey, $idsTruck, OrdersTruckLocation::TRUCK_MOTRICE->value);
            $this->applyOrdersToJourney($journey, $idsTrailer, OrdersTruckLocation::TRUCK_RIMORCHIO->value);
            $this->applyOrdersToJourney($journey, $idsFulfill, OrdersTruckLocation::TRUCK_RIEMPIMENTO->value);

            if ($journeyExecutionContextChanged) {
                $ordersToInvalidate = Order::query()
                    ->where('journey_id', $journey->id)
                    ->get();

                foreach ($ordersToInvalidate as $order) {
                    $this->invalidateOrderReadyState($order);
                    $order->save();
                }
            }

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
                'status' => $this->statusAfterJourneyDetach($order),
                'cargo_location' => null,
                'documents_status' => OrderDocumentsStatus::NOT_GENERATED->value,
                'documents_generated_at' => null,
                'documents_error' => null,
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
    $newState = JourneyStatus::from($request->new_state);

    if (!JourneyStatus::fromMixed($journey->status)->canTransitionTo($newState)) {
        abort(403, 'Invalid state transition.');
    }

    // Add lifecycle-specific logic
    switch ($newState) {
        case JourneyStatus::STATUS_CREATED:
            $journey->planned_date = $request->planned_date;
            break;

        case JourneyStatus::STATUS_ACTIVE:
            $notReadyOrders = $journey->orders()
                ->where(function ($query) {
                    $query->where('status', '!=', OrderStatus::STATUS_READY->value)
                        ->orWhere('documents_status', '!=', OrderDocumentsStatus::GENERATED->value);
                })
                ->count();

            if ($notReadyOrders > 0) {
                abort(422, 'Impossibile avviare il viaggio: tutti gli ordini devono essere READY con documenti generati.');
            }

            $journey->executed_at = now();
            break;

        case JourneyStatus::STATUS_EXECUTED:
            // Attachments or warehouse updates
            $journey->downloaded_files = $request->file('attachments')->store('journeys');
            break;
    }

    $journey->status = $newState->value;
    $journey->save();

    return redirect()->back()->with('success', "Journey state updated to {$newState->value}.");
}

private function statusAfterJourneyDetach(Order $order): string
{
    return OrderStatus::STATUS_CREATED->value;
}

private function invalidateOrderReadyState(Order $order): void
{
    $order->status = OrderStatus::STATUS_CREATED->value;
    $order->documents_status = OrderDocumentsStatus::NOT_GENERATED->value;
    $order->documents_generated_at = null;
    $order->documents_error = null;
}

public function documentsStatus(Journey $journey): \Illuminate\Http\JsonResponse
{
    Gate::authorize('view', $journey);

    $documentsService = app(OrderDocumentGenerationService::class);

    $orders = Order::query()
        ->where('journey_id', $journey->id)
        ->with([
            'customer:id,company_name',
            'items:id,order_id,cer_code_id,has_adr,adr,adr_hp',
            'items.cerCode:id,is_dangerous',
        ])
        ->orderBy('id')
        ->get([
            'id',
            'journey_id',
            'legacy_code',
            'customer_id',
            'cargo_location',
            'status',
            'documents_status',
            'documents_generated_at',
            'documents_error',
            'documents_version',
        ]);

    $total = $orders->count();
    $ready = $orders->filter(function (Order $order) {
        return OrderStatus::fromMixed($order->status)->value === OrderStatus::STATUS_READY->value;
    })->count();

    return response()->json([
        'journey_id' => $journey->id,
        'summary' => [
            'total' => $total,
            'ready' => $ready,
            'not_ready' => max(0, $total - $ready),
            'all_ready' => $total > 0 && $ready === $total,
        ],
        'orders' => $orders->map(function (Order $order) use ($documentsService) {
            $documents = $documentsService->listDocuments($order);
            $modelDocument = collect($documents)->first(fn (array $document) => ($document['extension'] ?? null) === 'xlsx');
            $adrHpDocument = collect($documents)->first(fn (array $document) => ($document['extension'] ?? null) === 'pdf');
            $requiresAdrHp = $order->items->contains(function ($item): bool {
                $adrEnabled = (bool) ($item->has_adr ?? $item->adr ?? false);
                if ($adrEnabled) {
                    return true;
                }

                $dangerousCer = (bool) ($item->cerCode?->is_dangerous ?? false);
                $hasHpCode = trim((string) ($item->adr_hp ?? '')) !== '';

                return $dangerousCer || $hasHpCode;
            });

            return [
            'id' => $order->id,
            'legacy_code' => $order->legacy_code,
            'customer_name' => $order->customer?->company_name,
            'status' => OrderStatus::fromMixed($order->status)->value,
            'documents_status' => OrderDocumentsStatus::fromMixed($order->documents_status)->value,
            'documents_generated_at' => $order->documents_generated_at,
            'documents_error' => $order->documents_error,
            'documents_version' => (int) ($order->documents_version ?? 0),
            'has_model_document' => $modelDocument !== null,
            'model_document_name' => $modelDocument['name'] ?? null,
            'has_adr_hp_document' => $adrHpDocument !== null,
            'adr_hp_document_name' => $adrHpDocument['name'] ?? null,
            'requires_adr_hp_document' => $requiresAdrHp,
        ];
        })->values(),
    ]);
}

public function generateDocuments(
    Request $request,
    Journey $journey,
    OrderDocumentGenerationService $service
): \Illuminate\Http\JsonResponse {
    Gate::authorize('update', $journey);

    $validated = $request->validate([
        'order_ids' => ['nullable', 'array'],
        'order_ids.*' => ['integer', 'exists:orders,id'],
    ]);

    $orderIds = array_values(array_unique(array_map('intval', $validated['order_ids'] ?? [])));
    $query = Order::query()->where('journey_id', $journey->id);
    if (!empty($orderIds)) {
        $query->whereIn('id', $orderIds);
    }
    $orders = $query->get();

    if ($orders->isEmpty()) {
        return response()->json([
            'type' => 'warning',
            'message' => 'Nessun ordine trovato nel journey selezionato.',
        ], 422);
    }

    $queued = [];
    $skipped = [];

    foreach ($orders as $order) {
        $documentsState = OrderDocumentsStatus::fromMixed($order->documents_status ?? OrderDocumentsStatus::NOT_GENERATED->value);

        if ($documentsState === OrderDocumentsStatus::GENERATING && !$service->isGeneratingStateStale($order)) {
            $skipped[] = [
                'order_id' => $order->id,
                'reason' => 'already_generating',
            ];
            continue;
        }

        if ($documentsState === OrderDocumentsStatus::GENERATING && $service->isGeneratingStateStale($order)) {
            $service->recoverStaleGeneratingState($order);
            $order->refresh();
        }

        $service->enqueueGeneration($order);
        $queued[] = $order->id;
    }

    return response()->json([
        'type' => 'success',
        'message' => sprintf(
            'Generazione avviata per %d ordini%s.',
            count($queued),
            count($skipped) > 0 ? ' (alcuni ordini erano gia in generazione)' : ''
        ),
        'queued_order_ids' => $queued,
        'skipped' => $skipped,
    ], 202);
}



}





