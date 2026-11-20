<?php

namespace App\Services\JourneyExecution;

use App\Models\CerCode;
use App\Models\Holder;
use App\Models\Journey;
use App\Models\Warehouse;

/** Semantic snapshot: no generated timestamps, document bookkeeping or UI preferences. */
class ExecutionUpdateSnapshot
{
    public function capture(Journey $journey, bool $original = false): array
    {
        $baseline = $journey->baseline?->data ?? [];
        $plannedOrders = collect($baseline['orders'] ?? [])->keyBy('id');
        $plannedStops = collect($baseline['stops'] ?? [])->keyBy('id');
        $records = [];
        $records['journey:'.$journey->id] = $this->record('Viaggio #'.$journey->id, null, null, null, [
            'notes' => $original ? ($baseline['journey']['notes'] ?? '') : ($journey->execution_data['notes'] ?? $journey->notes ?? ''),
        ] + ($original ? [] : ['journey_status' => $journey->status->value]));
        $orders = $journey->orderExecutions()->with(['items.allocations', 'holders'])->orderBy('id')->get();
        $itemData = $orders->flatMap(fn ($order) => $order->items->pluck('data'))->concat($plannedOrders->flatMap(fn ($order) => $order['items'] ?? []));
        $cers = CerCode::whereIn('id', $itemData->pluck('cer_code_id')->filter())->pluck('code', 'id');
        $containers = Holder::whereIn('id', $itemData->pluck('holder_id')->filter())->pluck('name', 'id');
        $warehouses = Warehouse::whereIn('id', $itemData->pluck('warehouse_id')->filter())->pluck('name', 'id');
        foreach ($journey->stops()->with(['customer', 'technicalAction'])->orderBy('id')->get() as $stop) {
            $plan = $plannedStops->get($stop->id);
            if ($original && ! $plan) {
                continue;
            }
            $label = $stop->customer?->company_name ?: ($stop->description ?: $stop->technicalAction?->label ?: 'Tappa tecnica');
            $values = ['description' => $stop->description ?? '', 'address_text' => $stop->address_text ?? '', 'sequence' => $stop->sequence];
            if ($original) {
                $values = ['description' => $plan['description'] ?? '', 'address_text' => $plan['address_text'] ?? '', 'sequence' => $plan['planned_sequence'] ?? $plan['sequence'] ?? $stop->planned_sequence];
            } else {
                $values += ['status' => $stop->status->value, 'reason_code' => $stop->reason_code ?? '', 'driver_notes' => $stop->driver_notes ?? ''];
            }
            $records['stop:'.$stop->id] = $this->record($label, $stop->id, null, null, $values);
        }
        foreach ($orders as $order) {
            $plan = $plannedOrders->get($order->source_order_id);
            if ($original && ! $plan) {
                continue;
            }
            $data = $original ? $plan : $order->data;
            $label = 'Ordine '.($data['legacy_code'] ?? $order->source_order_id ?? 'E'.$order->id);
            $meta = [$order->journey_stop_id, $order->id, $order->source_order_id];
            $records['order:'.$order->id] = $this->record($label, ...[...$meta, ['notes' => $data['notes'] ?? '', 'cargo_location' => $data['cargo_location'] ?? 'vehicle']]);
            $items = collect($plan['items'] ?? [])->keyBy('id');
            foreach ($order->items as $item) {
                $source = $items->get($item->source_order_item_id);
                if ($original && ! $source) {
                    continue;
                }
                $row = $original ? $source : $item->data;
                $quantity = $original ? ($row['holder_quantity'] ?? null) : ($item->actual_containers ?? $row['holder_quantity'] ?? null);
                $location = $row['cargo_location'] ?? $data['cargo_location'] ?? 'vehicle';
                $split = ! $original && $item->allocations->isNotEmpty();
                $values = [
                    'cer_code' => $original ? ($row['cer_code']['code'] ?? (string) ($row['cer_code_id'] ?? '')) : $cers->get($row['cer_code_id'] ?? null),
                    'description' => $row['description'] ?? '', 'holder' => $original ? ($row['holder']['name'] ?? (string) ($row['holder_id'] ?? '')) : $containers->get($row['holder_id'] ?? null),
                    'holder_quantity' => $row['holder_quantity'] ?? null, 'weight_declared' => $row['weight_declared'] ?? null,
                    'actual_containers' => $original ? ($row['holder_quantity'] ?? null) : ($item->actual_containers ?? $row['holder_quantity'] ?? null),
                    'actual_weight_kg' => $original ? ($row['weight_declared'] ?? null) : ($item->actual_weight_kg ?? $row['weight_declared'] ?? null),
                    'is_bulk' => (bool) ($row['is_bulk'] ?? false), 'cargo_location' => $row['cargo_location'] ?? $data['cargo_location'] ?? 'vehicle',
                    'is_loaded' => $original ? false : (bool) $item->is_loaded,
                    'vehicle_containers' => $split ? $item->allocations->where('cargo_location', 'vehicle')->sum('containers') : ($location === 'vehicle' ? $quantity : 0),
                    'trailer_containers' => $split ? $item->allocations->where('cargo_location', 'trailer')->sum('containers') : ($location === 'trailer' ? $quantity : 0),
                    'weight_gross' => $row['weight_gross'] ?? null, 'weight_tare' => $row['weight_tare'] ?? null, 'weight_net' => $row['weight_net'] ?? null,
                    'warehouse_status' => $row['warehouse_status'] ?? null, 'warehouse_notes' => $row['warehouse_notes'] ?? '',
                    'order_item_group_label' => $row['order_item_group_label'] ?? $row['order_item_group']['label'] ?? '',
                    'hidden_from_driver' => $original ? false : (bool) $item->hidden_from_driver,
                    'warehouse' => $original ? ($row['warehouse']['name'] ?? (isset($row['warehouse_id']) ? $warehouses->get($row['warehouse_id']) : null)) : $warehouses->get($row['warehouse_id'] ?? null), 'removed' => $original ? false : (bool) $item->removed,
                ];
                $records['item:'.$item->id] = $this->record($label.' · CER '.$values['cer_code'], ...[...$meta, $values]);
            }
            $holders = collect($plan['holders'] ?? [])->keyBy('id');
            foreach ($order->holders as $holder) {
                $source = $holders->get($holder->source_order_holder_id);
                if ($original && ! $source) {
                    continue;
                }
                $row = $original ? $source : $holder->data;
                $records['holder:'.$holder->id] = $this->record($label.' · '.($row['holder']['name'] ?? 'Contenitori'), ...[...$meta, [
                    'total_holders_count' => $row['total_holders_count'] ?? null,
                    'actual_delivered' => $original ? ($row['total_holders_count'] ?? null) : ($holder->actual_delivered ?? $row['total_holders_count'] ?? null),
                ]]);
            }
        }

        return $records;
    }

    private function record(string $label, ?int $stopId, ?int $executionOrderId, ?int $orderId, array $values): array
    {
        return ['label' => $label, 'stop_id' => $stopId, 'execution_order_id' => $executionOrderId, 'order_id' => $orderId, 'values' => $values];
    }
}
