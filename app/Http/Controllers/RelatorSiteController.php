<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class RelatorSiteController extends Controller
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
            'Relator/Index',
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
            'Relator/Show',
            ['site' => $site->load('orders', 'customer')],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Site::class);
        return inertia('Relator/Create');
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

        return redirect()->route('relator.site.index')->with('success', 'Site was created!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        Gate::authorize('update', $site);
        return inertia(
            'Relator/Edit',
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
        $site->update([
            $request->validate([
                'customer_id' => 'nullable|number',
                'denominazione' => 'nullable|text',
                'tipologia'  => 'nullable|text',
                'indirizzo'  => 'nullable|text',
                'lat'  => 'nullable|float',
                'lng'  => 'nullable|float',
                'fattore_rischioCalcolato'  => 'nullable|float',
                'giorniProssimoRitiro'  => 'nullable|number',
                'has_muletto' => 'nullable|boolean',
                'has_transpallet_el' => 'nullable|boolean',
                'has_transpallet_ma' => 'nullable|boolean',
                'other_machines' => 'nullable|text',
                'has_adr_consultant' => 'nullable|boolean'              
            ])
        ]);

        return redirect()->route('relator.site.index')->with('success', 'Site was successfully modified!');
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
