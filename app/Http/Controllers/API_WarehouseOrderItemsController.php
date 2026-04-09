<?php

namespace App\Http\Controllers;

//API_WarehouseOrderItemsController.php

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Services\OrderItemUpdater;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\OrderItemExplosionSync;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Gate;


class API_WarehouseOrderItemsController extends Controller{


public function saveItems(
    Request $request,
    OrderItemUpdater $updater,
    OrderItemExplosionSync $explosionSync
) {
    $conflicts = [];
    $saved     = [];
    $userId    = $request->user()->id;

    if ($request->filled('order_id')) {
        $order = Order::findOrFail($request->input('order_id'));
        Gate::authorize('warehouseManage', $order);

        // normalizza il gruista: ' => null
        $craneOperatorUserId = $request->input('crane_operator_user_id');
        $craneOperatorUserId = ($craneOperatorUserId === '' || $craneOperatorUserId === null) ? null : (int) $craneOperatorUserId;

        $order->forceFill([
            'has_crane'              => (int) $request->boolean('has_crane'),
            'crane_operator_user_id' => $craneOperatorUserId,
            'machinery_time_minutes' => (int) $request->input('machinery_time_minutes', 0),
            //'updated_by_user_id'  => $userId, // se usi l’audit
        ])->save();
    }

    foreach ($request->input('items', []) as $index => $data) {
        $item = OrderItem::findOrFail($data['id']);
        Gate::authorize('warehouseManage', $item->order);

        // files caricati per questo indice
        $images = $request->file("items.$index.images", []);

        // explosions: JSON string → array
        //$explosions = json_decode($request->input("items.$index.explosions", '[]'), true) ?: [];
        $explosionsRaw = json_decode($request->input("items.$index.explosions", '[]'), true) ?: [];
        $explosions    = $this->normalizeExplosionsArray($explosionsRaw);

        // payload scalari: togli ‘images’ e ‘explosions’
        $scalars = collect($data)->except(['images', 'explosions'])->all();

        // safeUpdate: setta updated_by_user_id, gestisce upload immagini
        $result = $updater->safeUpdate($item, array_merge($scalars, ['images' => $images]), $userId);

        if ($result['status'] === 'conflict') {
            $conflicts[] = $result['conflict'];
            continue;
        }

        // sync esplosione (anche [] per wipe)
        $explosionSync->sync($item->id, $explosions);

        // ricarica relazioni per UI
        $fresh = $item->fresh([
            'images',
            'holder',
            'cerCode',
            'warehouse',
        ]);

        // ATTACCA l’albero annidato come "explosions"
        $fresh->setRelation('explosions', $item->explosionsTree()->get());

        $saved[] = $fresh;
    }

    if (!empty($conflicts)) {
        return response()->json([
            'message'   => 'Conflict',
            'conflicts' => $conflicts,
        ], 409);
    }

    return response()->json([
        'message'    => 'Salvataggio completato',
        'savedItems' => $saved,
        'order'      => isset($order) ? $order->fresh() : null,
    ]);
}


public function update(
    Request $request,
    OrderItem $orderItem,
    OrderItemUpdater $updater,
    OrderItemExplosionSync $explosionSync,
) {
    Gate::authorize('warehouseManage', $orderItem->order);

    $userId = $request->user()->id;

    // 1) PRIMITIVI (eccetto images/explosions)
    $data = $request->except(['images', 'explosions']);

    // 2) IMMAGINI (array di UploadedFile)
    $images = $request->file('images', []);

    // 3) ESPLOSIONE (JSON string -> array)
    // $explosions = json_decode($request->input('explosions', '[]'), true) ?: [];
    $explosionsRaw = json_decode($request->input('explosions', '[]'), true) ?: [];
    $explosions    = $this->normalizeExplosionsArray($explosionsRaw);

    // 4) safeUpdate (gestisce anche upload immagini)
    $result = $updater->safeUpdate($orderItem, array_merge($data, ['images' => $images]), $userId);

    if ($result['status'] === 'conflict') {
        return response()->json([
            'message'   => 'Conflict',
            'conflicts' => [$result['conflict']],
        ], 409);
    }

    // 5) Sync esplosione (accetta l’ID dell’item)
    $explosionSync->sync($orderItem->id, $explosions, $userId);

    // 6) Carica relazioni utili per il frontend
    $fresh = $orderItem->fresh([
        'images',
        'holder',
        'cerCode',
        'warehouse',
    ]);

    // ATTACCA l’albero annidato come "explosions"
    $fresh->setRelation('explosions', $orderItem->explosionsTree()->get());

    // se l’Image model espone accessor url, ok; in caso contrario puoi mappare qui
    // $fresh->images->each(fn($img) => $img->url = Storage::disk('public')->url($img->filename));

    return response()->json([
        'item' => $fresh,
    ]);
}



    public function moveJourneyCargo(Request $request, OrderItem $orderItem)
    {
        Gate::authorize('warehouseManage', $orderItem->order);

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
                'download_warehouse_id' => $data['warehouse_id'],
            ];

            // Mantieni solo questa associazione attiva
            $orderItem->journeyCargos()->sync([
                $data['journey_cargo_id'] => $pivotAttrs
            ]);

            // 🔧 3) IMPORTANTISSIMO: resetta cache relazioni e ricarica
            $orderItem->unsetRelation('journeyCargos'); // svuota relazione in cache
            $orderItem->refresh(); // ricarica attributi dal DB (warehouse_id, ecc.)
            $orderItem->load([
                'journeyCargos' => fn($q) => $q->select('journey_cargos.id')->withPivot('download_warehouse_id'),
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
        Gate::authorize('warehouseManage', $orderItem->order);

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


    private function normalizeExplosionNode(array $n): ?array
    {
        $catalogId = $n['catalog_item_id']
            ?? ($n['_selected']['id'] ?? null)
            ?? ($n['catalog_item']['id'] ?? null);

        if (!$catalogId) {
            return null; // scarta nodi senza id valido
        }

        // NON affidarti al 'type' per decidere il peso: può non arrivare dal FE.
        // Se weight_net è presente, prendilo. Se mancante, lascialo null.
        $weight = array_key_exists('weight_net', $n)
            ? (is_null($n['weight_net']) ? null : (float) $n['weight_net'])
            : null;

        $children = [];
        if (!empty($n['children']) && is_array($n['children'])) {
            foreach ($n['children'] as $child) {
                $cn = $this->normalizeExplosionNode($child);
                if ($cn) $children[] = $cn;
            }
        }

        return [
            'catalog_item_id' => (int)$catalogId,
            'recipe_id'       => $n['_selectedRecipeId'] ?? ($n['recipe_id'] ?? null),
            'recipe_version'  => $n['recipe_version'] ?? null,
            'explosion_source'=> $n['explosion_source'] ?? 'ad_hoc',
            'weight_net'      => $weight,
            'notes'           => $n['notes'] ?? null,
            'children'        => $children,
        ];
    }

    private function normalizeExplosionsArray($nodes): array
    {
        $out = [];
        if (!is_array($nodes)) return $out;
        foreach ($nodes as $n) {
            $nn = $this->normalizeExplosionNode($n);
            if ($nn) $out[] = $nn;
        }
        return $out;
    }





}



