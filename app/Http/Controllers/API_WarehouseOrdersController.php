<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Services\OrderItemUpdater;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;


class API_WarehouseOrdersController extends Controller{


    // app/Http/Controllers/WarehouseOrderApiController.php
public function update(Request $request, Order $order, OrderItemUpdater $updater)
{
    $request->validate([
      'has_ragno'     => 'required|boolean',
      'ragnista_id'   => 'nullable|exists:users,id',
      'machinery_time'=> 'required|integer|min:0',
      'items'         => 'array',
      'items.*.id'    => 'required|exists:order_items,id',
      'items.*.updated_at' => 'nullable|date_format:Y-m-d\TH:i:s.u\Z',
      'items.*.images'     => 'array',
      'items.*.images.*'   => 'file|image|max:5120',
    ]);

    // at the top of your method, before the transaction:
    $conflicts = [];
    $saved     = [];

    DB::transaction(function() use($request, $order, $updater, &$conflicts, &$saved) {
      // 1) aggiorno l'ordine
      $order->update($request->only(['has_ragno','ragnista_id','machinery_time']));

      // 2) process items
      foreach ($request->input('items', []) as $i => $data) {
        $item = OrderItem::findOrFail($data['id']);
        // prendo anche i file
        $images = $request->file("items.$i.images", []);
        $result = $updater->safeUpdate(
          $item,
          array_merge($data, ['images' => $images]),
          $request->user()->id
        );
        if ($result['status']==='conflict') {
          $conflicts[] = $result['conflict'];
        } else {
          $saved[] = $result['item'];
        }
      }

      if ($conflicts) {
        // forzo rollback
        throw new \Illuminate\Validation\ValidationException(
          Validator::make([],[]),
          response()->json(['conflicts'=>$conflicts], 409)
        );
      }
    });

    // 3) riload completo dell’ordine coi suoi items+images
    $order->load('items.images', 'items.cerCode', 'items.holder', 'items.warehouse');

    // turn your $saved array into a collection of IDs
    $savedIds = collect($saved)->pluck('id')->toArray();

    return response()->json([
        'order'      => $order,
        // pick only those items whose id is in $savedIds
        'savedItems' => collect($order->items)
                          ->whereIn('id', $savedIds)
                          ->values(),  // re-index
    ]);
}



}