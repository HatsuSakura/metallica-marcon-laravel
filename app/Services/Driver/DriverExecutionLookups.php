<?php

namespace App\Services\Driver;

use App\Models\CerCode;
use App\Models\Customer;
use App\Models\Holder;
use App\Models\Journey;
use App\Models\JourneyStopAction;
use App\Models\Order;
use App\Models\Site;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class DriverExecutionLookups
{
    public function load(Journey $journey): array
    {
        if (! $journey->has_baseline) {
            return [];
        }

        return [
            'warehouses' => Warehouse::get(['id', 'name']),
            'cers' => CerCode::orderBy('code')->get(['id', 'code', 'description']),
            'holders' => Holder::orderBy('name')->get(['id', 'name']),
            'customers' => Customer::orderBy('company_name')->get(['id', 'company_name']),
            'sites' => Site::orderBy('name')->get(['id', 'customer_id', 'name', 'address']),
            'orders' => Order::whereNull('journey_id')->whereIn('status', ['creato', 'pronto'])->whereNotIn('id', DB::table('order_commitments')->select('order_id'))->get(['id', 'legacy_code', 'customer_id', 'site_id']),
            'technicalActions' => JourneyStopAction::where('is_active', true)->get(['id', 'label']),
        ];
    }
}
