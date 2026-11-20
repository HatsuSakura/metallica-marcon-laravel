<?php

namespace App\Services\Planning;

use App\Enums\JourneyStatus;
use App\Enums\OrderDocumentsStatus;
use App\Enums\OrderStatus;
use App\Enums\OrdersTruckLocation;
use App\Models\Journey;
use App\Models\JourneyStop;
use App\Models\JourneyStopOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\JourneyExecution\JourneyExecutionService;
use App\Services\JourneyPlanning\PlanningInput;
use App\Services\JourneyPlanning\VehicleConfiguration;
use App\Services\OrderDocumentGenerationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JourneyWriter
{
    public function create(array $input): Journey
    {
        $validated = Validator::make($input, [
            'planned_start_at' => 'required|date',
            'planned_end_at' => 'required|date|after_or_equal:planned_start_at',
            'notes' => 'nullable|string',
            'vehicle_id' => 'required|integer',
            'trailer_id' => 'nullable|integer',
            'vehicle_cargo_id' => 'required|integer',
            'trailer_cargo_id' => 'nullable|integer',
            'driver_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'driver')],
            'logistics_user_id' => 'nullable|integer|exists:users,id',

            'orders_truck' => 'nullable|array',
            'orders_truck.*' => 'integer|exists:orders,id',

            'orders_trailer' => 'nullable|array',
            'orders_trailer.*' => 'integer|exists:orders,id',

            'orders_fulfill' => 'nullable|array',
            'orders_fulfill.*' => 'integer|exists:orders,id',

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
        ])->validate();

        app(VehicleConfiguration::class)->validate($validated);

        return DB::transaction(function () use ($validated) {

            $this->lockSelectedOrders($validated);
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
            if (! empty($validated['stops'])) {

                // Cache ordini selezionati (serve per validare customer_id)
                $ordersById = Order::query()
                    ->whereIn('id', $allSelectedIds)
                    ->get(['id', 'customer_id'])
                    ->keyBy('id');

                // Valida che gli ordini dentro stops siano un subset degli ordini selezionati
                $stopOrderIdsFlat = [];
                foreach ($validated['stops'] as $s) {
                    foreach (($s['orders'] ?? []) as $oid) {
                        $stopOrderIdsFlat[] = (int) $oid;
                    }
                }
                $stopOrderIdsFlat = array_values(array_unique($stopOrderIdsFlat));

                // (a) niente ordini “estranei”
                $diff = array_diff($stopOrderIdsFlat, $allSelectedIds);
                if (! empty($diff)) {
                    abort(422, 'Gli stop contengono ordini non selezionati nel viaggio: '.implode(',', $diff));
                }

                // (b) nessun ordine duplicato in 2 stop diversi
                // (a DB ci pensa con uniq (journey_id, order_id), ma meglio dare errore user-friendly)
                $seen = [];
                foreach ($validated['stops'] as $s) {
                    foreach (($s['orders'] ?? []) as $oid) {
                        $oid = (int) $oid;
                        if (isset($seen[$oid])) {
                            abort(422, "Ordine {$oid} assegnato a più tappe.");
                        }
                        $seen[$oid] = true;
                    }
                }

                // Crea gli stop in ordine di sequence (per stabilità)
                $stopsSorted = $validated['stops'];
                usort($stopsSorted, fn ($a, $b) => ($a['sequence'] ?? 0) <=> ($b['sequence'] ?? 0));

                foreach ($stopsSorted as $stopPayload) {
                    $kind = $stopPayload['kind'];

                    // vincoli minimi coerenti col kind
                    if ($kind === 'customer') {
                        if (empty($stopPayload['customer_id'])) {
                            abort(422, 'Stop customer senza customer_id');
                        }
                    }
                    if ($kind === 'technical') {
                        if (empty($stopPayload['technical_action_id'])) {
                            abort(422, 'Stop technical senza technical_action_id');
                        }
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
                        $orderId = (int) $orderId;

                        // extra check: ordine deve appartenere al customer dello stop (solo per stop customer)
                        if ($kind === 'customer') {
                            $o = $ordersById->get($orderId);
                            if (! $o) {
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

            return $journey;
        });
    }

    private function applyOrdersToJourney(
        Journey $journey,
        array $orderIds,
        string $truckLocation
    ): void {
        if (empty($orderIds)) {
            return;
        }

        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            app(JourneyExecutionService::class)->claim($order, $journey->id);
            $alreadyInJourney = (int) $order->journey_id === (int) $journey->id;
            $order->cargo_location = $truckLocation;
            $order->journey_id = $journey->id;

            if (! $alreadyInJourney) {
                $this->invalidateOrderReadyState($order);
            }

            $order->save();
        }
    }

    public function update(Journey $journey, array $input): Journey
    {
        $validated = Validator::make($input, [
            'post_action' => ['nullable', Rule::in(['save_stay', 'save_exit'])],
            'planned_start_at' => 'required|date',
            'planned_end_at' => 'required|date|after_or_equal:planned_start_at',
            'notes' => 'nullable|string',
            'vehicle_id' => 'required|integer',
            'trailer_id' => 'nullable|integer',
            'vehicle_cargo_id' => 'required|integer',
            'trailer_cargo_id' => 'nullable|integer',
            'driver_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'driver')],
            'logistics_user_id' => 'nullable|integer|exists:users,id',

            'orders_truck' => 'nullable|array',
            'orders_truck.*' => 'integer|exists:orders,id',

            'orders_trailer' => 'nullable|array',
            'orders_trailer.*' => 'integer|exists:orders,id',

            'orders_fulfill' => 'nullable|array',
            'orders_fulfill.*' => 'integer|exists:orders,id',

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
        ])->validate();

        app(VehicleConfiguration::class)->validate($validated);

        return DB::transaction(function () use ($validated, $journey) {
            $journey = Journey::query()
                ->whereKey($journey->id)
                ->lockForUpdate()
                ->firstOrFail();

            app(PlanningGuard::class)->journey($journey);
            $this->lockSelectedOrders($validated, $journey);
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

            $comparison = app(PlanningInput::class);
            if ($comparison->current($journey) === $comparison->normalize($validated)) {
                return $journey;
            }
            $documents = app(OrderDocumentGenerationService::class);
            foreach ($journey->orders()->lockForUpdate()->get() as $order) {
                abort_if($order->documents_status === OrderDocumentsStatus::GENERATING && ! $documents->isGeneratingStateStale($order), 409, 'Attendi il completamento della generazione documenti prima di modificare il viaggio.');
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
            if (! empty($overlapTruckTrailer) || ! empty($overlapTruckFulfill) || ! empty($overlapTrailerFulfill)) {
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
            if (! empty($toDetach)) {
                $ordersToDetach = Order::query()->whereIn('id', $toDetach)->get();
                foreach ($ordersToDetach as $orderToDetach) {
                    DB::table('order_commitments')->where('order_id', $orderToDetach->id)->delete();
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

            if (! empty($validated['stops'])) {
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
                if (! empty($diff)) {
                    abort(422, 'Gli stop contengono ordini non selezionati nel viaggio: '.implode(',', $diff));
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
                usort($stopsSorted, fn ($a, $b) => ($a['sequence'] ?? 0) <=> ($b['sequence'] ?? 0));

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
                            if (! $o) {
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

            $journey->increment('plan_version');
            foreach ($journey->orders()->get() as $order) {
                $documents->invalidateAfterReadyOrderEdit($order);
            }

            return $journey;
        });
    }

    private function invalidateOrderReadyState(Order $order): void
    {
        app(OrderDocumentGenerationService::class)->invalidateAfterReadyOrderEdit($order);
    }

    private function statusAfterJourneyDetach(Order $order): string
    {
        return OrderStatus::STATUS_CREATED->value;
    }

    private function lockSelectedOrders(array $input, ?Journey $journey = null): void
    {
        $ids = array_merge($input['orders_truck'] ?? [], $input['orders_trailer'] ?? [], $input['orders_fulfill'] ?? []);
        abort_unless(count($ids) === count(array_unique($ids)), 422, 'Un ordine non può essere assegnato a più comparti.');
        $orders = Order::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        abort_unless($orders->count() === count($ids), 422, 'Uno degli ordini non è disponibile.');
        foreach ($orders as $order) {
            abort_if($order->journey_id && (int) $order->journey_id !== (int) $journey?->id, 409, 'Ordine già assegnato a un altro viaggio.');
            abort_unless(in_array($order->getRawOriginal('status'), ['creato', 'pronto', 'pianificato'], true), 409, 'Ordine non disponibile per la pianificazione.');
        }
    }
}
