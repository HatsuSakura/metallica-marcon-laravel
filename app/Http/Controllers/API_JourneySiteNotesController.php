<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use App\Models\Site;
use App\Services\Driver\DriverJourneyReadModel;
use App\Services\JourneyExecution\JourneySiteNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class API_JourneySiteNotesController extends Controller
{
    public function update(Request $request, Journey $journey, Site $site, JourneySiteNotes $service)
    {
        Gate::authorize('updateSiteNotes', $journey);
        $input = $request->validate([
            'notes' => ['present', 'nullable', 'string', 'max:5000'],
            'notes_version' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'name' => ['missing'], 'address' => ['missing'], 'customer_id' => ['missing'],
        ]);
        $service->update($journey, $site, $request->user()->id, $input['notes'], $input['notes_version']);

        return response()->json(['journey' => app(DriverJourneyReadModel::class)->load($journey->fresh())]);
    }
}
