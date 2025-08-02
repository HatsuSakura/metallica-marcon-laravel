<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Site;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\JourneyCargo;
use Illuminate\Http\Request;
use App\Enums\CustomerJobType;
use Illuminate\Support\Carbon;
use App\Models\InternalContact;
use App\Enums\JourneyCargosState;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RelatorDashboardController extends Controller
{

    public function index(Request $request){

        $journeys = Journey::query()
        //->alphabetic()
        //->filter($filters)
        ->with('driver')
        ->with('vehicle')
        ->with('cargoForVehicle')
        ->with('trailer')
        ->with('cargoForTrailer')
        ->withCount('journeyCargo')
        ->paginate(25)
        ->withQueryString();

        $transshipments = OrderItem::query()
        ->where('is_transshipment', true)
        ->with('cerCode')
        ->with('holder')
        ->with('order.customer', 'order.site')
        ->get();

        $groundings = JourneyCargo::query()
        ->where('state', JourneyCargosState::STATE_CREATED->value)
        ->where('is_grounding', true)
        ->with('cargo')
        ->with('journey')
        ->with('warehouse')
        ->get();

        $doubleLoadItems = OrderItem::select(
            'order_items.*',
            'journey_cargo_order_item.is_double_load',
            'journey_cargo_order_item.warehouse_download_id'
        )
        ->join('journey_cargo_order_item', 'order_items.id', '=', 'journey_cargo_order_item.order_item_id')
        ->where('journey_cargo_order_item.is_double_load', true)
        ->with('cerCode')
        ->with('holder')
        ->with('warehouse')
        ->with('order.customer', 'order.site')
        ->get();

        $journeyCargos = JourneyCargo::query()
        ->where('state', JourneyCargosState::STATE_CREATED->value)
        ->where('is_grounding', false)
        ->with('cargo')
        ->with('journey')
        ->with('warehouse')
        ->get();

        $itemsCount = OrderItem::select('warehouse_manager_id', DB::raw('COUNT(*) as total'))
        ->with('warehouseManager')
        ->groupBy('warehouse_manager_id')
        ->get();

        return inertia(
            'Relator/Dashboard/Index',
            [
                'journeys' => $journeys,
                'transshipments' => $transshipments,
                'groundings' => $groundings,
                'journeyCargos' => $journeyCargos,
                'doubleLoadItems' => $doubleLoadItems,
                'itemsCount' => $itemsCount,
                'warehouses' => Warehouse::all()->load(['chiefs', 'managers']),
            ]);
    }

}