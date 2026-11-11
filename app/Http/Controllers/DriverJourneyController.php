<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Services\Driver\DriverExecutionLookups;
use App\Services\Driver\DriverJourneyReadModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DriverJourneyController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return redirect()->route('driver.home', array_filter($validated));
    }

    public function show(Journey $journey, DriverJourneyReadModel $readModel)
    {
        Gate::authorize('view', $journey);

        return inertia('Driver/Journey/Workspace', [
            'journey' => $readModel->load($journey),
            'readOnly' => true,
            'canViewExecutionHistory' => Gate::allows('viewExecutionHistory', $journey),
        ]);
    }

    public function edit(Journey $journey, DriverJourneyReadModel $readModel)
    {
        Gate::authorize('view', $journey);

        return inertia('Driver/Journey/Workspace', [
            'journey' => $readModel->load($journey),
            'readOnly' => ! Gate::allows('update', $journey),
            'canViewExecutionHistory' => Gate::allows('viewExecutionHistory', $journey),
            'warehouseAccess' => Gate::allows('warehouseManage', $journey),
            'executionLookups' => app(DriverExecutionLookups::class)->load($journey),
        ]);
    }
}
