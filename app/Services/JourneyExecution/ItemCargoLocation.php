<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\JourneyExecutionItem;

/** Resolve the planned carrier without changing the frozen plan or source order. */
class ItemCargoLocation
{
    public function planned(array $snapshot, array $order, array $item): ?string
    {
        if (in_array($item['cargo_location'] ?? null, ['vehicle', 'trailer'], true)) {
            return $item['cargo_location'];
        }
        // Capacity orders deliberately have no carrier chosen in advance.
        if (($order['cargo_location'] ?? null) === 'fulfill') {
            return null;
        }
        $cargos = collect($snapshot['cargos'] ?? [])->keyBy('id');
        $allocations = collect($snapshot['allocations'] ?? [])->filter(fn ($row) => isset($item['id']) && ($row['order_item_id'] ?? null) == $item['id']
            && ($row['source'] ?? 'planned') === 'planned' && ($row['allocated_containers'] ?? 1) > 0);
        $links = $allocations->isNotEmpty() ? $allocations : collect($snapshot['cargo_items'] ?? [])->filter(fn ($row) => isset($item['id']) && ($row['order_item_id'] ?? null) == $item['id']);
        $locations = $links->map(fn ($row) => $cargos->get($row['journey_cargo_id'])['cargo_location'] ?? null)
            ->filter(fn ($location) => in_array($location, ['vehicle', 'trailer'], true))->unique()->values();
        if ($locations->count() > 1) {
            return null; // Never silently collapse an existing split into one carrier.
        }

        return $locations->first() ?? (in_array($order['cargo_location'] ?? null, ['vehicle', 'trailer'], true) ? $order['cargo_location'] : 'vehicle');
    }

    public function originals(Journey $journey): \Illuminate\Support\Collection
    {
        $snapshot = $journey->baseline->data;

        return collect($snapshot['orders'])->flatMap(fn ($order) => array_map(
            fn ($item) => array_replace($item, ['cargo_location' => $this->planned($snapshot, $order, $item)]), $order['items'] ?? []))->keyBy('id');
    }

    public function data(Journey $journey, JourneyExecutionItem $item): array
    {
        $data = $item->data;
        if (array_key_exists('cargo_location', $data) && $data['cargo_location'] !== 'fulfill') {
            return $data;
        }
        $original = $item->source_order_item_id ? $this->originals($journey)->get($item->source_order_item_id) : null;
        $location = $original ? $original['cargo_location'] : $this->planned($journey->baseline->data, $item->orderExecution->data, []);

        return array_replace($data, ['cargo_location' => $location]);
    }
}
