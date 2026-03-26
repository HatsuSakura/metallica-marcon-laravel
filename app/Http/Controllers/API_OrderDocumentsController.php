<?php

namespace App\Http\Controllers;

use App\Enums\OrderDocumentsStatus;
use App\Models\Order;
use App\Services\OrderDocumentGenerationService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class API_OrderDocumentsController extends Controller
{
    public function generate(Request $request, Order $order, OrderDocumentGenerationService $service): JsonResponse
    {
        Gate::authorize('update', $order);

        $documentsState = OrderDocumentsStatus::fromMixed($order->documents_status ?? OrderDocumentsStatus::NOT_GENERATED->value);

        if ($documentsState === OrderDocumentsStatus::GENERATING) {
            if ($service->isGeneratingStateStale($order)) {
                $service->recoverStaleGeneratingState($order);
                $order->refresh();
            } else {
                return response()->json([
                    'type' => 'warning',
                    'message' => 'Generazione documenti già in corso per questo ordine.',
                    'data' => $service->statusPayload($order),
                ], 409);
            }
        }

        $service->enqueueGeneration($order);
        $order->refresh();

        return response()->json([
            'type' => 'success',
            'message' => 'Generazione documenti avviata correttamente.',
            'data' => $service->statusPayload($order),
        ], 202);
    }

    public function status(Request $request, Order $order, OrderDocumentGenerationService $service): JsonResponse
    {
        Gate::authorize('view', $order);

        return response()->json([
            'type' => 'info',
            'data' => $service->statusPayload($order),
        ]);
    }

    public function list(Request $request, Order $order, OrderDocumentGenerationService $service): JsonResponse
    {
        Gate::authorize('view', $order);

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
        Gate::authorize('view', $order);

        $decodedFileName = rawurldecode($document);
        $filePath = $service->findDocumentPathByName($order, $decodedFileName);
        abort_if($filePath === null, 404, 'Documento non trovato.');

        return response()->download(
            storage_path('app/private/' . $filePath),
            basename($filePath)
        );
    }
}


