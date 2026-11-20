<?php

namespace App\Services\Planning;

use App\Enums\JourneyStatus;
use App\Models\Journey;
use App\Models\Order;

class PlanningGuard
{
    public function order(Order $order): void
    {
        if ($order->journey_id) {
            $this->journey(Journey::whereKey($order->journey_id)->lockForUpdate()->firstOrFail());
        }
    }

    public function journey(Journey $journey): void
    {
        abort_if($journey->baseline()->exists() || $journey->status !== JourneyStatus::STATUS_CREATED, 409, 'Viaggio partito o concluso: il piano originale non è modificabile.');
    }
}
