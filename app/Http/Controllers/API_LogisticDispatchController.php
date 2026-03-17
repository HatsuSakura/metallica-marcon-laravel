<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Services\Dispatch\JourneyDispatchPlanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class API_LogisticDispatchController extends Controller
{
    public function __construct(
        private JourneyDispatchPlanService $dispatchPlanService
    ) {
    }

    public function updatePlan(Request $request, Journey $journey)
    {
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
        ]);
    }

    public function resume(Request $request, Journey $journey)
    {
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
        ]);
    }

    public function complete(Request $request, Journey $journey)
    {
        $validated = $request->validate([
            'completion_code' => ['required', Rule::in(['single_load_done', 'double_load_done', 'temporary_storage_done'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->dispatchPlanService->addOperationalEvent(
            $journey,
            (string) $validated['completion_code'],
            $validated['notes'] ?? null,
            $request->user()?->id
        );

        return response()->json([
            'type' => 'success',
        ]);
    }
}
