<?php

namespace App\Http\Controllers;

use App\Enums\JourneyStatus;
use App\Enums\OrderDocumentsStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Cargo;
use App\Models\Holder;
use App\Models\Journey;
use App\Models\JourneyStopAction;
use App\Models\Order;
use App\Models\Trailer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Services\JourneyExecution\ExecutionProjection;
use App\Services\OrderDocumentGenerationService;
use App\Services\Planning\JourneyWriter;
use App\Support\Audit\AuditTrailPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class JourneyController extends Controller
{
    private function buildAuditTrail(Journey $journey): array
    {
        if (! optional(auth()->user())->is_admin) {
            return [];
        }

        return AuditTrailPresenter::forModel($journey);
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Journey::class);

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
        if ($journey->baseline()->exists()) {
            return redirect()->route('journey.execution', ['journey' => $journey->id]);
        }
        Gate::authorize('view', $journey);

        $returnTo = request()->query('return_to');
        if (! is_string($returnTo) || trim($returnTo) === '') {
            $returnTo = url()->previous();
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $isSafeReturnTo = Str::startsWith($returnTo, '/')
            || ($appUrl !== '' && Str::startsWith($returnTo, $appUrl));

        if (! $isSafeReturnTo) {
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
                if (! $order) {
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
                'audits' => $this->buildAuditTrail($journey),
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
        $drivers = User::where('role', UserRole::DRIVER->value)->get();
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
                'items.holder:id,name,volume,is_custom',
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
        $journey = app(JourneyWriter::class)->create($request->all());

        return redirect()->route('journey.edit', $journey)->with('success', 'Viaggio inserito con successo! Genera ora i documenti ordini dal pannello Journey.');
    }

    public function edit(Journey $journey)
    {
        if ($journey->baseline()->exists()) {
            return redirect()->route('journey.execution', ['journey' => $journey->id]);
        }
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
                'audits' => $this->buildAuditTrail($journey),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journey $journey)
    {
        Gate::authorize('update', $journey);
        $journey = app(JourneyWriter::class)->update($journey, $request->all());

        return $this->redirectAfterJourneyUpdate($journey, $request->input('post_action', 'save_exit'), 'Viaggio modificato con successo!');
    }

    private function redirectAfterJourneyUpdate(Journey $journey, string $postAction, string $message): RedirectResponse
    {
        $response = $postAction === 'save_stay'
            ? redirect()->route('journey.edit', ['journey' => $journey->id])
            : redirect()->route('journey.index');

        return $response->with('success', $message);
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

    public function restore(Journey $journey)
    {
        $journey->restore();

        return redirect()->back()->with('success', 'Viaggio ripristinato con successo!');
    }

    /*
        CUSTOM actions for Journey life cycle
    */

    private function statusAfterJourneyDetach(Order $order): string
    {
        return OrderStatus::STATUS_CREATED->value;
    }

    private function invalidateOrderReadyState(Order $order): void
    {
        app(OrderDocumentGenerationService::class)->invalidateAfterReadyOrderEdit($order);
    }

    public function documentsStatus(Journey $journey): JsonResponse
    {
        Gate::authorize('view', $journey);

        $documentsService = app(OrderDocumentGenerationService::class);

        $orders = $journey->baseline()->exists()
            ? $journey->orderExecutions()->with(['items', 'holders'])->get()->map(fn ($e) => app(ExecutionProjection::class)->order($e, $journey))
            : Order::where('journey_id', $journey->id)->orderBy('id')->get();

        $total = $orders->count();
        $ready = $orders->filter(fn (Order $order) => $documentsService->isCurrent($order))->count();

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

                $current = $documentsService->isCurrent($order);
                $generationActive = $order->documents_status === OrderDocumentsStatus::GENERATING && ! $documentsService->isGeneratingStateStale($order);

                return [
                    'id' => $order->id,
                    'legacy_code' => $order->legacy_code,
                    'customer_name' => $order->customer?->company_name,
                    'status' => OrderStatus::fromMixed($order->status)->value,
                    'documents_status' => OrderDocumentsStatus::fromMixed($order->documents_status)->value,
                    'documents_generated_at' => $order->documents_generated_at,
                    'documents_error' => $order->documents_error,
                    'documents_version' => (int) ($order->documents_version ?? 0),
                    'current' => $current,
                    'can_generate' => ! $current && ! $generationActive,
                    'generation_active' => $generationActive,
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
    ): JsonResponse {
        Gate::authorize('update', $journey);

        $validated = $request->validate([
            'order_ids' => ['nullable', 'array'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $orderIds = array_values(array_unique(array_map('intval', $validated['order_ids'] ?? [])));
        $query = Order::query()->where('journey_id', $journey->id);
        if (! empty($orderIds)) {
            $query->whereIn('id', $orderIds);
        }
        $orders = $journey->baseline()->exists()
            ? $journey->orderExecutions()->where('status', '!=', 'released')
                ->when($orderIds, fn ($q) => $q->whereIn('source_order_id', $orderIds))->get()
                ->map(fn ($e) => app(ExecutionProjection::class)->order($e, $journey))
            : $query->get();

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

            if ($documentsState === OrderDocumentsStatus::GENERATING && ! $service->isGeneratingStateStale($order)) {
                $skipped[] = [
                    'order_id' => $order->id,
                    'reason' => 'already_generating',
                ];

                continue;
            }

            if ($documentsState === OrderDocumentsStatus::GENERATING && $service->isGeneratingStateStale($order)) {
                $service->recoverStaleGeneratingState($order);
            }

            if ($service->enqueueGeneration($order)) {
                $queued[] = $order->id;
            } else {
                $skipped[] = ['order_id' => $order->id, 'reason' => 'already_current'];
            }
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
