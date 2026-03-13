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
            // Accept both camelCase (SPA) and snake_case (legacy/manual URL).
            'rischioBasso' => $request->boolean('rischioBasso', $request->boolean('rischio_basso', false)),
            'rischioMedio' => $request->boolean('rischioMedio', $request->boolean('rischio_medio', false)),
            'rischioAlto' => $request->boolean('rischioAlto', $request->boolean('rischio_alto', true)),
            'rischioCritico' => $request->boolean('rischioCritico', $request->boolean('rischio_critico', true)),

            ...$request->only(['chiave']) // ... is like "merge array"
        ];

        return inertia(
            'Map/Index',
            [
                'filters' => $filters,
                'sites' => Site::query()
                    ->with([
                        'customer' => fn ($query) => $query->withTrashed()->with('seller'),
                        'timetable',
                    ])
                    ->filter($filters)
                    ->get()
                    ->map(function (Site $site) {
                        $siteData = $site->toArray();
                        $siteData['customer'] = [
                            'id' => $site->customer?->id,
                            'company_name' => $site->customer?->company_name ?? '[Cliente non disponibile]',
                            'seller' => [
                                'name' => $site->customer?->seller?->name ?? 'N/D',
                            ],
                        ];

                        return $siteData;
                    }),
            ]);
    }
}



