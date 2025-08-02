<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RelatorListingImageController extends Controller
{
    public function create(Listing $listing) {
        $listing->load(['images']);
        return inertia(
            'Relator/ListingImages/Create',
            ['listing' => $listing]
        );
    }

    public function store(Listing $listing, Request $request) {
        if($request->hasFile('images')){
            $request->validate([
                'images.*' => 'mimes:png,jpg,jpeg|max:5000',
            ], [
                'images.*mimes' => 'The file should be in one of the formats: png, jpg, jpeg'
            ]);
            // Validate stops the request and return the error message automatically if validate rules ar broken
            foreach($request->file('images') as $file){
                $path = $file->store('images', 'public'); // images folder into the public disk

                $listing->images()->save(new ListingImage([
                    'filename' => $path
                ]));
            }
        }

        return redirect()->back()->with('success', 'Image(s) uploaded succesfully');
    }

    //public function destroy(Listing $listing, ListingImage $image){
    // In questo caso togliamo l'hint Listing sul modello in quanto non serve caricare l'intero listing dal databse, ma il suo ID viene passato come parametro
    public function destroy($listing, ListingImage $image){
        Storage::disk('public')->delete($image->filename);
        $image->delete();
        return redirect()->back()->with('success', 'Image was deleted');
    }
}
