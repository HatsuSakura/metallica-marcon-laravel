<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\CalculateRiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class API_SiteBooleanUpdateController extends Controller
{
    public function update(Request $request, Site $site) {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'is_main' => 'nullable|boolean',
            'name' => 'nullable|string',
            //'preferred_area' => 'nullable|number',
            'has_muletto' => 'nullable|boolean',
            'has_electric_pallet_truck' => 'nullable|boolean',
            'has_manual_pallet_truck' => 'nullable|boolean',
            'other_machines' => 'nullable|string',
            'has_adr_consultant' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);
    
        $site->update(
            $validated
        );

        // Check if full data is requested
        if ($request->query('full', false)) {
            $site->load(['customer', 'orders', 'timetable', 'internal_contacts']);
        }
    
        return response()->json(['message' => 'Site saved successfully.', 'site' => $site], 200);
    }

    public function recalculateRisk(Site $site, CalculateRiskService $calculateRiskService)
    {
        Gate::authorize('update', $site);

        $updatedSite = $calculateRiskService->recalculateSiteRisk([
            'siteId' => (int) $site->id,
        ]);

        return response()->json([
            'message' => 'Site risk recalculated successfully.',
            'site' => [
                'id' => $updatedSite->id,
                'calculated_risk_factor' => $updatedSite->calculated_risk_factor,
                'days_until_next_withdraw' => $updatedSite->days_until_next_withdraw,
            ],
        ], 200);
    }
}


