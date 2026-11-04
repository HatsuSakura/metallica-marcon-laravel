<?php

namespace App\Services\JourneyExecution;

use App\Models\CerCode;
use App\Models\Customer;
use App\Models\Holder;
use App\Models\InternalContact;
use App\Models\Journey;
use App\Models\JourneyOrderExecution;
use App\Models\Order;
use App\Models\OrderHolder;
use App\Models\OrderItem;
use App\Models\OrderItemGroup;
use App\Models\Site;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ExecutionProjection
{
    public function order(JourneyOrderExecution $execution, ?Journey $journey = null): Order
    {
        $execution->loadMissing(['items.allocations', 'holders']);
        $journey ??= $execution->journey;
        $journey->loadMissing(['vehicle', 'trailer', 'driver']);
        $documentJourney = (new Journey)->setRawAttributes($journey->getAttributes());
        foreach (['vehicle', 'trailer', 'driver'] as $relation) {
            $documentJourney->setRelation($relation, $journey->$relation);
        }
        $data = $execution->data;
        $order = $this->model(Order::class, $data);
        $order->setAttribute('id', $execution->source_order_id ?? ('E'.$execution->id));
        $order->setAttribute('journey_id', $journey->id);
        $order->setAttribute('status', $data['status'] ?? 'creato');
        $order->setAttribute('execution_order_id', $execution->id);
        $order->setAttribute('execution_status', $execution->status);
        $baseline = $journey->baseline?->data ?? [];
        $originalOrder = collect($baseline['orders'] ?? [])->firstWhere('id', $execution->source_order_id);
        $order->setAttribute('original_plan', $originalOrder ? ['items' => $originalOrder['items'], 'holders' => $originalOrder['holders']] : null);
        $originalItems = collect($baseline['orders'] ?? [])->flatMap(fn ($o) => $o['items'] ?? [])->keyBy('id');
        $order->setAttribute('removed_items', $execution->items->where('removed', true)->map(fn ($i) => ['id' => $i->id, 'description' => $i->data['description'] ?? '', 'cer_code' => $i->data['cer_code']['code'] ?? ''])->values()->all());
        foreach (['documents_base_path', 'documents_status', 'documents_version', 'documents_generated_at', 'documents_error', 'documents_input_hash', 'documents_requested_hash', 'documents_generation_started_at'] as $field) {
            $order->setAttribute($field, $execution->getRawOriginal($field));
        }
        $customer = $this->model(Customer::class, $data['customer'] ?? []);
        $customer->setRelation('seller', $this->model(User::class, $data['customer']['seller'] ?? []));
        $site = $this->model(Site::class, $data['site'] ?? []);
        $site->setRelation('internalContacts', new Collection(array_map(fn ($c) => $this->model(InternalContact::class, $c), $data['site']['internal_contacts'] ?? [])));
        $site->setRelation('timetable', $this->model(Timetable::class, $data['site']['timetable'] ?? []));
        $order->setRelation('customer', $customer)->setRelation('site', $site)
            ->setRelation('logisticsUser', $this->model(User::class, $data['logistics_user'] ?? []))
            ->setRelation('journey', $documentJourney);
        $items = $execution->items->where('removed', false)->map(function ($item) use ($originalItems, $journey, $execution) {
            $item->setRelation('orderExecution', $execution);
            $row = $this->model(OrderItem::class, app(ItemCargoLocation::class)->data($journey, $item));
            $row->setAppends([]);
            $row->setAttribute('id', $item->id);
            $row->setAttribute('source_order_item_id', $item->source_order_item_id);
            $row->setAttribute('actual_containers', $item->actual_containers);
            $row->setAttribute('is_loaded', (bool) $item->is_loaded);
            $row->setAttribute('load_allocations', $item->allocations->map(fn ($allocation) => ['cargo_location' => $allocation->cargo_location, 'containers' => $allocation->containers])->values()->all());
            $row->setAttribute('load_total', app(ItemLoad::class)->total($item));
            $row->setAttribute('load_allocation_valid', app(ItemLoad::class)->valid($item));
            $row->setAttribute('actual_weight_kg', $item->actual_weight_kg);
            $row->setAttribute('original_values', $originalItems->get($item->source_order_item_id));
            $row->setRelation('holder', $this->model(Holder::class, $item->data['holder'] ?? []));
            $row->setRelation('cerCode', $this->model(CerCode::class, $item->data['cer_code'] ?? []));
            $row->setRelation('orderItemGroup', $this->model(OrderItemGroup::class, $item->data['order_item_group'] ?? []));

            return $row;
        });
        $holders = $execution->holders->map(function ($holder) {
            $row = $this->model(OrderHolder::class, $holder->data);
            $row->setAttribute('id', $holder->id);
            $row->setAttribute('actual_delivered', $holder->actual_delivered);

            return $row->setRelation('holder', $this->model(Holder::class, $holder->data['holder'] ?? []));
        });

        return $order->setRelation('items', new Collection($items->values()->all()))->setRelation('holders', new Collection($holders->values()->all()));
    }

    private function model(string $class, array $data): Model
    {
        $model = new $class;
        $model->setRawAttributes(array_filter($data, fn ($value) => ! is_array($value)));

        return $model;
    }
}
