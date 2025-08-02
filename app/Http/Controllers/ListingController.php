<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ListingController extends Controller
{
    
    public function __consruct(){
        $this->authorizeResource(Listing::class, 'listing');
    }
    

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Listing::class);

        $filters = $request->only([
            'priceFrom', 'priceTo', 'beds', 'baths', 'areaFrom', 'areaTo'
        ]);

        return inertia(
            'Listing/Index',
            [
                'filters' => $filters,
                //'listings' => Listing::all()
                'listings' => Listing::mostRecent()
                ->filter($filters)
                ->withoutSold()
                ->paginate(10)
                ->withQueryString()
            ]
        );
    }



    /**
     * Display the specified resource.
     */
    public function show(Listing $listing)
    {
        Gate::authorize('view', $listing);

        $listing->load(['images']);
        $offerMade = !Auth::user()? null : $listing->offers()->byMe()->first(); // se Auth::user() è NULL, non consuma risorsa ad eseguire una query che darà vuoto

        return inertia(
            'Listing/Show',
            [
                'listing' => $listing,
                'offerMade' => $offerMade
            ]
        );
    }


}
