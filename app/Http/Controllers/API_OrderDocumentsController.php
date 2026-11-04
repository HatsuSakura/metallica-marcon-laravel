<?php

namespace App\Http\Controllers;

use App\Enums\OrderDocumentsStatus;
use App\Models\Order;
use App\Services\OrderDocumentGenerationService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class API_OrderDocumentsController extends Controller
{
    public function generate(Request $request, Order $order, OrderDocumentGenerationService $service): JsonResponse
    {
        $order = $this->subject($request, $order, true);

        $documentsState = OrderDocumentsStatus::fromMixed($order->documents_status ?? OrderDocumentsStatus::NOT_GENERATED->value);

        if ($documentsState === OrderDocumentsStatus::GENERATING) {
            if ($service->isGeneratingStateStale($order)) {
                $service->recoverStaleGeneratingState($order);
            } else {
                return response()->json([
                    'type' => 'warning',
                    'message' => 'Generazione documenti già in corso per questo ordine.',
                    'data' => $service->statusPayload($order),
                ], 409);
            }
        }

        $queued = $service->enqueueGeneration($order);
        $order = $this->subject($request, Order::findOrFail($order->id), true);

        return response()->json([
            'type' => 'success',
            'message' => $queued ? 'Generazione documenti avviata correttamente.' : 'Documenti già aggiornati: usa il download.',
            'data' => $service->statusPayload($order),
        ], $queued ? 202 : 200);
    }

    public function status(Request $request, Order $order, OrderDocumentGenerationService $service): JsonResponse
    {
        $order = $this->subject($request, $order, false);

        return response()->json([
            'type' => 'info',
            'data' => $service->statusPayload($order),
        ]);
    }

    public function list(Request $request, Order $order, OrderDocumentGenerationService $service): JsonResponse
    {
        $order = $this->subject($request, $order, false);

        return response()->json([
            'type' => 'info',
            'data' => $service->listDocuments($order),
        ]);
    }

    public function download(
        Request $request,
        Order $order,
        string $document,
        OrderDocumentGenerationService $service
    ): BinaryFileResponse {
        $order = $this->subject($request, $order, false);

        $decodedFileName = rawurldecode($document);
        $filePath = $service->findDocumentPathByName($order, $decodedFileName);
        abort_if($filePath === null, 404, 'Documento non trovato.');

        return response()->download(
            Storage::disk('local')->path($filePath),
            basename($filePath)
        );
    }
    private function subject(Request $request, Order $order, bool $write): Order
    {
        $id = $request->integer('journey_id') ?: $order->journey_id;
        $journey = $id ? \App\Models\Journey::findOrFail($id) : null;
        if ($journey && $journey->baseline()->exists()) Gate::authorize($write ? 'update' : 'view', $journey);
        else Gate::authorize($write ? 'update' : 'view', $order);
        return app(\App\Services\JourneyExecution\DocumentRevision::class)->resolve($order, $id);
    }
}

