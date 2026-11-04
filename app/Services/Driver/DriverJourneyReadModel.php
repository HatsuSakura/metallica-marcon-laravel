<?php

namespace App\Services\Driver;

use App\Models\Journey;
use App\Models\JourneyStopOrder;
use App\Services\JourneyExecution\ExecutionProjection;
use Illuminate\Database\Eloquent\Collection;

class DriverJourneyReadModel
{
    public function load(Journey $journey): Journey
    {
        $relations = ['vehicle', 'trailer', 'stops' => fn ($q) => $q->orderBy('sequence')->orderBy('id'), 'stops.customer' => fn ($q) => $q->withTrashed(), 'stops.technicalAction'];
        $managed = $journey->baseline()->exists();
        $journey->setAttribute('has_baseline', $managed);
        $journey->setAttribute('legacy_execution', ! $managed && $journey->status?->value !== 'creato');
        $journey->setAttribute('container_balance', null);
        if (! $managed) {
            return $journey->load(array_merge($relations, ['stops.stopOrders.order.site' => fn ($q) => $q->withTrashed(), 'stops.stopOrders.order.site.timetable', 'stops.stopOrders.order.site.internalContacts:id,site_id,name,surname,mobile,phone,role',
                'stops.stopOrders.order.customer' => fn ($q) => $q->withTrashed(), 'stops.stopOrders.order.items.cerCode', 'stops.stopOrders.order.items.holder',
                'stops.stopOrders.order.items.orderItemGroup', 'stops.stopOrders.order.holders.holder', 'stops.stopOrders.order.items.journeyCargos']))->loadCount('stops');
        }
        $journey->load($relations);
        $executions = $journey->orderExecutions()->with(['items.allocations', 'holders'])->get();
        foreach ($executions as $execution) {
            $execution->setRelation('journey', $journey);
            foreach ($execution->items as $item) {
                $item->setRelation('orderExecution', $execution);
            }
        }
        // Operational phonebook stays current; frozen execution/document data is untouched.
        $siteIds = $executions->map(fn ($execution) => $execution->data['site']['id'] ?? $execution->data['site_id'] ?? null)->filter()->unique();
        $contacts = \App\Models\InternalContact::whereIn('site_id', $siteIds)->get(['id', 'site_id', 'name', 'surname', 'mobile', 'phone', 'role'])->groupBy('site_id');
        $journey->setAttribute('container_balance', app(\App\Services\JourneyExecution\ContainerBalance::class)->calculate($journey->baseline->data['orders'], $executions));
        $previews = app(\App\Services\JourneyExecution\ExecutionRestoration::class)->previews($journey, $executions->where('status', 'pending')->flatMap->items);
        $reopening = app(\App\Services\JourneyExecution\StopReopening::class)->previews($journey, $executions);
        $orders = new Collection;
        foreach ($journey->stops as $stop) {
            $stop->setAttribute('reopen_available', $reopening[$stop->id]['available']);
            $stop->setAttribute('reopen_unavailable_reason', $reopening[$stop->id]['reason']);
            $links = new Collection;
            foreach ($executions->where('journey_stop_id', $stop->id) as $execution) {
                $order = app(ExecutionProjection::class)->order($execution, $journey);
                $siteId = $order->site?->id ?? $order->site_id;
                if ($siteId) {
                    $order->site->setRelation('internalContacts', $contacts->get($siteId, new Collection));
                }
                foreach ($order->items as $item) {
                    $item->setAttribute('restore_options', $previews[$item->id] ?? []);
                }
                $order->setAttribute('removed_items', array_map(fn ($item) => $item + ['restore_options' => $previews[$item['id']] ?? []], $order->removed_items));
                $link = new JourneyStopOrder;
                $link->setRawAttributes(['id' => $execution->id, 'journey_id' => $journey->id, 'journey_stop_id' => $stop->id, 'order_id' => $order->id]);
                $link->setRelation('order', $order);
                $links->push($link);
                $orders->push($order);
                if ($stop->kind?->value !== 'technical') {
                    $stop->setRelation('customer', $order->customer);
                }
            }
            $stop->setRelation('stopOrders', $links);
        }
        $census = $executions->flatMap->items->filter(fn ($i) => ! $i->removed && ($i->actual_containers !== null || $i->actual_weight_kg !== null))
            ->map(fn ($i) => ['order_item_id' => $i->id, 'actual_containers' => $i->actual_containers, 'total_weight_kg' => $i->actual_weight_kg])->values();
        $journey->setRelation('orders', $orders)->setRelation('loadCensusItems', $census);
        $overrides = $journey->execution_data ?? [];
        foreach (['notes'] as $field) {
            if (array_key_exists($field, $overrides)) {
                $journey->setAttribute($field, $overrides[$field]);
            }
        }

        return $journey->loadCount('stops');
    }
}
