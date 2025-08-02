<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class RelatorListingController extends Controller
{

    public function __consruct(){
        $this->authorizeResource(Listing::class, 'listing');
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
                'listings' => Auth::user()
                    ->listings()
                    //->mostRecent() // managed by default 'by'
                    ->withCount('images')
                    ->withCount('offers')
                    ->filter($filters)
                    ->paginate(5)
                    ->withQueryString()
            ]);
    }

    public function show(Listing $listing){
        return inertia(
            'Relator/Show',
            ['listing' => $listing->load('offers', 'offers.bidder')],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Listing::class);
        return inertia('Relator/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // sostistuisco "//Listing::create([" con questa nuova riga per generare direttamente il LISTING associato all'utente che lo crea
        $request->user()->listings()->create(
        //Listing::create(
            $request->validate([
                'beds' => 'required|integer|min:0|max:20',
                'baths' => 'required|integer|min:0|max:20',
                'area' => 'required|integer|min:15|max:1500',
                'city' => 'required',
                'code' => 'required',
                'street' => 'required',
                'street_nr' => 'required|integer|min:1|max:1000',
                'price' => 'required|integer|min:1000|max:10000000',
            ])
        );

        return redirect()->route('relator.listing.index')->with('success', 'Listing was created!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Listing $listing)
    {
        Gate::authorize('update', $listing);
        return inertia(
            'Relator/Edit',
            [
                'listing' => $listing
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Listing $listing)
    {
        $listing->update([
            $request->validate([
                'beds' => 'required|integer|min:0|max:20',
                'baths' => 'required|integer|min:0|max:20',
                'area' => 'required|integer|min:15|max:1500',
                'city' => 'required',
                'code' => 'required',
                'street' => 'required',
                'street_nr' => 'required|integer|min:1|max:1000',
                'price' => 'required|integer|min:1000|max:10000000',
                
            ])
        ]);

        return redirect()->route('relator.listing.index')->with('success', 'Listing was successfully modified!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Listing $listing)
    {
        Gate::authorize('delete', $listing);
        $listing->deleteOrFail();

        return redirect()->back()->with('success', 'Listing was deleted');
    }

    public function restore(Listing $listing){
        $listing->restore();
        return redirect()->back()->with('success', 'Listing was restored');
    }

}
