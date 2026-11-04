<?php
namespace App\Jobs;

use App\Models\{Order, JourneyOrderExecution};
use App\Services\OrderDocumentGenerationService;
use App\Services\JourneyExecution\ExecutionProjection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateOrderDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public ?int $executionOrderId = null;
    public ?string $expectedHash = null;
    public function __construct(public int $orderId, ?int $executionOrderId = null, ?string $expectedHash = null)
    {
        $this->executionOrderId = $executionOrderId;
        $this->expectedHash = $expectedHash;
    }
    public function handle(OrderDocumentGenerationService $service): void
    {
        $order = $this->executionOrderId
            ? app(ExecutionProjection::class)->order(JourneyOrderExecution::findOrFail($this->executionOrderId))
            : Order::find($this->orderId);
        if (!$order) return;
        try { $service->generate($order, $this->expectedHash); }
        catch (Throwable $e) { $service->markFailed($order, $e->getMessage(), $this->expectedHash); throw $e; }
    }
}
