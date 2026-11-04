<?php

namespace App\Http\Middleware;

use App\Models\Journey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\JourneyExecution\JourneyExecutionService;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Transaction boundary shared by all legacy planning CRUD, including direct API calls. */
class PlanningWriteBoundary
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }
        $name = $request->route()?->getName() ?? '';
        $planning = preg_match('/^(?:order|driver\.order)\.(?:store|update|destroy|restore)$/', $name)
            || preg_match('/^journey\.(?:store|update|destroy)$/', $name);
        $legacyWarehouse = $request->is('api/warehouse-order*') || $request->is('warehouse-manager/orders/*') || $request->is('api/warehouse-journey-cargos/*') || $request->is('api/logistic/dispatch/*') || $request->is('api/order/updateState/*') || $request->is('api/order-items/*') || $request->is('api/order-item-explosions/*') || $request->is('warehouse-manager/order-item/*') || $request->is('api/logistic/transshipments/*');
        if (! $planning && ! $legacyWarehouse) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next, $legacyWarehouse) {
            $ids = [];
            if ($request->is('api/order-item-explosions/*')) {
                $itemId = DB::table('order_item_explosions')->where('id', $request->route('id'))->value('order_item_id');
                $source = $itemId ? OrderItem::findOrFail($itemId)->order : null;
                if ($source?->journey_id) {
                    $ids[] = $source->journey_id;
                }
            }
            foreach ($request->route()->parameters() as $model) {
                if ($model instanceof Journey) {
                    $ids[] = $model->id;
                }
                if ($model instanceof \App\Models\TransshipmentNeed) {
                    $ids[] = $model->journey_id;
                }
                if ($model instanceof OrderItem) {
                    $model = $model->order;
                }
                if ($model instanceof Order && $model->journey_id) {
                    $ids[] = $model->journey_id;
                }
            }
            if ($request->filled('order_id')) {
                $source = Order::findOrFail($request->integer('order_id'));
                if ($source->journey_id) {
                    $ids[] = $source->journey_id;
                }
            }
            $bulkItemIds = collect($request->input('items', []))->pluck('id')->filter()->all();
            if ($bulkItemIds) {
                $ids = array_merge($ids, Order::whereIn('id', OrderItem::whereIn('id', $bulkItemIds)->select('order_id'))->whereNotNull('journey_id')->pluck('journey_id')->all());
            }
            $cargo = $request->route('journeyCargo');
            if ($cargo instanceof \App\Models\JourneyCargo) {
                $ids[] = $cargo->journey_id;
            }
            if ($request->filled('journey_id')) {
                $ids[] = (int) $request->input('journey_id');
            }
            $selected = array_merge((array) $request->input('orders_truck', []), (array) $request->input('orders_trailer', []), (array) $request->input('orders_fulfill', []));
            if ($selected) {
                $ids = array_merge($ids, Order::whereIn('id', $selected)->whereNotNull('journey_id')->pluck('journey_id')->all());
            }
            $journeys = Journey::whereIn('id', array_unique($ids))->orderBy('id')->lockForUpdate()->get();
            foreach ($journeys as $journey) {
                abort_if($journey->baseline()->exists() || $journey->status !== \App\Enums\JourneyStatus::STATUS_CREATED, 409, 'Viaggio partito: usa il contesto esecutivo. I viaggi legacy senza baseline sono in sola lettura.');
                if ($legacyWarehouse && $journey->status === \App\Enums\JourneyStatus::STATUS_CREATED) {
                    \Illuminate\Support\Facades\Gate::authorize('create', Journey::class);
                }
            }
            $previousOrderIds = Order::whereIn('journey_id', $ids)->pluck('id')->all();
            $response = $next($request);
            $selected = array_merge($selected, $previousOrderIds);
            if ($response->getStatusCode() >= 400) {
                throw new HttpResponseException($response);
            }
            // Maintain reservations even when a resource was assigned/detached by old planning CRUD.
            $order = $request->route('order');
            if ($order instanceof Order) {
                $selected[] = $order->id;
            }
            $freshJourney = $request->route('journey');
            if ($freshJourney instanceof Journey) {
                $ids[] = $freshJourney->id;
            }
            $assigned = Order::withTrashed()->where(fn ($q) => $q->whereIn('journey_id', $ids)->orWhereIn('id', $selected))->lockForUpdate()->get();
            foreach ($assigned as $source) {
                if ($source->journey_id && ! $source->deleted_at) {
                    app(JourneyExecutionService::class)->claim($source, $source->journey_id);
                } else {
                    DB::table('order_commitments')->where('order_id', $source->id)->delete();
                }
            }

            return $response;
        });
    }
}
