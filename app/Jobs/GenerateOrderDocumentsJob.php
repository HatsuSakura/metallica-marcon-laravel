<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderDocumentGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateOrderDocumentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(OrderDocumentGenerationService $service): void
    {
        $order = Order::query()->find($this->orderId);
        if (!$order) {
            return;
        }

        try {
            $service->generate($order);
        } catch (Throwable $e) {
            $service->markFailed($order, $e->getMessage());
            throw $e;
        }
    }
}

