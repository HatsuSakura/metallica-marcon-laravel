<?php

namespace App\Http\Controllers;

//WarehouseManagerOrderItemImageController.php

use App\Models\OrderItem;
use App\Models\OrderItemImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WarehouseManagerOrderItemImageController extends Controller
{
    public function create(OrderItem $orderItem) {
        $orderItem->load(['images']);
        return inertia(
            'Relator/OrderItemImages/Create',
            ['orderItem' => $orderItem]
        );
    }

    public function store(OrderItem $orderItem, Request $request) {
        if($request->hasFile('images')){
            $request->validate([
                'images.*' => 'mimes:png,jpg,jpeg|max:5000',
            ], [
                'images.*mimes' => 'The file should be in one of the formats: png, jpg, jpeg'
            ]);
            // Validate stops the request and return the error message automatically if validate rules ar broken
            foreach($request->file('images') as $file){
                $path = $file->store('images', 'public'); // images folder into the public disk

                $orderItem->images()->save(new OrderItemImage([
                    'filename' => $path
                ]));
            }
        }

        return redirect()->back()->with('success', 'Image(s) uploaded succesfully');
    }



    public function destroy(OrderItem $orderItem, OrderItemImage $image, Request $request)
    {
        abort_unless($image->order_item_id === $orderItem->id, 404);

        $disk = $image->disk ?: 'public';
        $path = $image->path
            ?? $image->filename
            ?? (isset($image->url) ? ltrim(str_replace('/storage/', '', $image->url), '/') : null);

        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
        $image->delete();

        if ($request->wantsJson()) {
            // Ritorna l’elenco aggiornato
            $images = $orderItem->images()
                ->select('id','disk','path','original_name','mime','size','created_at')
                ->get()
                ->map(fn($img) => [
                    'id'    => $img->id,
                    'url'   => $img->url,           // accessor dal model
                    'name'  => $img->original_name, // opzionale
                    'mime'  => $img->mime,
                    'size'  => $img->size,
                ]);

            return response()->json([
                'deleted' => true,
                'images'  => $images,
                'item_id' => $orderItem->id,
            ]);
        }

        return back()->with('success', 'Image was deleted');
    }
}
