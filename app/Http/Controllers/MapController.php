<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapController extends Controller
{
    public function index(Request $request){
        $filters = [
            'deleted' => $request->boolean('deleted'),
            'continuativo' => $request->boolean('continuativo', true), // SET the default value to TRUE if is not initialized (otherwise it reads it as a false)
            'occasionale' => $request->boolean('occasionale'),
            'rischioBasso' => $request->boolean('rischioBasso', false),
            'rischioMedio' => $request->boolean('rischioMedio', false),
            'rischioAlto' => $request->boolean('rischioAlto', true),
            'rischioCritico' => $request->boolean('rischioCritico', true),

            ...$request->only(['chiave']) // ... is like "merge array"
        ];

        return inertia(
            'Map/Index',
            [
                'filters' => $filters,
                'sites' => Site::query()
                    ->with(['owner', 'owner.seller', 'timetable']) 
                    ->filter($filters)
                    ->get(),
            ]);
    }
}
