<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RelatorListingAcceptOfferController extends Controller
{
    use AuthorizesRequests;
    
    public function __invoke(Offer $offer)
    {

        $listing = $offer->listing;

        $this->authorize('update', $listing);

        // Accept selected Offer
        $offer->update(['accepted_at' => now()]);

        // modify the listing inserting the date of offer acceptance
        $listing->sold_at = now();
        $listing->save();


        // Reject all others offers
        $listing->offers()->except($offer)
        ->update(['rejected_at' => now()]);
    
        return redirect()->back()->with('succes', "Offer #{$offer->id} accepted, others rejected");
    }
}
