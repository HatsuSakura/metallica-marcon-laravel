<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Journey;
use App\Enums\OrderStatus;
use App\Enums\JourneyStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DriverJourneyController extends Controller
{


    public function index(Request $request)
    {
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        return redirect()->route('driver.home', array_filter($validated));
    }

    public function show(Journey $journey, \App\Services\Driver\DriverJourneyReadModel $readModel)
    {
        Gate::authorize('view', $journey);
        return inertia('Driver/Journey/Workspace', [
            'journey' => $readModel->load($journey),
            'readOnly' => true,
            'canViewExecutionHistory' => Gate::allows('viewExecutionHistory', $journey),
        ]);
    }

    public function edit(Journey $journey, \App\Services\Driver\DriverJourneyReadModel $readModel)
    {
        Gate::authorize('view', $journey);
        return inertia('Driver/Journey/Workspace', [
            'journey' => $readModel->load($journey),
            'readOnly' => !Gate::allows('update', $journey),
            'canViewExecutionHistory' => Gate::allows('viewExecutionHistory', $journey),
            'warehouseAccess' => Gate::allows('warehouseManage', $journey),
            'executionLookups' => $journey->has_baseline ? [
                'warehouses' => \App\Models\Warehouse::get(['id','name']),
                'cers' => \App\Models\CerCode::orderBy('code')->get(['id','code','description']),
                'holders' => \App\Models\Holder::orderBy('name')->get(['id','name']),
                'customers' => \App\Models\Customer::orderBy('company_name')->get(['id','company_name']),
                'sites' => \App\Models\Site::orderBy('name')->get(['id','customer_id','name','address']),
                'orders' => Order::whereNull('journey_id')->whereIn('status', ['creato','pronto'])->whereNotIn('id', \Illuminate\Support\Facades\DB::table('order_commitments')->select('order_id'))->get(['id','legacy_code','customer_id','site_id']),
                'technicalActions' => \App\Models\JourneyStopAction::where('is_active', true)->get(['id','label']),
            ] : [],
        ]);
    }

}
