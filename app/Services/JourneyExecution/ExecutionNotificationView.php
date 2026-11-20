<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\JourneyOrderExecution;
use App\Models\User;
use App\Notifications\JourneyExecutionUpdated;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;

class ExecutionNotificationView
{
    public function present(DatabaseNotification $notification, User $user): array
    {
        $data = $notification->data;
        $result = ['id' => $notification->id, 'type' => $notification->type, 'created_at' => $notification->created_at,
            'read_at' => $notification->read_at, 'data' => $data, 'journey_url' => null, 'orders' => []];
        if ($notification->type !== JourneyExecutionUpdated::TYPE) {
            return $result;
        }
        $journey = Journey::find($data['journey_id'] ?? null);
        if (! $journey || ! Gate::forUser($user)->allows('view', $journey)) {
            return $result;
        }
        $result['journey_url'] = route('journey.execution', ['journey' => $journey->id]);
        $orders = JourneyOrderExecution::where('journey_id', $journey->id)->whereIn('id', array_column($data['orders'] ?? [], 'execution_order_id'))->get();
        foreach ($orders as $order) {
            $result['orders'][] = ['id' => $order->source_order_id, 'label' => $order->data['legacy_code'] ?? '#'.($order->source_order_id ?? 'E'.$order->id),
                'url' => route('journey.execution', ['journey' => $journey->id, 'stop' => $order->journey_stop_id, 'execution_order' => $order->id])];
        }

        return $result;
    }
}
