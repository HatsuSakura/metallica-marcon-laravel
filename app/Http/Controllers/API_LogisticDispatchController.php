<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Services\Dispatch\JourneyDispatchPlanService;
use App\Services\Dispatch\JourneyDispatchStatusService;
use Illuminate\Http\Request;

class API_LogisticDispatchController extends Controller
{
    public function __construct(
        private JourneyDispatchPlanService $dispatchPlanService,
        private JourneyDispatchStatusService $dispatchStatusService
    ) {
    }

    public function updatePlan(Request $request, Journey $journey)
    {
        $this->authorize('dispatchWorkspaceSave', $journey);

        $validated = $request->validate([
            'is_double_load' => ['required', 'boolean'],
            'is_temporary_storage' => ['required', 'boolean'],
            'primary_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'secondary_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'primary_warehouse_download_at' => ['nullable', 'date'],
            'secondary_warehouse_download_at' => ['nullable', 'date'],
        ]);

        if (
            !empty($validated['primary_warehouse_id']) &&
            !empty($validated['secondary_warehouse_id']) &&
            (int) $validated['primary_warehouse_id'] === (int) $validated['secondary_warehouse_id']
        ) {
            return response()->json([
                'type' => 'error',
                'message' => 'Magazzino primario e secondario devono essere diversi.',
            ], 422);
        }

        if (!empty($validated['secondary_warehouse_id']) && empty($validated['is_double_load'])) {
            return response()->json([
                'type' => 'error',
                'message' => 'Il secondo magazzino richiede la modalita doppio scarico.',
            ], 422);
        }

        $journey = $this->dispatchPlanService->setPlan(
            $journey,
            $validated,
            $request->user()?->id
        );

        return response()->json([
            'type' => 'success',
            'journey' => $journey,
        ]);
    }

    public function hold(Request $request, Journey $journey)
    {
        $this->authorize('dispatchWorkspaceSave', $journey);

        $dispatchStatus = $this->dispatchStatusService->resolveCurrentStatus($journey);
        if ($dispatchStatus === JourneyDispatchStatusService::STATUS_ON_HOLD) {
            return response()->json([
                'type' => 'warning',
                'message' => 'Il dispatch Ã¨ giÃ  in attesa.',
            ], 422);
        }
        if ($dispatchStatus === JourneyDispatchStatusService::STATUS_MANAGED) {
            return response()->json([
                'type' => 'warning',
                'message' => 'Il dispatch è già gestito.',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        $this->dispatchPlanService->addOperationalEvent(
            $journey,
            'dispatch_hold',
            $validated['notes'],
            $request->user()?->id
        );

        return response()->json([
            'type' => 'success',
            'dispatch_status' => JourneyDispatchStatusService::STATUS_ON_HOLD,
        ]);
    }

    public function resume(Request $request, Journey $journey)
    {
        $this->authorize('dispatchWorkspaceSave', $journey);

        $dispatchStatus = $this->dispatchStatusService->resolveCurrentStatus($journey);
        if ($dispatchStatus !== JourneyDispatchStatusService::STATUS_ON_HOLD) {
            return response()->json([
                'type' => 'warning',
                'message' => 'Riprendi Ã¨ disponibile solo quando il dispatch Ã¨ in attesa.',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->dispatchPlanService->addOperationalEvent(
            $journey,
            'dispatch_resume',
            $validated['notes'] ?? null,
            $request->user()?->id
        );

        return response()->json([
            'type' => 'success',
            'dispatch_status' => JourneyDispatchStatusService::STATUS_IN_PROGRESS,
        ]);
    }
}
