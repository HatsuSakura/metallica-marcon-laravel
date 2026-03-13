<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class API_SiteBooleanUpdateController extends Controller
{
    public function update(Request $request, Site $site) {
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
}




