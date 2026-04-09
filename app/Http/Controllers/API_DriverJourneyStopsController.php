<?php

namespace App\Http\Controllers;

use App\Enums\JourneyStatus;
use App\Enums\JourneyStopStatus;
use App\Enums\OrderDocumentsStatus;
use App\Enums\OrderStatus;
use App\Models\Journey;
use App\Models\JourneyEvent;
use App\Models\JourneyStop;
use App\Models\JourneyStopOrder;
use App\Services\Dispatch\JourneyWorkspaceInitializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class API_DriverJourneyStopsController extends Controller
{
    public function __construct(
        private JourneyWorkspaceInitializer $journeyWorkspaceInitializer
    ) {
    }

    private const SKIP_REASON_CODES = [
        'traffic',
        'over_capacity',
        'customer_closed',
        'customer_refused',
        'vehicle_issue',
        'other',
    ];
    private function authorizeDriver(Request $request, Journey $journey): void
    {
        if (!$request->user() || (int) $request->user()->id !== (int) $journey->driver_id) {
            abort(403, 'Non sei autorizzato ad aggiornare questo viaggio.');
        }
    }

    private function loadJourneyStops(Journey $journey): Journey
    {
        return $journey->load([
            'driver',
            'vehicle',
            'trailer',
            'stops' => fn ($q) => $q->orderBy('sequence'),
            'stops.customer',
            'stops.technicalAction',
            'stops.stopOrders.order.site',
            'stops.stopOrders.order.site.timetable',
            'stops.stopOrders.order.customer',
            'stops.stopOrders.order.items',
            'stops.stopOrders.order.items.cerCode',
        ]);
    }

    private function currentStop(Journey $journey): ?JourneyStop
    {
        return $journey->stops()
            ->where('status', JourneyStopStatus::InProgress->value)
            ->orderBy('sequence')
            ->first();
    }

    private function nextPlannedStop(Journey $journey, int $afterSequence): ?JourneyStop
    {
        return $journey->stops()
            ->where('status', JourneyStopStatus::Planned->value)
            ->where('sequence', '>', $afterSequence)
            ->orderBy('sequence')
            ->first();
    }

    private function logEvent(Request $request, Journey $journey, ?JourneyStop $stop, array $payload = [], ?string $status = null): void
    {
        JourneyEvent::create([
            'journey_id' => $journey->id,
            'journey_stop_id' => $stop?->id,
            'status' => $status,
            'payload' => $payload,
            'created_by_user_id' => $request->user()?->id,
        ]);
    }

    public function startJourney(Request $request, Journey $journey)
    {
        $this->authorizeDriver($request, $journey);

        return DB::transaction(function () use ($request, $journey) {
            $journey = Journey::query()
                ->whereKey($journey->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentState = JourneyStatus::fromMixed($journey->status);

            if ($currentState === JourneyStatus::STATUS_CREATED) {
                $hasOtherActiveJourney = Journey::query()
                    ->where('driver_id', $journey->driver_id)
                    ->where('status', JourneyStatus::STATUS_ACTIVE->value)
                    ->where('id', '!=', $journey->id)
                    ->exists();

                if ($hasOtherActiveJourney) {
                    abort(422, 'Hai gia un altro viaggio attivo.');
                }

                $notReadyOrders = $journey->orders()
                    ->where(function ($query) {
                        $query->where('status', '!=', OrderStatus::STATUS_READY->value)
                            ->orWhere('documents_status', '!=', OrderDocumentsStatus::GENERATED->value);
                    })
                    ->count();

                if ($notReadyOrders > 0) {
                    abort(422, 'Impossibile avviare il viaggio: tutti gli ordini devono essere READY con documenti generati.');
                }
            }

            if ($currentState === JourneyStatus::STATUS_CREATED) {
                $journey->status = JourneyStatus::STATUS_ACTIVE->value;
                $journey->actual_start_at = now();
                $journey->save();

                $this->journeyWorkspaceInitializer->initializeForJourney($journey);
            }

            $current = $this->currentStop($journey);
            if (!$current) {
                $first = $journey->stops()
                    ->where('status', JourneyStopStatus::Planned->value)
                    ->orderBy('sequence')
                    ->first();

                if ($first) {
                    $first->status = JourneyStopStatus::InProgress->value;
                    $first->started_at = $first->started_at ?? now();
                    $first->save();
                    $current = $first;
                }
            }

            $this->logEvent($request, $journey, $current, [
                'event' => 'journey_started',
            ], $current ? $this->stopStatusValue($current->status) : null);

            $this->loadJourneyStops($journey);

            return response()->json([
                'type' => 'success',
                'journey' => $journey,
            ], 200);
        });
    }

    public function reorder(Request $request, Journey $journey)
    {
        $this->authorizeDriver($request, $journey);

        $validated = $request->validate([
            'stop_ids' => ['required', 'array', 'min:1'],
            'stop_ids.*' => ['integer'],
        ]);

        return DB::transaction(function () use ($request, $journey, $validated) {
            $stops = $journey->stops()->orderBy('sequence')->get();

            $reorderable = $stops->filter(fn ($s) => in_array($this->stopStatusValue($s->status), [
                JourneyStopStatus::Planned->value,
                JourneyStopStatus::InProgress->value,
            ], true));

            $locked = $stops->filter(fn ($s) => in_array($this->stopStatusValue($s->status), [
                JourneyStopStatus::Done->value,
                JourneyStopStatus::Skipped->value,
                JourneyStopStatus::Cancelled->value,
            ], true));

            $incoming = array_values($validated['stop_ids']);
            $expected = $reorderable->pluck('id')->values()->all();

            sort($incoming);
            $expectedSorted = $expected;
            sort($expectedSorted);

            if ($incoming !== $expectedSorted) {
                abort(422, 'La lista delle fermate non Ã¨ valida.');
            }

            $slots = $stops->map(function ($stop) use ($locked) {
                return $locked->contains('id', $stop->id) ? $stop->id : null;
            })->values()->all();

            $incomingQueue = $validated['stop_ids'];
            foreach ($slots as $idx => $slot) {
                if ($slot === null) {
                    $slots[$idx] = array_shift($incomingQueue);
                }
            }

            if (!empty($incomingQueue)) {
                abort(422, 'La lista delle fermate non Ã¨ valida.');
            }

            $byId = $stops->keyBy('id');
            foreach ($slots as $index => $stopId) {
                $stop = $byId->get($stopId);
                if (!$stop) continue;
                $stop->sequence = $index + 1;
                $stop->save();
            }

            // Se la tappa in corso non è più la prima utile, riallinea stati:
            // la prima (planned|in_progress) diventa in corso, le altre planned.
            $orderedActive = $journey->stops()
                ->whereIn('status', [
                    JourneyStopStatus::Planned->value,
                    JourneyStopStatus::InProgress->value,
                ])
                ->orderBy('sequence')
                ->get();

            $firstActive = $orderedActive->first();
            foreach ($orderedActive as $idx => $activeStop) {
                if ($idx === 0) {
                    $activeStop->status = JourneyStopStatus::InProgress->value;
                    $activeStop->started_at = $activeStop->started_at ?? now();
                } else {
                    $activeStop->status = JourneyStopStatus::Planned->value;
                    $activeStop->started_at = null;
                }
                $activeStop->save();
            }

            $this->logEvent($request, $journey, null, [
                'event' => 'stops_reordered',
                'stop_ids' => $validated['stop_ids'],
                'current_stop_id' => $firstActive?->id,
            ]);

            $this->loadJourneyStops($journey);

            return response()->json([
                'type' => 'success',
                'journey' => $journey,
            ], 200);
        });
    }

    public function complete(Request $request, Journey $journey, JourneyStop $stop)
    {
        $this->authorizeDriver($request, $journey);

        if ((int) $stop->journey_id !== (int) $journey->id) {
            abort(404);
        }

        if (JourneyStopStatus::fromMixed($stop->status) !== JourneyStopStatus::InProgress) {
            abort(422, 'Solo la tappa corrente puÃ² essere completata.');
        }

        return DB::transaction(function () use ($request, $journey, $stop) {
            $stop->status = JourneyStopStatus::Done->value;
            $stop->completed_at = $stop->completed_at ?? now();
            $stop->save();

            $this->logEvent($request, $journey, $stop, [
                'event' => 'stop_completed',
            ], $this->stopStatusValue($stop->status));

            $next = $this->nextPlannedStop($journey, $stop->sequence);
            if ($next) {
                $next->status = JourneyStopStatus::InProgress->value;
                $next->started_at = $next->started_at ?? now();
                $next->save();
            }

            $this->loadJourneyStops($journey);

            return response()->json([
                'type' => 'success',
                'journey' => $journey,
            ], 200);
        });
    }

    public function skip(Request $request, Journey $journey, JourneyStop $stop)
    {
        $this->authorizeDriver($request, $journey);

        if ((int) $stop->journey_id !== (int) $journey->id) {
            abort(404);
        }

        if (JourneyStopStatus::fromMixed($stop->status) !== JourneyStopStatus::InProgress) {
            abort(422, 'Solo la tappa corrente puÃ² essere saltata.');
        }

        $validated = $request->validate([
            'reason_code' => ['required', 'string', 'max:64', Rule::in(self::SKIP_REASON_CODES)],
            'driver_notes' => ['nullable', 'string', 'max:2000', 'required_if:reason_code,other'],
            'confirm_release_orders' => ['required', 'accepted'],
        ]);

        return DB::transaction(function () use ($request, $journey, $stop, $validated) {
            $releasedOrderIds = JourneyStopOrder::query()
                ->where('journey_id', $journey->id)
                ->where('journey_stop_id', $stop->id)
                ->pluck('order_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($releasedOrderIds)) {
                DB::table('orders')
                    ->whereIn('id', $releasedOrderIds)
                    ->update([
                        'journey_id' => null,
                        'cargo_location' => null,
                        'status' => OrderStatus::STATUS_CREATED->value,
                        'documents_status' => OrderDocumentsStatus::NOT_GENERATED->value,
                        'documents_generated_at' => null,
                        'documents_error' => null,
                        'updated_at' => now(),
                    ]);

                JourneyStopOrder::query()
                    ->where('journey_id', $journey->id)
                    ->where('journey_stop_id', $stop->id)
                    ->delete();
            }

            $stop->status = JourneyStopStatus::Skipped->value;
            $stop->completed_at = $stop->completed_at ?? now();
            $stop->reason_code = $validated['reason_code'];
            $stop->driver_notes = $validated['driver_notes'];
            $stop->save();

            $this->logEvent($request, $journey, $stop, [
                'event' => 'stop_skipped',
                'reason_code' => $validated['reason_code'],
                'driver_notes' => $validated['driver_notes'],
                'released_order_ids' => $releasedOrderIds,
                'released_orders_count' => count($releasedOrderIds),
            ], $this->stopStatusValue($stop->status));

            $next = $this->nextPlannedStop($journey, $stop->sequence);
            if ($next) {
                $next->status = JourneyStopStatus::InProgress->value;
                $next->started_at = $next->started_at ?? now();
                $next->save();
            }

            $this->loadJourneyStops($journey);

            return response()->json([
                'type' => 'success',
                'journey' => $journey,
            ], 200);
        });
    }

    public function createTechnical(Request $request, Journey $journey)
    {
        $this->authorizeDriver($request, $journey);

        $validated = $request->validate([
            'technical_action_id' => ['required', 'integer', 'exists:journey_stop_actions,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'driver_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($request, $journey, $validated) {
            $current = $this->currentStop($journey);
            $insertAfter = $current?->sequence ?? $journey->stops()->max('sequence') ?? 0;

            $journey->stops()
                ->where('sequence', '>', $insertAfter)
                ->increment('sequence');

            $stop = JourneyStop::create([
                'journey_id' => $journey->id,
                'kind' => 'technical',
                'technical_action_id' => $validated['technical_action_id'],
                'description' => $validated['description'] ?? null,
                'address_text' => $validated['address_text'] ?? null,
                'driver_notes' => $validated['driver_notes'] ?? null,
                'sequence' => $insertAfter + 1,
                'planned_sequence' => $insertAfter + 1,
                'status' => JourneyStopStatus::Planned->value,
            ]);

            $this->logEvent($request, $journey, $stop, [
                'event' => 'technical_stop_created',
                'technical_action_id' => $validated['technical_action_id'],
            ], $this->stopStatusValue($stop->status));

            $this->loadJourneyStops($journey);

            return response()->json([
                'type' => 'success',
                'journey' => $journey,
            ], 201);
        });
    }

    private function stopStatusValue(mixed $status): string
    {
        return JourneyStopStatus::fromMixed($status)->value;
    }
}
