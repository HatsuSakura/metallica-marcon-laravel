<?php

namespace App\Http\Controllers;

use App\Enums\JourneysState;
use App\Models\Journey;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogisticDispatchController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $journeys = Journey::query()
            ->with([
                'driver:id,name,surname',
                'vehicle:id,plate,name',
                'trailer:id,plate,name',
                'primaryWarehouse:id,name',
                'secondaryWarehouse:id,name',
                'orders:id,journey_id,customer_id,site_id,status,notes',
                'orders.customer:id,company_name',
                'orders.site:id,name,address',
            ])
            ->when(
                $status === 'open',
                fn ($query) => $query->whereIn('status', [
                    JourneysState::STATUS_ACTIVE->value,
                    JourneysState::STATUS_EXECUTED->value,
                ]),
                fn ($query) => $query->where('status', JourneysState::STATUS_CLOSED->value)
            )
            ->orderByDesc('planned_end_at')
            ->paginate(20)
            ->withQueryString();

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
            'orders:id,journey_id,customer_id,site_id,status,notes',
            'orders.customer:id,company_name',
            'orders.site:id,name,address',
            'orders.items:id,order_id,holder_id,cer_code_id,quantity,weight,notes,truck_location',
            'orders.items.holder:id,name',
            'orders.items.cerCode:id,code,description',
        ]);

        return Inertia::render('LogisticDispatch/Show', [
            'journey' => $journey,
            'warehouses' => Warehouse::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
