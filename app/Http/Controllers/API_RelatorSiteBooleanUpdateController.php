<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class API_RelatorSiteBooleanUpdateController extends Controller
{
    public function update(Request $request, Site $site) {
        $validated = $request->validate([
            'is_main' => 'nullable|boolean',
            'denominazione' => 'nullable|string',
            //'preferred_area' => 'nullable|number',
            'has_muletto' => 'nullable|boolean',
            'has_transpallet_el' => 'nullable|boolean',
            'has_transpallet_ma' => 'nullable|boolean',
            'other_machines' => 'nullable|string',
            'has_adr_consultant' => 'nullable|boolean',
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
