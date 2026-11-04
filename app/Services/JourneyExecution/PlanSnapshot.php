<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class PlanSnapshot
{
    public const ORDER_RELATIONS = [
        'customer.seller:id,name,surname,user_code', 'site.timetable',
        'site.internalContacts:id,site_id,name,surname,mobile,phone',
        'logisticsUser:id,name,surname,user_code', 'items.cerCode', 'items.holder',
        'items.orderItemGroup', 'holders.holder',
    ];

    public function capture(Journey $journey, bool $validate = true): array
    {
        $journey->load(['vehicle', 'trailer', 'driver:id,name,surname',
            'stops' => fn ($q) => $q->orderBy('sequence')->orderBy('id'),
            'stops.customer', 'stops.technicalAction', 'stops.stopOrders.order', 'journeyCargos.cargo', 'cargoAllocations']);
        $orders = [];
        $stops = [];
        foreach ($journey->stops as $stop) {
            $links = [];
            foreach ($stop->stopOrders as $link) {
                abort_unless($link->order && (int) $link->order->journey_id === (int) $journey->id, 422, 'Ordine/tappa non coerenti con il viaggio.');
                $orders[$link->order_id] ??= $this->order($link->order);
                $links[] = (int) $link->order_id;
            }
            $stops[] = $this->plain($stop) + ['orders' => $links, 'customer' => $this->plain($stop->customer), 'technical_action' => $this->plain($stop->technicalAction)];
        }
        $attached = $journey->orders()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $linked = collect($stops)->flatMap(fn ($s) => $s['orders'])->all();
        if ($validate) {
            abort_unless(count($linked) === count(array_unique($linked)) && collect($linked)->sort()->values()->all() === $attached, 422, 'Ogni ordine deve appartenere a una sola tappa.');
        }

        return ['schema_version' => 1, 'journey' => $this->plain($journey), 'vehicle' => $this->plain($journey->vehicle),
            'trailer' => $this->plain($journey->trailer), 'driver' => $this->plain($journey->driver), 'stops' => $stops, 'orders' => array_values($orders),
            'cargos' => $journey->journeyCargos->map(fn ($c) => $this->plain($c) + ['cargo' => $this->plain($c->cargo)])->all(),
            'allocations' => $journey->cargoAllocations->map(fn ($a) => $this->plain($a))->all(),
            'cargo_items' => \Illuminate\Support\Facades\DB::table('journey_cargo_order_item')->whereIn('journey_cargo_id', $journey->journeyCargos->pluck('id'))->orderBy('id')->get()->map(fn ($p) => (array) $p)->all()];
    }

    public function order(Order $order): array
    {
        $order->load(self::ORDER_RELATIONS);

        return $this->plain($order) + [
            'customer' => $this->plain($order->customer) + ['seller' => $this->plain($order->customer?->seller)],
            'site' => $this->plain($order->site) + ['timetable' => $this->plain($order->site?->timetable),
                'internal_contacts' => $order->site?->internalContacts->map(fn ($c) => $this->plain($c))->all() ?? []],
            'logistics_user' => $this->plain($order->logisticsUser),
            'items' => $order->items->map(fn ($i) => $this->plain($i) + ['cer_code' => $this->plain($i->cerCode), 'holder' => $this->plain($i->holder), 'order_item_group' => $this->plain($i->orderItemGroup)])->all(),
            'holders' => $order->holders->map(fn ($h) => $this->plain($h) + ['holder' => $this->plain($h->holder)])->all(),
        ];
    }

    public function checksum(array $snapshot): string
    {
        $canonical = function (array $data) use (&$canonical): array {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = $canonical($value);
                }
            }
            if (! array_is_list($data)) {
                ksort($data);
            }

            return $data;
        };

        return hash('sha256', json_encode($canonical($snapshot), JSON_THROW_ON_ERROR));
    }

    private function plain(?Model $model): array
    {
        return $model ? array_diff_key($model->getAttributes(), array_flip(['password', 'remember_token'])) : [];
    }
}
