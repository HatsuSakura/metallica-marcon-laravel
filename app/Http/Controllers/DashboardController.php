<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Site;
use App\Models\User;
use App\Enums\DispatchStatus;
use App\Enums\UserRole;
use App\Enums\TranshipmentStatus;
use App\Enums\OrderDocumentsStatus;
use App\Enums\JourneyStatus;
use App\Models\Journey;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\JourneyCargo;
use Illuminate\Http\Request;
use App\Enums\CustomerJobType;
use Illuminate\Support\Carbon;
use App\Enums\OrderStatus;
use App\Models\InternalContact;
use App\Enums\JourneyCargoStatus;
use App\Models\Order;
use App\Models\Warehouse;
use App\Models\TransshipmentNeed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function logisticHome(Request $request)
    {
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $nextThreeDaysEnd = $now->copy()->addDays(3)->endOfDay();
        $criticalStatuses = [
            OrderStatus::STATUS_CREATED->value,
            OrderStatus::STATUS_READY->value,
            OrderStatus::STATUS_PLANNED->value,
        ];

        $baseQuery = Order::query()
            ->whereNotNull('fixed_withdraw_at')
            ->with([
                'customer:id,company_name',
                'site:id,name,address',
            ]);

        $overdueFixedOrders = (clone $baseQuery)
            ->where('fixed_withdraw_at', '<', $now)
            ->whereIn('status', $criticalStatuses)
            ->orderBy('fixed_withdraw_at')
            ->get([
                'id',
                'legacy_code',
                'customer_id',
                'site_id',
                'status',
                'fixed_withdraw_at',
                'expected_withdraw_at',
            ]);

        $upcomingFixedOrders = (clone $baseQuery)
            ->where('fixed_withdraw_at', '>=', $now)
            ->orderBy('fixed_withdraw_at')
            ->limit(10)
            ->get([
                'id',
                'legacy_code',
                'customer_id',
                'site_id',
                'status',
                'fixed_withdraw_at',
                'expected_withdraw_at',
            ]);

        $orderStatusCountsRaw = Order::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $orderKpis = [
            'open_total' => Order::query()
                ->where('status', '!=', OrderStatus::STATUS_CLOSED->value)
                ->count(),
            'created' => (int) ($orderStatusCountsRaw[OrderStatus::STATUS_CREATED->value] ?? 0),
            'ready' => (int) ($orderStatusCountsRaw[OrderStatus::STATUS_READY->value] ?? 0),
            'planned' => (int) ($orderStatusCountsRaw[OrderStatus::STATUS_PLANNED->value] ?? 0),
            'executed' => (int) ($orderStatusCountsRaw[OrderStatus::STATUS_EXECUTED->value] ?? 0),
            'downloaded' => (int) ($orderStatusCountsRaw[OrderStatus::STATUS_DOWNLOADED->value] ?? 0),
            'closed' => (int) ($orderStatusCountsRaw[OrderStatus::STATUS_CLOSED->value] ?? 0),
            'fixed_overdue' => (clone $baseQuery)
                ->where('fixed_withdraw_at', '<', $now)
                ->whereIn('status', $criticalStatuses)
                ->count(),
            'fixed_today' => (clone $baseQuery)
                ->whereBetween('fixed_withdraw_at', [$todayStart, $todayEnd])
                ->whereIn('status', $criticalStatuses)
                ->count(),
            'fixed_next_3d' => (clone $baseQuery)
                ->where('fixed_withdraw_at', '>', $todayEnd)
                ->where('fixed_withdraw_at', '<=', $nextThreeDaysEnd)
                ->whereIn('status', $criticalStatuses)
                ->count(),
            'docs_misaligned' => Order::query()
                ->where('status', OrderStatus::STATUS_READY->value)
                ->where('documents_status', '!=', OrderDocumentsStatus::GENERATED->value)
                ->count(),
        ];

        $journeyStatusCountsRaw = Journey::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $journeyToCloseBase = Journey::query()
            ->whereIn('status', [
                JourneyStatus::STATUS_EXECUTED->value,
                JourneyStatus::STATUS_CLOSED->value,
            ])
            ->where('dispatch_status', '!=', DispatchStatus::MANAGED->value);

        $journeyKpis = [
            'created' => (int) ($journeyStatusCountsRaw[JourneyStatus::STATUS_CREATED->value] ?? 0),
            'active' => (int) ($journeyStatusCountsRaw[JourneyStatus::STATUS_ACTIVE->value] ?? 0),
            'executed' => (int) ($journeyStatusCountsRaw[JourneyStatus::STATUS_EXECUTED->value] ?? 0),
            'closed' => (int) ($journeyStatusCountsRaw[JourneyStatus::STATUS_CLOSED->value] ?? 0),
            'active_today' => Journey::query()
                ->where('status', JourneyStatus::STATUS_ACTIVE->value)
                ->whereBetween('planned_start_at', [$todayStart, $todayEnd])
                ->count(),
            'late_return' => Journey::query()
                ->where('status', JourneyStatus::STATUS_ACTIVE->value)
                ->whereNotNull('planned_end_at')
                ->where('planned_end_at', '<', $now)
                ->count(),
            'to_close' => (clone $journeyToCloseBase)->count(),
            'to_close_over_24h' => (clone $journeyToCloseBase)
                ->where(function ($query) use ($now) {
                    $query
                        ->whereNotNull('actual_end_at')
                        ->where('actual_end_at', '<=', $now->copy()->subDay())
                        ->orWhere(function ($inner) use ($now) {
                            $inner
                                ->whereNull('actual_end_at')
                                ->whereNotNull('planned_end_at')
                                ->where('planned_end_at', '<=', $now->copy()->subDay());
                        });
                })
                ->count(),
            'managed_today' => Journey::query()
                ->where('dispatch_status', DispatchStatus::MANAGED->value)
                ->whereBetween('dispatch_managed_at', [$todayStart, $todayEnd])
                ->count(),
        ];

        $groundingBase = JourneyCargo::query()
            ->where('is_grounded', true)
            ->whereHas('journey', fn ($query) => $query->where('dispatch_status', '!=', DispatchStatus::MANAGED->value));

        $operationsKpis = [
            'groundings_active' => (clone $groundingBase)->count(),
            'groundings_over_24h' => (clone $groundingBase)
                ->where('updated_at', '<=', $now->copy()->subDay())
                ->count(),
            'transshipments_active' => TransshipmentNeed::query()
                ->where('status', '!=', TranshipmentStatus::CANCELLED->value)
                ->count(),
            'transshipments_proposed' => TransshipmentNeed::query()
                ->where('status', TranshipmentStatus::PROPOSED->value)
                ->count(),
            'transshipments_cancelled_7d' => TransshipmentNeed::query()
                ->where('status', TranshipmentStatus::CANCELLED->value)
                ->where('updated_at', '>=', $now->copy()->subDays(7))
                ->count(),
        ];

        return inertia('Dashboard/Logistic', [
            'overdueFixedOrders' => $overdueFixedOrders,
            'upcomingFixedOrders' => $upcomingFixedOrders,
            'fixedOrdersSnapshotAt' => $now->toIso8601String(),
            'orderKpis' => $orderKpis,
            'journeyKpis' => $journeyKpis,
            'operationsKpis' => $operationsKpis,
        ]);
    }

    public function index(Request $request){

        $journeys = Journey::query()
        //->alphabetic()
        //->filter($filters)
        ->with('driver')
        ->with('vehicle')
        ->with('cargoForVehicle')
        ->with('trailer')
        ->with('cargoForTrailer')
        ->withCount('journeyCargos')
        ->paginate(25)
        ->withQueryString();

        $transshipments = OrderItem::query()
        ->where('is_transshipment', true)
        ->with('cerCode')
        ->with('holder')
        ->with('order.customer', 'order.site')
        ->get();

        $groundings = JourneyCargo::query()
        ->where('status', JourneyCargoStatus::STATUS_CREATED->value)
        ->where('is_grounded', true)
        ->with('cargo')
        ->with('journey')
        ->with('warehouse')
        ->get();

        $doubleLoadItems = OrderItem::select(
            'order_items.*',
            'journey_cargo_order_item.is_double_load',
            'journey_cargo_order_item.download_warehouse_id'
        )
        ->join('journey_cargo_order_item', 'order_items.id', '=', 'journey_cargo_order_item.order_item_id')
        ->where('journey_cargo_order_item.is_double_load', true)
        ->with('cerCode')
        ->with('holder')
        ->with('warehouse')
        ->with('order.customer', 'order.site')
        ->get();

        $journeyCargos = JourneyCargo::query()
        ->where('status', JourneyCargoStatus::STATUS_CREATED->value)
        ->where('is_grounded', false)
        ->with('cargo')
        ->with('journey')
        ->with('warehouse')
        ->get();

        $itemsCount = OrderItem::select('warehouse_manager_id', DB::raw('COUNT(*) as total'))
        ->with('warehouseManager')
        ->groupBy('warehouse_manager_id')
        ->get();

        return inertia(
            'Dashboard/Index',
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

    public function logisticOperations(Request $request)
    {
        $transshipmentView = $request->string('transshipments', 'active')->toString();
        if (!in_array($transshipmentView, ['active', 'cancelled'], true)) {
            $transshipmentView = 'active';
        }

        $groundings = JourneyCargo::query()
            ->where('is_grounded', true)
            ->whereHas('journey', fn ($query) => $query->where('dispatch_status', '!=', DispatchStatus::MANAGED->value))
            ->with([
                'journey:id,dispatch_status,vehicle_id,trailer_id',
                'journey.vehicle:id,plate,name',
                'journey.trailer:id,plate,name',
                'warehouse:id,name',
            ])
            ->orderByDesc('updated_at')
            ->get([
                'id',
                'journey_id',
                'cargo_location',
                'warehouse_id',
                'is_grounded',
                'operation_mode',
                'updated_at',
            ]);

        $transshipments = TransshipmentNeed::query()
            ->when(
                $transshipmentView === 'cancelled',
                fn ($query) => $query->where('status', TranshipmentStatus::CANCELLED->value),
                fn ($query) => $query->where('status', '!=', TranshipmentStatus::CANCELLED->value)
            )
            ->with([
                'journey:id,dispatch_status',
                'orderItem:id,order_id,holder_id,description,holder_quantity',
                'orderItem.holder:id,name',
                'orderItem.order:id,legacy_code,customer_id',
                'orderItem.order.customer:id,company_name',
                'fromWarehouse:id,name',
                'toWarehouse:id,name',
            ])
            ->orderByDesc('id')
            ->get([
                'id',
                'journey_id',
                'order_item_id',
                'from_warehouse_id',
                'to_warehouse_id',
                'quantity_containers',
                'status',
                'notes',
                'updated_at',
            ]);

        $transshipmentCounts = [
            'active' => TransshipmentNeed::query()
                ->where('status', '!=', TranshipmentStatus::CANCELLED->value)
                ->count(),
            'cancelled' => TransshipmentNeed::query()
                ->where('status', TranshipmentStatus::CANCELLED->value)
                ->count(),
        ];

        return inertia('Dashboard/LogisticOperations', [
            'groundings' => $groundings,
            'transshipments' => $transshipments,
            'transshipmentView' => $transshipmentView,
            'transshipmentCounts' => $transshipmentCounts,
        ]);
    }

}
