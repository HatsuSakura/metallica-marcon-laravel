<?php

namespace App\Support\AgentApi;

use App\Models\Journey;
use App\Models\Order;
use App\Services\JourneyPlanning\PlanningInput;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Representation
{
    public const ORDER_FIELDS = ['id', 'legacy_code', 'status', 'customer_id', 'site_id', 'journey_id', 'cargo_location', 'logistics_user_id', 'is_urgent', 'requested_at', 'expected_withdraw_at', 'fixed_withdraw_at', 'notes', 'updated_at'];

    public const ITEM_FIELDS = ['id', 'cer_code_id', 'order_item_group_id', 'order_item_group_label', 'holder_id', 'holder_quantity', 'is_bulk', 'description', 'weight_declared', 'custom_l_cm', 'custom_w_cm', 'custom_h_cm', 'adr', 'adr_hp', 'adr_un_code', 'adr_lot_code', 'adr_volume', 'warehouse_id', 'is_adr_total', 'has_adr_total_exemption', 'has_adr_partial_exemption'];

    public const HOLDER_FIELDS = ['id', 'holder_id', 'filled_holders_count', 'empty_holders_count', 'total_holders_count'];

    public function order(Order $order): array
    {
        $order->refresh()->load(['items', 'holders']);
        $data = Arr::only($order->attributesToArray(), self::ORDER_FIELDS);
        $data['items'] = $order->items->map(fn ($item) => Arr::only($item->attributesToArray(), self::ITEM_FIELDS))->all();
        $data['holders'] = $order->holders->map(fn ($holder) => Arr::only($holder->attributesToArray(), self::HOLDER_FIELDS))->all();

        return $this->version($data);
    }

    public function journey(Journey $journey): array
    {
        $journey->refresh();
        $data = Arr::only($journey->attributesToArray(), ['id', 'status', 'plan_version', 'planned_start_at', 'planned_end_at', 'actual_start_at', 'actual_end_at', 'vehicle_id', 'vehicle_cargo_id', 'trailer_id', 'trailer_cargo_id', 'driver_id', 'logistics_user_id', 'notes', 'updated_at']);
        $data += app(PlanningInput::class)->current($journey);

        return $this->version($data);
    }

    private function version(array $data): array
    {
        return $data + ['version' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))];
    }

    public function checkVersion(Request $request, array $current): void
    {
        abort_unless($request->hasHeader('If-Match'), 428, 'If-Match obbligatorio: usa la versione letta con GET.');
        abort_unless(hash_equals($current['version'], trim($request->header('If-Match'), '"')), 409, 'La risorsa è cambiata: rileggila prima di applicare modifiche.');
    }
}
