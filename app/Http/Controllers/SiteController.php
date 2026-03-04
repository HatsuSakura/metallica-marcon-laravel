<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class SiteController extends Controller
{

    public function __consruct(){
        $this->authorizeResource(Site::class, 'site');
    }

    public function index(Request $request){
        $filters = [
            'deleted' => $request->boolean('deleted'),
            ...$request->only(['by', 'order']) // ... is like "merge array"
        ];

        return inertia(
            'Index',
            [
                'filters' => $filters,
                'sites' => Site::query()
                    ->sites()
                    //->mostRecent() // managed by default 'by'
                    //->withCount('images')
                    //->withCount('offers')
                    //->filter($filters)
                    //->paginate(5)
                    //->withQueryString()
            ]);
    }

    public function show(Site $site){
        return inertia(
            'Show',
            ['site' => $site->load('orders', 'customer')],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Site::class);
        return inertia('Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // sostistuisco "//Site::create([" con questa nuova riga per generare direttamente il LISTING associato all'utente che lo crea
        $request->user()->sites()->create(
        //Site::create(
            $request->validate([
                'beds' => 'required|integer|min:0|max:20',
                'baths' => 'required|integer|min:0|max:20',
            ])
        );

        return redirect()->route('site.index')->with('success', 'Site was created!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        Gate::authorize('update', $site);
        return inertia(
            'Edit',
            [
                'site' => $site
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|numeric',
            'name' => 'nullable|string',
            'site_type'  => 'nullable|string',
            'address'  => 'nullable|string',
            'latitude'  => 'nullable|numeric',
            'longitude'  => 'nullable|numeric',
            'calculated_risk_factor'  => 'nullable|numeric',
            'days_until_next_withdraw'  => 'nullable|numeric',
            'has_muletto' => 'nullable|boolean',
            'has_electric_pallet_truck' => 'nullable|boolean',
            'has_manual_pallet_truck' => 'nullable|boolean',
            'other_machines' => 'nullable|string',
            'has_adr_consultant' => 'nullable|boolean',
        ]);

        $site->update([
            'customer_id' => $validated['customer_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'site_type' => $validated['site_type'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'calculated_risk_factor' => $validated['calculated_risk_factor'] ?? null,
            'days_until_next_withdraw' => $validated['days_until_next_withdraw'] ?? null,
            'has_muletto' => $validated['has_muletto'] ?? null,
            'has_electric_pallet_truck' => $validated['has_electric_pallet_truck'] ?? null,
            'has_manual_pallet_truck' => $validated['has_manual_pallet_truck'] ?? null,
            'other_machines' => $validated['other_machines'] ?? null,
            'has_adr_consultant' => $validated['has_adr_consultant'] ?? null,
        ]);

        return redirect()->route('site.index')->with('success', 'Site was successfully modified!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        Gate::authorize('delete', $site);
        $site->deleteOrFail();

        return redirect()->back()->with('success', 'Site was deleted');
    }

    public function restore(Site $site){
        $site->restore();
        return redirect()->back()->with('success', 'Site was restored');
    }

}




