<?php

namespace App\Services\JourneyPlanning;

use App\Models\Journey;
use Illuminate\Support\Carbon;

/** Compare business inputs, excluding generated IDs and document bookkeeping. */
class PlanningInput
{
    public function current(Journey $journey): array
    {
        $data = $journey->getAttributes();
        foreach (['vehicle' => 'orders_truck', 'trailer' => 'orders_trailer', 'fulfill' => 'orders_fulfill'] as $location => $key) {
            $data[$key] = $journey->orders()->where('cargo_location', $location)->pluck('id')->all();
        }
        $data['stops'] = $journey->stops()->orderBy('sequence')->get()->map(fn ($s) => $s->getAttributes() + ['orders' => $s->stopOrders()->pluck('order_id')->all()])->all();
        return $this->normalize($data);
    }

    public function normalize(array $input): array
    {
        $out = [];
        foreach (['planned_start_at', 'planned_end_at'] as $key) $out[$key] = empty($input[$key]) ? null : Carbon::parse($input[$key])->format('Y-m-d H:i:s');
        foreach (['vehicle_id', 'trailer_id', 'vehicle_cargo_id', 'trailer_cargo_id', 'driver_id', 'logistics_user_id'] as $key) $out[$key] = empty($input[$key]) ? null : (int) $input[$key];
        $out['notes'] = $input['notes'] ?? '';
        foreach (['orders_truck', 'orders_trailer', 'orders_fulfill'] as $key) {
            $out[$key] = array_map('intval', $input[$key] ?? []);
            sort($out[$key]);
        }
        $stops = $input['stops'] ?? [];
        usort($stops, fn ($a, $b) => ($a['sequence'] ?? 0) <=> ($b['sequence'] ?? 0));
        $out['stops'] = array_map(function ($s) {
            $row = ['kind' => $s['kind']];
            foreach (['customer_id', 'technical_action_id', 'sequence', 'planned_sequence'] as $key) $row[$key] = empty($s[$key]) ? ($key === 'planned_sequence' ? (int) $s['sequence'] : null) : (int) $s[$key];
            $row['customer_visit_index'] = $s['kind'] === 'customer' ? (int) ($s['customer_visit_index'] ?? 1) : null;
            foreach (['description', 'address_text'] as $key) $row[$key] = $s[$key] ?? '';
            foreach (['location_lat', 'location_lng'] as $key) $row[$key] = isset($s[$key]) ? round((float) $s[$key], 7) : null;
            $row['orders'] = array_map('intval', $s['orders'] ?? []);
            sort($row['orders']);
            return $row;
        }, $stops);
        return $out;
    }
}
