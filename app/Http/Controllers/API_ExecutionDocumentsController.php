<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Models\JourneyOrderExecution;
use App\Services\JourneyExecution\ExecutionProjection;
use App\Services\OrderDocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class API_ExecutionDocumentsController extends Controller
{
    public function documents(Request $request, Journey $journey, JourneyOrderExecution $executionOrder, OrderDocumentGenerationService $service)
    {
        Gate::authorize($request->isMethod('POST') ? 'update' : 'view', $journey);
        abort_unless((int) $executionOrder->journey_id === (int) $journey->id, 404);
        $order = app(ExecutionProjection::class)->order($executionOrder, $journey);
        $queued = $request->isMethod('POST') ? $service->enqueueGeneration($order) : false;
        $order = app(ExecutionProjection::class)->order($executionOrder->fresh(), $journey);

        return response()->json(['queued' => $queued, 'data' => $service->statusPayload($order), 'documents' => $service->listDocuments($order)]);
    }

    public function download(Request $request, Journey $journey, JourneyOrderExecution $executionOrder, string $document, OrderDocumentGenerationService $service)
    {
        Gate::authorize('view', $journey);
        abort_unless((int) $executionOrder->journey_id === (int) $journey->id, 404);
        $order = app(ExecutionProjection::class)->order($executionOrder, $journey);
        $path = $service->findDocumentPathByName($order, rawurldecode($document));
        abort_unless($path, 404);

        return response()->download(Storage::disk('local')->path($path), basename($path));
    }
}
