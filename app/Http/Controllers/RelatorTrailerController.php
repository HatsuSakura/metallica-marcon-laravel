<?php

namespace App\Http\Controllers;

use App\Models\Trailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class RelatorTrailerController extends Controller
{

    public function __consruct(){
        $this->authorizeResource(Trailer::class, 'trailer');
    }

    public function index(Request $request){
        $filters = [
            'deleted' => $request->boolean('deleted'),
            ...$request->only(['by', 'order']) // ... is like "merge array"
        ];

        return inertia(
            'Relator/Trailer/Index',
            [
                'filters' => $filters,
                'trailers' => Trailer::query()
                //->alphabetic()
                //->withCount('sites')
                //->filter($filters)
                ->paginate(25)
                ->withQueryString()
            ]);
    }

    public function show(Trailer $trailer){
        return inertia(
            'Relator/Trailer/Show',
            ['trailer' => $trailer->load('offers', 'offers.bidder')],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Trailer::class);
        return inertia('Relator/Trailer/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'plate' => 'required',
            'is_front_cargo' => 'required|boolean',
            'load_capacity' => 'required|integer|min:0|max:50000',
        ]);
        
        Trailer::create(
            $validated    
        );

        return redirect()->route('relator.trailer.index')->with('success', 'Rimorchio creato con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trailer $trailer)
    {
        Gate::authorize('update', $trailer);
        return inertia(
            'Relator/Trailer/Edit',
            [
                'trailer' => $trailer
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trailer $trailer)
    {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'plate' => 'required',
            'is_front_cargo' => 'required|boolean',
            'load_capacity' => 'required|integer|min:0|max:50000',
        ]);

        $trailer->update(
            $validated
        );

        return redirect()->route('relator.trailer.index')->with('success', 'Rimorchio modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trailer $trailer)
    {
        Gate::authorize('delete', $trailer);
        $trailer->deleteOrFail();

        return redirect()->back()->with('success', 'Rimorchio cancellato');
    }

    public function restore(Trailer $trailer){
        $trailer->restore();
        return redirect()->back()->with('success', 'Rimorchio ripristinato');
    }

}
