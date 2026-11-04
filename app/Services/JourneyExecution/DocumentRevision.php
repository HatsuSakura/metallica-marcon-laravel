<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\JourneyOrderExecution;
use App\Models\Order;

class DocumentRevision
{
    public function resolve(Order $source, ?int $journeyId = null): Order
    {
        $id = $journeyId ?? $source->journey_id;
        if ($id && ($journey = Journey::find($id)) && $journey->baseline()->exists()) {
            $e = $journey->orderExecutions()->where('source_order_id', $source->id)->firstOrFail();

            return app(ExecutionProjection::class)->order($e, $journey);
        }
        $source->loadMissing(PlanSnapshot::ORDER_RELATIONS);
        $source->loadMissing(['journey.vehicle', 'journey.trailer', 'journey.driver']);

        return $source;
    }

    public function hash(Order $order): string
    {
        if ($order->execution_order_id) {
            $j = Journey::findOrFail($order->journey_id);
            $data = ['execution_order_id' => $order->execution_order_id, 'state' => app(JourneyExecutionService::class)->state($j)];
        } else {
            $order = Order::findOrFail($order->id);
            $data = ['order' => app(PlanSnapshot::class)->order($order)];
            if ($order->journey_id) {
                $data['journey'] = app(PlanSnapshot::class)->capture(Journey::findOrFail($order->journey_id), false);
            }
        }

        return hash('sha256', json_encode($this->canonical($data), JSON_THROW_ON_ERROR));
    }

    private function canonical(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && (str_starts_with($key, 'documents_') || in_array($key, ['created_at', 'updated_at', 'deleted_at']))) {
                continue;
            }
            $result[$key] = is_array($value) ? $this->canonical($value) : $value;
        }
        if (! array_is_list($result)) {
            ksort($result);
        }

        return $result;
    }

    public function persist(Order $order, array $values): void
    {
        if ($order->execution_order_id) {
            JourneyOrderExecution::findOrFail($order->execution_order_id)->forceFill($values)->save();
            $order->forceFill($values);
        } else {
            $order->forceFill($values)->save();
        }
    }
}
