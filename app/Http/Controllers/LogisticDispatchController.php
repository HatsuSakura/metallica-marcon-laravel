<?php

namespace App\Http\Controllers;

use App\Enums\DispatchStatus;
use App\Enums\JourneyStatus;
use App\Models\Journey;
use App\Models\Warehouse;
use App\Services\Dispatch\JourneyDispatchStatusService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;

class LogisticDispatchController extends Controller
{
    public function __construct(
        private JourneyDispatchStatusService $dispatchStatusService
    ) {
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'to_manage');
        $perPage = 20;
        $page = max(1, (int) $request->query('page', 1));

        $allJourneys = Journey::query()
            ->with([
                'driver:id,name,surname',
                'vehicle:id,plate,name',
                'trailer:id,plate,name',
                'primaryWarehouse:id,name',
                'secondaryWarehouse:id,name',
                'orders:id,journey_id,customer_id,site_id,status,notes,legacy_code',
                'orders.customer:id,company_name',
                'orders.site:id,name,address',
            ])
            ->whereIn('status', [
                JourneyStatus::STATUS_ACTIVE->value,
                JourneyStatus::STATUS_EXECUTED->value,
                JourneyStatus::STATUS_CLOSED->value,
            ])
            ->orderByDesc('planned_end_at')
            ->get();

        $statusByJourneyId = $this->dispatchStatusService->resolveForJourneyIds(
            $allJourneys->pluck('id')->all()
        );

        $filteredJourneys = $allJourneys
            ->map(function (Journey $journey) use ($statusByJourneyId) {
                $journey->setAttribute('dispatch_status', $statusByJourneyId[(int) $journey->id] ?? JourneyDispatchStatusService::STATUS_PENDING);
                return $journey;
            })
            ->filter(function (Journey $journey) use ($status) {
                $dispatchStatus = $journey->dispatch_status;
                if ($dispatchStatus instanceof DispatchStatus) {
                    $dispatchStatus = $dispatchStatus->value;
                }

                if ($status === 'managed') {
                    return $dispatchStatus === JourneyDispatchStatusService::STATUS_MANAGED;
                }
                return $dispatchStatus !== JourneyDispatchStatusService::STATUS_MANAGED;
            })
            ->values();

        $journeys = new LengthAwarePaginator(
            $filteredJourneys->forPage($page, $perPage)->values(),
            $filteredJourneys->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('LogisticDispatch/Board', [
            'journeys' => $journeys,
            'status' => $status,
            'warehouses' => Warehouse::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(Journey $journey)
    {
        $journey->load([
            'driver:id,name,surname',
            'vehicle:id,plate,name',
            'trailer:id,plate,name',
            'primaryWarehouse:id,name',
            'secondaryWarehouse:id,name',
            'orders:id,journey_id,customer_id,site_id,status,notes,cargo_location,legacy_code',
            'orders.customer:id,company_name',
            'orders.site:id,name,address',
            'orders.items:id,order_id,holder_id,cer_code_id,description,holder_quantity,weight_declared,warehouse_id,status',
            'orders.items.holder:id,name',
            'orders.items.cerCode:id,code,description',
        ]);
        $journey->setAttribute('dispatch_status', $this->dispatchStatusService->resolveCurrentStatus($journey));

        return Inertia::render('LogisticDispatch/Show', [
            'journey' => $journey,
            'warehouses' => Warehouse::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}

