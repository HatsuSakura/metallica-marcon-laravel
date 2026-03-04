<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;

class API_SiteTimetableController extends Controller
{
    public function store(Request $request, Site $site) {
        $validated = $request->validate([
            'timetable_data' => 'required|array',
        ]);
    
        $site->timetable()->updateOrCreate(
            ['site_id' => $site->id], // Condition to check if timetable exists for the site
            ['hours_json' => json_encode($validated['timetable_data'])] // Fields to update or create
        );
    
        return response()->json(['message' => 'Timetable saved successfully.'], 200);
    }
}




