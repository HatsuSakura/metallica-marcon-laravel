<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Services\OrderItemUpdater;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;


class API_WarehouseOrderItemsController extends Controller{

public function saveItems(Request $request, OrderItemUpdater $updater)
{
    $conflicts = [];
    $saved = [];

    $userId = $request->user()->id;

    foreach ($request->input('items', []) as $index => $data) {
        $item = OrderItem::findOrFail($data['id']);


        $images = $request->file("items.$index.images", []);

        // safeUpdate con immagini reali
        $result = $updater->safeUpdate($item, array_merge($data, ['images' => $images]), $userId);

        //$result = $updater->safeUpdate($item, $data, $userId);


        if ($result['status'] === 'conflict') {
            $conflicts[] = $result['conflict'];
        } else {
            $saved[] = $result['item'];
        }
    }

    if (!empty($conflicts)) {
        return response()->json([
            'message' => 'Conflict',
            'conflicts' => $conflicts,
        ], 409);
    }

    return response()->json([
        'message' => 'Salvataggio completato',
        'savedItems' => $saved,
    ]);
}




public function update( Request $request, OrderItem $orderItem, OrderItemUpdater $updater ) {
/*            
        $validated = $request->validate([
            'warehouse_id' => 'required|integer',
            'warehouse_notes' => 'nullable|string',
            'warehouse_manager_id' => 'nullable|integer',
            'worker_id' => 'nullable|integer',
            'has_selection' => 'nullable|boolean',
            'selection_time' => 'nullable|integer',
            'is_ragnabile' => 'nullable|boolean',
            'machinery_time_fraction' => 'nullable|integer',
            'is_machinery_time_manual' => 'nullable|boolean',
            'is_transshipment' => 'nullable|boolean',   
            'weight_gross' => 'nullable|numeric',
            'weight_tare' => 'nullable|numeric',
            'weight_net' => 'nullable|numeric',
        ]);
*/
        $userId = $request->user()->id;

        // Se usi file upload per le immagini
        $images = $request->file('images', []);

        $data = array_merge(
          $request->except('images'),
          ['images' => $images]
        );

        $result = $updater->safeUpdate($orderItem, $data, $userId);

        if ($result['status'] === 'conflict') {
            return response()->json([
                'message'  => 'Conflict',
                'conflicts'=> [$result['conflict']],
            ], 409);
        }

    // Se non ci sono conflitti, ritorna l'item aggiornato
        return response()->json([
        'message'    => 'Item salvato',
        'savedItems' => [ $result['item'] ],
        ], 200);
    }




    public function moveJourneyCargo(Request $request, OrderItem $orderItem) {
        $validated = $request->validate([
            'journey_cargo_id' => 'required|integer',
        ]);
    
        $orderItem->update(
            $validated
        );
    
        return response()->json(['message' => 'Item moved successfully.', 'orderItem' => $orderItem], 200);
    }
}