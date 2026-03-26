<?php

namespace App\Http\Controllers;

use App\Enums\DispatchStatus;
use App\Enums\TranshipmentStatus;
use App\Models\Journey;
use App\Models\JourneyCargoAllocation;
use App\Models\JourneyCargoMismatchDecision;
use App\Models\JourneyCargoUnloadInstruction;
use App\Models\JourneyEvent;
use App\Models\JourneyLoadCensusItem;
use App\Models\TransshipmentNeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class API_LogisticDispatchWorkspaceController extends Controller
{
    public function workspace(Journey $journey): JsonResponse
    {
        $this->authorize('dispatchWorkspaceView', $journey);

        $journey->load([
            'driver:id,name,surname',
            'loadCensusItems.orderItem:id,order_id,description,holder_quantity,weight_declared',
            'loadCensusItems.orderItem.order:id,customer_id',
            'loadCensusItems.orderItem.order.customer:id,company_name',
            'journeyCargos:id,journey_id,cargo_location,warehouse_id,download_sequence,is_grounded,operation_mode,status',
            'cargoAllocations.instructions' => fn ($query) => $query->orderByDesc('id'),
            'cargoMismatchDecisions',
            'transshipmentNeeds.orderItem:id,order_id,holder_id,description',
            'transshipmentNeeds.orderItem.holder:id,name',
            'transshipmentNeeds.orderItem.order:id,legacy_code,customer_id',
            'transshipmentNeeds.orderItem.order.customer:id,company_name',
        ]);

        $events = JourneyEvent::query()
            ->where('journey_id', $journey->id)
            ->latest('id')
            ->limit(30)
            ->get(['id', 'payload', 'created_at', 'created_by_user_id'])
            ->map(fn (JourneyEvent $event) => [
                'id' => $event->id,
                'event' => data_get($event->payload, 'event'),
                'payload' => $event->payload,
                'at' => optional($event->created_at)?->toISOString(),
                'created_by_user_id' => $event->created_by_user_id,
            ])
            ->values();

        return response()->json([
            'journey' => $journey->only([
                'id',
                'status',
                'dispatch_status',
                'dispatch_started_at',
                'dispatch_managed_at',
                'dispatch_updated_at',
                'is_double_load',
                'is_temporary_storage',
                'primary_warehouse_id',
                'secondary_warehouse_id',
                'primary_warehouse_download_at',
                'secondary_warehouse_download_at',
            ]) + [
                'driver' => $journey->driver,
            ],
            'census' => [
                'items' => $journey->loadCensusItems,
            ],
            'cargos' => $journey->journeyCargos,
            'allocations' => $journey->cargoAllocations,
            'unload_instructions' => $journey->cargoAllocations->flatMap->instructions->values(),
            'mismatch_decisions' => $journey->cargoMismatchDecisions,
            'transshipment_needs' => $journey->transshipmentNeeds,
            'events' => $events,
        ]);
    }

    public function saveWorkspace(Request $request, Journey $journey): JsonResponse
    {
        $this->authorize('dispatchWorkspaceSave', $journey);
        $this->ensureJourneyIsNotClosed($journey);

        $validated = $request->validate([
            'census' => ['nullable', 'array'],
            'census.items' => ['nullable', 'array'],
            'census.items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'census.items.*.actual_containers' => ['required', 'integer', 'min:0'],
            'census.items.*.total_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'census.items.*.notes' => ['nullable', 'string'],
            'census.items.*.source' => ['nullable', 'in:phone,driver_ui,warehouse_ui'],

            'allocations' => ['nullable', 'array'],
            'allocations.*.journey_cargo_id' => ['required', 'integer', 'exists:journey_cargos,id'],
            'allocations.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'allocations.*.allocated_containers' => ['required', 'integer', 'min:1'],
            'allocations.*.source' => ['nullable', 'in:planned,actual'],
            'allocations.*.is_exception' => ['nullable', 'boolean'],
            'allocations.*.exception_reason' => ['nullable', 'string'],

            'unload_instructions' => ['nullable', 'array'],
            'unload_instructions.*.journey_cargo_id' => ['required', 'integer', 'exists:journey_cargos,id'],
            'unload_instructions.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'unload_instructions.*.target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'unload_instructions.*.unload_sequence' => ['nullable', 'integer', 'in:1,2'],
            'unload_instructions.*.instruction_type' => ['required', 'in:simple,double,drop_only'],
            'unload_instructions.*.planned_target_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'unload_instructions.*.proposed_for_transshipment' => ['nullable', 'boolean'],
            'unload_instructions.*.transshipment_reason' => ['nullable', 'string'],

            'mismatch_decisions' => ['nullable', 'array'],
            'mismatch_decisions.*.journey_cargo_id' => ['required', 'integer', 'exists:journey_cargos,id'],
            'mismatch_decisions.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'mismatch_decisions.*.decision' => ['required', 'in:double_unload,grounding'],
            'mismatch_decisions.*.secondary_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        $userId = (int) $request->user()->id;
        $this->validateWorkspaceBusinessRules($journey, $validated);

        DB::transaction(function () use ($validated, $journey, $userId) {
            $censusItems = data_get($validated, 'census.items', []);
            foreach ($censusItems as $row) {
                JourneyLoadCensusItem::query()->updateOrCreate(
                    [
                        'journey_id' => $journey->id,
                        'order_item_id' => (int) $row['order_item_id'],
                    ],
                    [
                        'actual_containers' => (int) $row['actual_containers'],
                        'total_weight_kg' => $row['total_weight_kg'] ?? null,
                        'notes' => $row['notes'] ?? null,
                        'source' => $row['source'] ?? 'phone',
                        'reported_by_user_id' => $userId,
                    ]
                );
            }

            $allocationByKey = [];
            $submittedActualAllocationKeys = [];
            foreach (data_get($validated, 'allocations', []) as $row) {
                $source = $row['source'] ?? 'actual';
                $allocation = JourneyCargoAllocation::query()->updateOrCreate(
                    [
                        'journey_id' => $journey->id,
                        'journey_cargo_id' => (int) $row['journey_cargo_id'],
                        'order_item_id' => (int) $row['order_item_id'],
                        'source' => $source,
                    ],
                    [
                        'allocated_containers' => (int) $row['allocated_containers'],
                        'estimated_weight_kg' => $this->estimateAllocatedWeightKg(
                            $journey->id,
                            (int) $row['order_item_id'],
                            (int) $row['allocated_containers']
                        ),
                        'is_exception' => (bool) ($row['is_exception'] ?? false),
                        'exception_reason' => $row['exception_reason'] ?? null,
                        'created_by_user_id' => $userId,
                        'updated_by_user_id' => $userId,
                    ]
                );

                $allocationByKey[$allocation->journey_cargo_id . ':' . $allocation->order_item_id] = $allocation;
                if ((string) $source === 'actual') {
                    $submittedActualAllocationKeys[((int) $allocation->journey_cargo_id) . ':' . ((int) $allocation->order_item_id)] = true;
                }
            }

            if (array_key_exists('allocations', $validated)) {
                $touchedOrderItemIds = collect(data_get($validated, 'census.items', []))
                    ->pluck('order_item_id')
                    ->merge(collect(data_get($validated, 'allocations', []))->pluck('order_item_id'))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $staleActualAllocations = JourneyCargoAllocation::query()
                    ->where('journey_id', $journey->id)
                    ->where('source', 'actual')
                    ->when($touchedOrderItemIds->isNotEmpty(), fn ($query) => $query->whereIn('order_item_id', $touchedOrderItemIds))
                    ->get(['id', 'journey_cargo_id', 'order_item_id'])
                    ->filter(function ($allocation) use ($submittedActualAllocationKeys) {
                        $key = ((int) $allocation->journey_cargo_id) . ':' . ((int) $allocation->order_item_id);
                        return !isset($submittedActualAllocationKeys[$key]);
                    });

                if ($staleActualAllocations->isNotEmpty()) {
                    $staleIds = $staleActualAllocations->pluck('id')->all();
                    JourneyCargoUnloadInstruction::query()
                        ->whereIn('journey_cargo_allocation_id', $staleIds)
                        ->delete();
                    JourneyCargoAllocation::query()
                        ->whereIn('id', $staleIds)
                        ->delete();
                }
            }

            foreach (data_get($validated, 'unload_instructions', []) as $row) {
                $key = ((int) $row['journey_cargo_id']) . ':' . ((int) $row['order_item_id']);
                $allocation = $allocationByKey[$key] ?? JourneyCargoAllocation::query()
                    ->where('journey_id', $journey->id)
                    ->where('journey_cargo_id', (int) $row['journey_cargo_id'])
                    ->where('order_item_id', (int) $row['order_item_id'])
                    ->where('source', 'actual')
                    ->first();

                if (!$allocation) {
                    throw ValidationException::withMessages([
                        'unload_instructions' => "Allocation mancante per journey_cargo_id {$row['journey_cargo_id']} e order_item_id {$row['order_item_id']}.",
                    ]);
                }

                JourneyCargoUnloadInstruction::query()->updateOrCreate(
                    [
                        'journey_cargo_allocation_id' => $allocation->id,
                        'target_warehouse_id' => (int) $row['target_warehouse_id'],
                        'unload_sequence' => $row['unload_sequence'] ?? null,
                    ],
                    [
                        'instruction_type' => $row['instruction_type'],
                        'planned_target_warehouse_id' => $row['planned_target_warehouse_id'] ?? null,
                        'proposed_for_transshipment' => (bool) ($row['proposed_for_transshipment'] ?? false),
                        'transshipment_reason' => $row['transshipment_reason'] ?? null,
                        'created_by_user_id' => $userId,
                        'updated_by_user_id' => $userId,
                    ]
                );
            }

            if (array_key_exists('mismatch_decisions', $validated)) {
                $submittedMismatchKeys = [];
                foreach (data_get($validated, 'mismatch_decisions', []) as $row) {
                    $journeyCargoId = (int) $row['journey_cargo_id'];
                    $orderItemId = (int) $row['order_item_id'];
                    $decisionKey = $journeyCargoId . ':' . $orderItemId;
                    $submittedMismatchKeys[$decisionKey] = true;

                    JourneyCargoMismatchDecision::query()->updateOrCreate(
                        [
                            'journey_id' => $journey->id,
                            'journey_cargo_id' => $journeyCargoId,
                            'order_item_id' => $orderItemId,
                        ],
                        [
                            'decision' => (string) $row['decision'],
                            'secondary_warehouse_id' => $row['secondary_warehouse_id'] ?? null,
                            'created_by_user_id' => $userId,
                            'updated_by_user_id' => $userId,
                        ]
                    );
                }

                $toDeleteIds = JourneyCargoMismatchDecision::query()
                    ->where('journey_id', $journey->id)
                    ->get(['id', 'journey_cargo_id', 'order_item_id'])
                    ->filter(function ($decision) use ($submittedMismatchKeys) {
                        $key = ((int) $decision->journey_cargo_id) . ':' . ((int) $decision->order_item_id);
                        return !isset($submittedMismatchKeys[$key]);
                    })
                    ->pluck('id');

                if ($toDeleteIds->isNotEmpty()) {
                    JourneyCargoMismatchDecision::query()
                        ->whereIn('id', $toDeleteIds->all())
                        ->delete();
                }

                $actualContainersByPair = [];
                foreach (data_get($validated, 'allocations', []) as $row) {
                    $source = (string) ($row['source'] ?? 'actual');
                    if ($source !== 'actual') {
                        continue;
                    }
                    $pairKey = ((int) $row['journey_cargo_id']) . ':' . ((int) $row['order_item_id']);
                    $actualContainersByPair[$pairKey] = ($actualContainersByPair[$pairKey] ?? 0) + (int) $row['allocated_containers'];
                }

                $orderItemIds = collect(data_get($validated, 'mismatch_decisions', []))
                    ->pluck('order_item_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                $plannedWarehouseByOrderItem = DB::table('order_items')
                    ->whereIn('id', $orderItemIds)
                    ->pluck('warehouse_id', 'id');
                $cargoWarehouseById = DB::table('journey_cargos')
                    ->where('journey_id', $journey->id)
                    ->pluck('warehouse_id', 'id');

                $expectedAutoTransshipments = [];
                foreach (data_get($validated, 'mismatch_decisions', []) as $row) {
                    if ((string) ($row['decision'] ?? '') !== 'grounding') {
                        continue;
                    }

                    $journeyCargoId = (int) $row['journey_cargo_id'];
                    $orderItemId = (int) $row['order_item_id'];
                    $pairKey = $journeyCargoId . ':' . $orderItemId;

                    $fromWarehouseId = (int) ($cargoWarehouseById[$journeyCargoId] ?? 0);
                    $toWarehouseId = (int) ($plannedWarehouseByOrderItem[$orderItemId] ?? 0);
                    $containers = (int) ($actualContainersByPair[$pairKey] ?? 0);
                    if ($fromWarehouseId <= 0 || $toWarehouseId <= 0 || $fromWarehouseId === $toWarehouseId || $containers <= 0) {
                        continue;
                    }

                    $autoKey = $orderItemId . ':' . $fromWarehouseId . ':' . $toWarehouseId;
                    $expectedAutoTransshipments[$autoKey] = true;

                    TransshipmentNeed::query()->updateOrCreate(
                        [
                            'journey_id' => $journey->id,
                            'order_item_id' => $orderItemId,
                            'from_warehouse_id' => $fromWarehouseId,
                            'to_warehouse_id' => $toWarehouseId,
                            'status' => TranshipmentStatus::PROPOSED->value,
                            'notes' => '[AUTO_MISMATCH]',
                        ],
                        [
                            'quantity_containers' => $containers,
                            'estimated_weight_kg' => null,
                        ]
                    );
                }

                $autoToDeleteIds = TransshipmentNeed::query()
                    ->where('journey_id', $journey->id)
                    ->where('status', TranshipmentStatus::PROPOSED->value)
                    ->where('notes', '[AUTO_MISMATCH]')
                    ->get(['id', 'order_item_id', 'from_warehouse_id', 'to_warehouse_id'])
                    ->filter(function ($need) use ($expectedAutoTransshipments) {
                        $key = ((int) $need->order_item_id) . ':' . ((int) $need->from_warehouse_id) . ':' . ((int) $need->to_warehouse_id);
                        return !isset($expectedAutoTransshipments[$key]);
                    })
                    ->pluck('id');

                if ($autoToDeleteIds->isNotEmpty()) {
                    TransshipmentNeed::query()
                        ->whereIn('id', $autoToDeleteIds->all())
                        ->delete();
                }
            }

            JourneyEvent::query()->create([
                'journey_id' => $journey->id,
                'created_by_user_id' => $userId,
                'status' => null,
                'payload' => [
                    'event' => 'dispatch_workspace_saved',
                ],
            ]);
        });

        return response()->json([
            'message' => 'Workspace salvato',
            'journey_id' => $journey->id,
        ]);
    }

    public function upsertCargos(Request $request, Journey $journey): JsonResponse
    {
        $this->authorize('dispatchWorkspaceSave', $journey);
        $this->ensureJourneyIsNotClosed($journey);

        $validated = $request->validate([
            'cargos' => ['required', 'array', 'min:1', 'max:2'],
            'cargos.*.cargo_location' => ['required', 'in:vehicle,trailer'],
            'cargos.*.enabled' => ['nullable', 'boolean'],
            'cargos.*.warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'cargos.*.download_sequence' => ['nullable', 'integer', 'in:1,2'],
            'cargos.*.is_grounded' => ['nullable', 'boolean'],
            'cargos.*.operation_mode' => ['nullable', 'in:unload,drop_only'],
        ]);

        $journey->loadMissing('journeyCargos');

        DB::transaction(function () use ($validated, $journey, $request) {
            $payloadByLocation = collect($validated['cargos'])
                ->keyBy(fn (array $row) => (string) $row['cargo_location']);

            $vehicleRow = $payloadByLocation->get('vehicle');
            if (!$vehicleRow || !($vehicleRow['enabled'] ?? true)) {
                throw ValidationException::withMessages([
                    'cargos' => 'La motrice deve essere sempre configurata.',
                ]);
            }

            foreach (['vehicle', 'trailer'] as $location) {
                /** @var array|null $row */
                $row = $payloadByLocation->get($location);
                $enabled = (bool) ($row['enabled'] ?? false);

                $existing = $journey->journeyCargos
                    ->first(fn ($cargo) => (string) $cargo->cargo_location === $location);

                if (!$enabled) {
                    if ($existing) {
                        $existing->delete();
                    }
                    continue;
                }

                $cargoId = $location === 'vehicle'
                    ? ((int) ($journey->vehicle_cargo_id ?? $journey->cargo_for_vehicle_id ?? 0))
                    : ((int) ($journey->trailer_cargo_id ?? $journey->cargo_for_trailer_id ?? 0));

                if ($cargoId <= 0) {
                    throw ValidationException::withMessages([
                        'cargos' => "Cargo master non disponibile per {$location}.",
                    ]);
                }

                if (!$existing) {
                    $operationMode = (string) ($row['operation_mode'] ?? ((bool) ($row['is_grounded'] ?? false) ? 'drop_only' : 'unload'));
                    DB::table('journey_cargos')->insert([
                        'journey_id' => $journey->id,
                        'cargo_id' => $cargoId,
                        'cargo_location' => $location,
                        'warehouse_id' => $row['warehouse_id'] ?? null,
                        'download_sequence' => $row['download_sequence'] ?? null,
                        'is_grounded' => $operationMode === 'drop_only',
                        'operation_mode' => $operationMode,
                        'status' => 'creato',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                $operationMode = (string) ($row['operation_mode'] ?? ((bool) ($row['is_grounded'] ?? false) ? 'drop_only' : 'unload'));
                $existing->warehouse_id = $row['warehouse_id'] ?? null;
                $existing->download_sequence = $row['download_sequence'] ?? null;
                $existing->is_grounded = $operationMode === 'drop_only';
                $existing->operation_mode = $operationMode;
                $existing->save();
            }

            $effectiveCargos = DB::table('journey_cargos')
                ->where('journey_id', $journey->id)
                ->get(['warehouse_id', 'download_sequence']);

            $warehouseIds = $effectiveCargos
                ->pluck('warehouse_id')
                ->filter(fn ($id) => !empty($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $primaryWarehouseId = null;
            $secondaryWarehouseId = null;

            if ($warehouseIds->count() === 1) {
                $primaryWarehouseId = (int) $warehouseIds->first();
            } elseif ($warehouseIds->count() > 1) {
                $seq1Warehouse = $effectiveCargos->firstWhere('download_sequence', 1)?->warehouse_id;
                $seq2Warehouse = $effectiveCargos->firstWhere('download_sequence', 2)?->warehouse_id;

                $primaryWarehouseId = $seq1Warehouse ? (int) $seq1Warehouse : (int) $warehouseIds->first();
                $secondaryWarehouseId = $seq2Warehouse ? (int) $seq2Warehouse : (int) $warehouseIds->first(fn ($id) => (int) $id !== (int) $primaryWarehouseId);

                if ((int) $secondaryWarehouseId === (int) $primaryWarehouseId) {
                    $secondaryWarehouseId = null;
                }
            }

            $journey->primary_warehouse_id = $primaryWarehouseId;
            $journey->secondary_warehouse_id = $secondaryWarehouseId;
            $journey->save();

            JourneyEvent::query()->create([
                'journey_id' => $journey->id,
                'created_by_user_id' => (int) $request->user()->id,
                'status' => null,
                'payload' => ['event' => 'dispatch_cargos_updated'],
            ]);
        });

        $cargos = DB::table('journey_cargos')
            ->where('journey_id', $journey->id)
            ->get(['id', 'journey_id', 'cargo_id', 'cargo_location', 'warehouse_id', 'download_sequence', 'is_grounded', 'operation_mode', 'status']);

        return response()->json([
            'message' => 'Convoglio aggiornato',
            'cargos' => $cargos,
        ]);
    }

    public function confirm(Request $request, Journey $journey): JsonResponse
    {
        $this->authorize('dispatchWorkspaceConfirm', $journey);
        $this->ensureJourneyIsNotClosed($journey);
        $this->validateConfirmBusinessRules($journey);

        $journey->dispatch_status = DispatchStatus::IN_PROGRESS->value;
        $journey->dispatch_started_at = $journey->dispatch_started_at ?? now();
        $journey->dispatch_updated_at = now();
        $journey->save();

        JourneyEvent::query()->create([
            'journey_id' => $journey->id,
            'created_by_user_id' => (int) $request->user()->id,
            'status' => null,
            'payload' => [
                'event' => 'dispatch_baseline_confirmed',
                'notes' => $request->input('notes'),
            ],
        ]);

        return response()->json([
            'message' => 'Baseline confermata',
            'journey' => [
                'id' => $journey->id,
                'dispatch_status' => $journey->dispatch_status,
                'baseline_confirmed_at' => optional($journey->dispatch_updated_at)?->toISOString(),
            ],
        ]);
    }

    public function appendEvent(Request $request, Journey $journey): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:120'],
            'payload' => ['nullable', 'array'],
        ]);
        $this->ensureJourneyIsNotClosed($journey);
        $this->authorizeAppendEvent($journey, $validated['event']);
        $this->validateEventPayload($journey, $validated['event'], $validated['payload'] ?? []);

        $event = JourneyEvent::query()->create([
            'journey_id' => $journey->id,
            'created_by_user_id' => (int) $request->user()->id,
            'status' => null,
            'payload' => array_merge(
                ['event' => $validated['event']],
                $validated['payload'] ?? []
            ),
        ]);

        $createdTransshipment = null;
        if ($validated['event'] === 'transshipment_proposed') {
            $payload = $validated['payload'] ?? [];
            $createdTransshipment = TransshipmentNeed::query()->create([
                'journey_id' => $journey->id,
                'order_item_id' => (int) ($payload['order_item_id'] ?? 0),
                'from_warehouse_id' => (int) ($payload['from_warehouse_id'] ?? 0),
                'to_warehouse_id' => (int) ($payload['to_warehouse_id'] ?? 0),
                'quantity_containers' => (int) ($payload['containers'] ?? 0),
                'estimated_weight_kg' => $payload['estimated_weight_kg'] ?? null,
                'status' => TranshipmentStatus::PROPOSED->value,
                'notes' => $payload['reason'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Evento registrato',
            'event_id' => $event->id,
            'transshipment' => $createdTransshipment,
        ], 201);
    }

    public function approveTransshipment(Request $request, TransshipmentNeed $transshipment): JsonResponse
    {
        $this->authorize('dispatchTransshipmentApprove', $transshipment->journey);
        $this->ensureJourneyIsNotClosed($transshipment->journey);
        $currentStatus = $this->resolveTransshipmentStatus($transshipment);
        $targetStatus = TranshipmentStatus::APPROVED;
        $this->ensureTransshipmentStatusTransition($transshipment, $currentStatus, $targetStatus);

        $transshipment->status = $targetStatus->value;
        $transshipment->approved_by_user_id = (int) $request->user()->id;
        $transshipment->notes = $request->input('notes', $transshipment->notes);
        $transshipment->save();

        return response()->json([
            'message' => 'Trasbordo approvato',
            'transshipment' => $transshipment,
        ]);
    }

    public function cancelTransshipment(Request $request, TransshipmentNeed $transshipment): JsonResponse
    {
        $this->authorize('dispatchTransshipmentApprove', $transshipment->journey);
        $this->ensureJourneyIsNotClosed($transshipment->journey);
        $currentStatus = $this->resolveTransshipmentStatus($transshipment);
        $targetStatus = TranshipmentStatus::CANCELLED;
        $this->ensureTransshipmentStatusTransition($transshipment, $currentStatus, $targetStatus);

        $transshipment->status = $targetStatus->value;
        $transshipment->notes = $request->input('notes', $transshipment->notes);
        $transshipment->save();

        return response()->json([
            'message' => 'Trasbordo annullato',
            'transshipment' => $transshipment,
        ]);
    }

    public function close(Request $request, Journey $journey): JsonResponse
    {
        $this->authorize('dispatchWorkspaceClose', $journey);
        $this->ensureJourneyCanBeClosed($journey);

        $journey->dispatch_status = DispatchStatus::MANAGED->value;
        $journey->dispatch_managed_at = now();
        $journey->dispatch_updated_at = now();
        $journey->save();

        JourneyEvent::query()->create([
            'journey_id' => $journey->id,
            'created_by_user_id' => (int) $request->user()->id,
            'status' => null,
            'payload' => [
                'event' => 'journey_closed_audit_frozen',
                'notes' => $request->input('notes'),
            ],
        ]);

        return response()->json([
            'message' => 'Journey chiuso',
            'journey' => [
                'id' => $journey->id,
                'dispatch_status' => $journey->dispatch_status,
                'audit_frozen_at' => optional($journey->dispatch_managed_at)?->toISOString(),
            ],
        ]);
    }

    private function estimateAllocatedWeightKg(int $journeyId, int $orderItemId, int $allocatedContainers): ?float
    {
        $census = JourneyLoadCensusItem::query()
            ->where('journey_id', $journeyId)
            ->where('order_item_id', $orderItemId)
            ->first(['actual_containers', 'total_weight_kg']);

        if (!$census || (int) $census->actual_containers <= 0 || $census->total_weight_kg === null) {
            return null;
        }

        $perContainer = ((float) $census->total_weight_kg) / (int) $census->actual_containers;
        return round($perContainer * $allocatedContainers, 2);
    }

    private function ensureJourneyIsNotClosed(Journey $journey): void
    {
        if ($journey->dispatch_status === DispatchStatus::MANAGED) {
            throw ValidationException::withMessages([
                'journey' => 'Journey già chiuso: non sono permesse ulteriori modifiche.',
            ]);
        }
    }

    private function ensureJourneyCanBeClosed(Journey $journey): void
    {
        if (!in_array($journey->dispatch_status, [DispatchStatus::IN_PROGRESS, DispatchStatus::ON_HOLD], true)) {
            throw ValidationException::withMessages([
                'journey' => 'Journey non chiudibile: confermare e avviare il dispatch prima della chiusura audit.',
            ]);
        }
    }

    private function resolveTransshipmentStatus(TransshipmentNeed $transshipment): TranshipmentStatus
    {
        return TranshipmentStatus::fromMixed($transshipment->status);
    }

    private function ensureTransshipmentStatusTransition(
        TransshipmentNeed $transshipment,
        TranshipmentStatus $currentStatus,
        TranshipmentStatus $targetStatus
    ): void
    {
        if (!$currentStatus->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'transshipment' => "Transizione non valida: '{$currentStatus->value}' -> '{$targetStatus->value}'.",
            ]);
        }
    }

    private function authorizeAppendEvent(Journey $journey, string $eventCode): void
    {
        $warehouseExceptionEvents = [
            'warehouse_exception_logged',
            'deviazione_destinazione',
            'correzione_scarico',
            'eccezione_warehouse',
        ];

        if (in_array($eventCode, $warehouseExceptionEvents, true)) {
            $this->authorize('dispatchWorkspaceAppendWarehouseEvent', $journey);
            return;
        }

        if ($eventCode === 'transshipment_proposed') {
            $this->authorize('dispatchWorkspaceAppendTransshipmentProposal', $journey);
            return;
        }

        $this->authorize('dispatchWorkspaceAppendLogisticEvent', $journey);
    }

    private function validateWorkspaceBusinessRules(Journey $journey, array $validated): void
    {
        $journeyOrderItemIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.journey_id', $journey->id)
            ->pluck('order_items.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedOrderItemIds = array_flip($journeyOrderItemIds);

        $journeyCargoIds = DB::table('journey_cargos')
            ->where('journey_id', $journey->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $allowedJourneyCargoIds = array_flip($journeyCargoIds);
        $seenCensusOrderItems = [];

        foreach (data_get($validated, 'census.items', []) as $idx => $row) {
            $orderItemId = (int) $row['order_item_id'];
            if (!isset($allowedOrderItemIds[(int) $row['order_item_id']])) {
                throw ValidationException::withMessages([
                    "census.items.$idx.order_item_id" => 'Order item non appartenente al journey.',
                ]);
            }

            if (isset($seenCensusOrderItems[$orderItemId])) {
                throw ValidationException::withMessages([
                    "census.items.$idx.order_item_id" => 'Order item duplicato nel census payload.',
                ]);
            }
            $seenCensusOrderItems[$orderItemId] = true;
        }

        $actualContainersByOrderItem = JourneyLoadCensusItem::query()
            ->where('journey_id', $journey->id)
            ->pluck('actual_containers', 'order_item_id')
            ->mapWithKeys(fn ($containers, $orderItemId) => [(int) $orderItemId => (int) $containers])
            ->all();

        foreach (data_get($validated, 'census.items', []) as $row) {
            $actualContainersByOrderItem[(int) $row['order_item_id']] = (int) $row['actual_containers'];
        }

        $allocationSumByOrderItem = [];
        $seenAllocationKey = [];
        foreach (data_get($validated, 'allocations', []) as $idx => $row) {
            $orderItemId = (int) $row['order_item_id'];
            $journeyCargoId = (int) $row['journey_cargo_id'];
            $source = (string) ($row['source'] ?? 'actual');
            $allocationKey = $journeyCargoId . ':' . $orderItemId . ':' . $source;

            if (!isset($allowedOrderItemIds[$orderItemId])) {
                throw ValidationException::withMessages([
                    "allocations.$idx.order_item_id" => 'Order item non appartenente al journey.',
                ]);
            }
            if (!isset($allowedJourneyCargoIds[$journeyCargoId])) {
                throw ValidationException::withMessages([
                    "allocations.$idx.journey_cargo_id" => 'Journey cargo non appartenente al journey.',
                ]);
            }

            if (isset($seenAllocationKey[$allocationKey])) {
                throw ValidationException::withMessages([
                    "allocations.$idx" => 'Allocation duplicata nel payload.',
                ]);
            }
            $seenAllocationKey[$allocationKey] = true;

            $allocationSumByOrderItem[$orderItemId] = ($allocationSumByOrderItem[$orderItemId] ?? 0) + (int) $row['allocated_containers'];
        }

        foreach ($allocationSumByOrderItem as $orderItemId => $sumContainers) {
            $actualContainers = $actualContainersByOrderItem[$orderItemId] ?? null;
            if ($actualContainers === null) {
                throw ValidationException::withMessages([
                    'allocations' => "Manca census per order_item_id {$orderItemId}.",
                ]);
            }
            if ($sumContainers > $actualContainers) {
                throw ValidationException::withMessages([
                    'allocations' => "Split non valido su order_item_id {$orderItemId}: {$sumContainers} > {$actualContainers}.",
                ]);
            }
        }

        $seenInstructionKey = [];
        foreach (data_get($validated, 'unload_instructions', []) as $idx => $row) {
            $orderItemId = (int) $row['order_item_id'];
            $journeyCargoId = (int) $row['journey_cargo_id'];
            $instructionKey = implode(':', [
                $journeyCargoId,
                $orderItemId,
                (int) $row['target_warehouse_id'],
                (string) ($row['unload_sequence'] ?? 'null'),
            ]);

            if (!isset($allowedOrderItemIds[$orderItemId])) {
                throw ValidationException::withMessages([
                    "unload_instructions.$idx.order_item_id" => 'Order item non appartenente al journey.',
                ]);
            }
            if (!isset($allowedJourneyCargoIds[$journeyCargoId])) {
                throw ValidationException::withMessages([
                    "unload_instructions.$idx.journey_cargo_id" => 'Journey cargo non appartenente al journey.',
                ]);
            }

            if (isset($seenInstructionKey[$instructionKey])) {
                throw ValidationException::withMessages([
                    "unload_instructions.$idx" => 'Istruzione di scarico duplicata nel payload.',
                ]);
            }
            $seenInstructionKey[$instructionKey] = true;

            if (($row['instruction_type'] ?? null) === 'double' && !isset($row['unload_sequence'])) {
                throw ValidationException::withMessages([
                    "unload_instructions.$idx.unload_sequence" => 'unload_sequence obbligatoria per doppio scarico.',
                ]);
            }

            if (($row['proposed_for_transshipment'] ?? false) && empty($row['transshipment_reason'])) {
                throw ValidationException::withMessages([
                    "unload_instructions.$idx.transshipment_reason" => 'Motivo obbligatorio quando si propone trasbordo.',
                ]);
            }
        }

        $submittedDecisionPairs = [];
        $cargoWarehouseById = DB::table('journey_cargos')
            ->where('journey_id', $journey->id)
            ->pluck('warehouse_id', 'id');

        foreach (data_get($validated, 'mismatch_decisions', []) as $idx => $row) {
            $orderItemId = (int) $row['order_item_id'];
            $journeyCargoId = (int) $row['journey_cargo_id'];
            $pairKey = $journeyCargoId . ':' . $orderItemId;

            if (!isset($allowedOrderItemIds[$orderItemId])) {
                throw ValidationException::withMessages([
                    "mismatch_decisions.$idx.order_item_id" => 'Order item non appartenente al journey.',
                ]);
            }
            if (!isset($allowedJourneyCargoIds[$journeyCargoId])) {
                throw ValidationException::withMessages([
                    "mismatch_decisions.$idx.journey_cargo_id" => 'Journey cargo non appartenente al journey.',
                ]);
            }
            if (isset($submittedDecisionPairs[$pairKey])) {
                throw ValidationException::withMessages([
                    "mismatch_decisions.$idx" => 'Decisione mismatch duplicata nel payload.',
                ]);
            }
            $submittedDecisionPairs[$pairKey] = true;

            $decision = (string) $row['decision'];
            $secondaryWarehouseId = $row['secondary_warehouse_id'] ?? null;
            $effectiveWarehouseId = isset($cargoWarehouseById[$journeyCargoId]) ? (int) $cargoWarehouseById[$journeyCargoId] : null;

            if ($decision === 'double_unload' && empty($secondaryWarehouseId)) {
                throw ValidationException::withMessages([
                    "mismatch_decisions.$idx.secondary_warehouse_id" => 'Secondo magazzino obbligatorio per doppio scarico.',
                ]);
            }

            if ($decision === 'double_unload' && $effectiveWarehouseId && (int) $secondaryWarehouseId === $effectiveWarehouseId) {
                throw ValidationException::withMessages([
                    "mismatch_decisions.$idx.secondary_warehouse_id" => 'Il secondo magazzino deve essere diverso da quello effettivo del cargo.',
                ]);
            }
        }
    }

    private function resolveCurrentMismatchPairs(Journey $journey): array
    {
        $pairs = [];
        $cargoWarehouseById = DB::table('journey_cargos')
            ->where('journey_id', $journey->id)
            ->pluck('warehouse_id', 'id');

        $allocations = JourneyCargoAllocation::query()
            ->where('journey_id', $journey->id)
            ->where('source', 'actual')
            ->get(['journey_cargo_id', 'order_item_id', 'allocated_containers']);

        if ($allocations->isEmpty()) {
            return [];
        }

        $plannedWarehouseByItem = DB::table('order_items')
            ->whereIn('id', $allocations->pluck('order_item_id')->unique()->values())
            ->pluck('warehouse_id', 'id');

        foreach ($allocations as $allocation) {
            if ((int) $allocation->allocated_containers <= 0) {
                continue;
            }

            $journeyCargoId = (int) $allocation->journey_cargo_id;
            $orderItemId = (int) $allocation->order_item_id;
            $effectiveWarehouseId = $cargoWarehouseById[$journeyCargoId] ?? null;
            $plannedWarehouseId = $plannedWarehouseByItem[$orderItemId] ?? null;

            if (!$effectiveWarehouseId || !$plannedWarehouseId) {
                continue;
            }

            if ((int) $effectiveWarehouseId === (int) $plannedWarehouseId) {
                continue;
            }

            $pairKey = $journeyCargoId . ':' . $orderItemId;
            $pairs[$pairKey] = [
                'journey_cargo_id' => $journeyCargoId,
                'order_item_id' => $orderItemId,
                'effective_warehouse_id' => (int) $effectiveWarehouseId,
                'planned_warehouse_id' => (int) $plannedWarehouseId,
            ];
        }

        return $pairs;
    }

    private function validateEventPayload(Journey $journey, string $eventCode, array $payload): void
    {
        if ($eventCode !== 'transshipment_proposed') {
            return;
        }

        $required = ['order_item_id', 'from_warehouse_id', 'to_warehouse_id', 'containers'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $payload)) {
                throw ValidationException::withMessages([
                    "payload.$key" => "Campo obbligatorio per evento {$eventCode}.",
                ]);
            }
        }

        if ((int) $payload['containers'] <= 0) {
            throw ValidationException::withMessages([
                'payload.containers' => 'Il numero contenitori deve essere maggiore di zero.',
            ]);
        }

        if ((int) $payload['from_warehouse_id'] === (int) $payload['to_warehouse_id']) {
            throw ValidationException::withMessages([
                'payload.to_warehouse_id' => 'Il magazzino di destinazione deve essere diverso da quello di origine.',
            ]);
        }

        $warehouseIds = DB::table('warehouses')
            ->whereIn('id', [(int) $payload['from_warehouse_id'], (int) $payload['to_warehouse_id']])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!in_array((int) $payload['from_warehouse_id'], $warehouseIds, true)) {
            throw ValidationException::withMessages([
                'payload.from_warehouse_id' => 'Magazzino origine non valido.',
            ]);
        }
        if (!in_array((int) $payload['to_warehouse_id'], $warehouseIds, true)) {
            throw ValidationException::withMessages([
                'payload.to_warehouse_id' => 'Magazzino destinazione non valido.',
            ]);
        }

        $belongsToJourney = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.journey_id', $journey->id)
            ->where('order_items.id', (int) $payload['order_item_id'])
            ->exists();

        if (!$belongsToJourney) {
            throw ValidationException::withMessages([
                'payload.order_item_id' => 'Order item non appartenente al journey.',
            ]);
        }

        $availableContainers = (int) JourneyCargoAllocation::query()
            ->where('journey_id', $journey->id)
            ->where('order_item_id', (int) $payload['order_item_id'])
            ->sum('allocated_containers');

        if ($availableContainers <= 0) {
            $availableContainers = (int) JourneyLoadCensusItem::query()
                ->where('journey_id', $journey->id)
                ->where('order_item_id', (int) $payload['order_item_id'])
                ->value('actual_containers');
        }

        if ($availableContainers > 0 && (int) $payload['containers'] > $availableContainers) {
            throw ValidationException::withMessages([
                'payload.containers' => "Contenitori proposti eccedono la disponibilita item ({$availableContainers}).",
            ]);
        }
    }

    private function validateConfirmBusinessRules(Journey $journey): void
    {
        $allocations = JourneyCargoAllocation::query()
            ->where('journey_id', $journey->id)
            ->where('source', 'actual')
            ->get(['id', 'journey_cargo_id', 'order_item_id', 'allocated_containers']);

        if ($allocations->isEmpty()) {
            throw ValidationException::withMessages([
                'allocations' => 'Impossibile confermare: nessuna allocation reale presente.',
            ]);
        }

        $instructionCounts = JourneyCargoUnloadInstruction::query()
            ->whereIn('journey_cargo_allocation_id', $allocations->pluck('id'))
            ->selectRaw('journey_cargo_allocation_id, COUNT(*) as cnt')
            ->groupBy('journey_cargo_allocation_id')
            ->pluck('cnt', 'journey_cargo_allocation_id');

        $cargoById = DB::table('journey_cargos')
            ->where('journey_id', $journey->id)
            ->get(['id', 'warehouse_id', 'operation_mode'])
            ->keyBy('id');

        foreach ($allocations as $allocation) {
            $cargo = $cargoById->get((int) $allocation->journey_cargo_id);
            $isDropOnly = $cargo
                && (
                    (string) ($cargo->operation_mode ?? '') === 'drop_only'
                    || empty($cargo->warehouse_id)
                );
            if ($isDropOnly) {
                continue;
            }
            if ((int) ($instructionCounts[$allocation->id] ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    'unload_instructions' => "Impossibile confermare: manca istruzione di scarico per allocation {$allocation->id}.",
                ]);
            }
        }

        $censusByItem = JourneyLoadCensusItem::query()
            ->where('journey_id', $journey->id)
            ->pluck('actual_containers', 'order_item_id');

        $allocatedByItem = $allocations
            ->groupBy('order_item_id')
            ->map(fn ($rows) => (int) $rows->sum('allocated_containers'));

        foreach ($allocatedByItem as $orderItemId => $allocatedContainers) {
            $actual = isset($censusByItem[$orderItemId]) ? (int) $censusByItem[$orderItemId] : null;
            if ($actual === null) {
                throw ValidationException::withMessages([
                    'allocations' => "Impossibile confermare: manca census per order_item_id {$orderItemId}.",
                ]);
            }
            if ($allocatedContainers !== $actual) {
                throw ValidationException::withMessages([
                    'allocations' => "Impossibile confermare: split incompleto per order_item_id {$orderItemId} ({$allocatedContainers}/{$actual}).",
                ]);
            }
        }

        $requiredMismatchPairs = $this->resolveCurrentMismatchPairs($journey);
        if (empty($requiredMismatchPairs)) {
            return;
        }

        $decisions = JourneyCargoMismatchDecision::query()
            ->where('journey_id', $journey->id)
            ->get(['journey_cargo_id', 'order_item_id', 'decision', 'secondary_warehouse_id'])
            ->keyBy(fn ($row) => ((int) $row->journey_cargo_id) . ':' . ((int) $row->order_item_id));

        foreach ($requiredMismatchPairs as $pairKey => $pair) {
            $decision = $decisions->get($pairKey);
            if (!$decision) {
                throw ValidationException::withMessages([
                    'mismatch_decisions' => "Impossibile confermare: decisione mancante per order_item_id {$pair['order_item_id']} sul cargo {$pair['journey_cargo_id']}.",
                ]);
            }

            if ((string) $decision->decision === 'double_unload') {
                $secondaryWarehouseId = $decision->secondary_warehouse_id ? (int) $decision->secondary_warehouse_id : null;
                if (!$secondaryWarehouseId) {
                    throw ValidationException::withMessages([
                        'mismatch_decisions' => "Impossibile confermare: magazzino secondario mancante per doppio scarico (order_item_id {$pair['order_item_id']}).",
                    ]);
                }

                if ($secondaryWarehouseId === (int) $pair['effective_warehouse_id']) {
                    throw ValidationException::withMessages([
                        'mismatch_decisions' => "Impossibile confermare: il secondo magazzino deve essere diverso da quello effettivo (order_item_id {$pair['order_item_id']}).",
                    ]);
                }
            }
        }
    }
}
