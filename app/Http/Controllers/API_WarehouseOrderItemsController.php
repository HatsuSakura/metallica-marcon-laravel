<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Services\OrderItemUpdater;
use Illuminate\Support\Facades\DB;
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



    public function moveJourneyCargo(Request $request, OrderItem $orderItem)
    {
        $data = $request->validate([
            'journey_cargo_id'      => ['required','integer','exists:journey_cargos,id'],
            'warehouse_id'          => ['required','integer','exists:warehouses,id'], // ← nuovo
            'is_double_load'        => ['sometimes','boolean'],
        ]);

        return DB::transaction(function () use ($orderItem, $data, $request) {
            // 1) Aggiorna l'ORDER ITEM (spostamento di magazzino)
            $orderItem->forceFill([
                'warehouse_id'         => $data['warehouse_id'],
                'is_not_found'         => false, // resetta se era "not found"
                // opzionale, se lo usi per audit/locking:
                'updated_by_user_id'   => optional($request->user())->id,
            ])->save();

            // 2) Aggiorna/Imposta la PIVOT journey_cargo_order_item
            $pivotAttrs = [
                'is_double_load'        => (int)($data['is_double_load'] ?? 0),
                // se non viene passato, usa il warehouse corrente come download
                'warehouse_download_id' => $data['warehouse_id'],
            ];

            // Mantieni solo questa associazione attiva
            $orderItem->journeyCargos()->sync([
                $data['journey_cargo_id'] => $pivotAttrs
            ]);

            // 🔧 3) IMPORTANTISSIMO: resetta cache relazioni e ricarica
            $orderItem->unsetRelation('journeyCargos'); // svuota relazione in cache
            $orderItem->refresh(); // ricarica attributi dal DB (warehouse_id, ecc.)
            $orderItem->load([
                'journeyCargos' => fn($q) => $q->select('journey_cargos.id')->withPivot('warehouse_download_id'),
                'warehouse',
                'holder',         // 👈 quello che OrderItemRow legge
                'cerCode',   // 👈 idem
                'images' // se li mostri
            ]);
            $orderItem->append('warehouse_download'); // se necessario, ma in teoria è già in $appends!

            // (gli appends 'warehouse_download' e 'journey_cargo' verranno ricalcolati ora)
            return response()->json([
                'message'   => 'Item moved successfully.',
                'orderItem' => $orderItem,
            ], 200);
        });
    }




    public function flagNotFound(Request $request, OrderItem $orderItem)
    {
        $data = $request->validate([
            'is_not_found' => ['required','boolean'],
            'updated_at'   => ['nullable','date'], // per optimistic locking “soft”
        ]);

        // (Opzionale ma consigliato) optimistic lock
        if (!empty($data['updated_at'])) {
            $clientUpdatedAt = Carbon::parse($data['updated_at'])->toImmutable();
            if ($orderItem->updated_at && $orderItem->updated_at->ne($clientUpdatedAt)) {
                return back()->withErrors([
                    'conflict' => "L'item è stato aggiornato da un altro utente. Ricarica la pagina."
                ], 409);
            }
        }

        $orderItem->forceFill([
            'is_not_found'        => $data['is_not_found'],
            'updated_by_user_id'  => $request->user()->id, // in linea col vostro locking
        ])->save();

        // Se la richiesta arriva da XHR/axios puoi restituire JSON
        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Stato "not found" aggiornato.');
    }




}