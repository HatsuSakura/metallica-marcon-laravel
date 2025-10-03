<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cargo;
use App\Models\Order;
use App\Models\Holder;
use App\Enums\UserRole;
use App\Models\CerCode;
use App\Models\Journey;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Enums\OrdersState;
use App\Enums\JourneysState;
use Illuminate\Http\Request;
use App\Enums\OrdersTruckLocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DriverJourneyController extends Controller
{


    public function index(Request $request){

        $user = $request->user();

        $journeys = Journey::query()
        ->where('driver_id', $user->id)
        //->alphabetic()
        ->withCount('orders')
        //->filter($filters)
        ->with('vehicle', 'trailer', 'driver', 'orders.customer', 'orders.site')
        ->paginate(25)
        ->withQueryString();

        $warehouses = Warehouse::all();

        return inertia(
            'Driver/Journey/Index',
            [
                //'filters' => $filters,
                'journeys' => $journeys,
                'warehouses' => $warehouses,
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Journey $journey)
    {
        Gate::authorize('update', $journey);

        $holders = Holder::all();
        $cerList = CerCode::select('id', 'code', 'description', 'is_dangerous')->get();
        $warehouses = Warehouse::all();

        return inertia(
            'Driver/Journey/Edit',
            [
                'journey' => $journey->load('vehicle', 'trailer', 'driver', 'orders.customer', 'orders.site', 'orders.items', 'orders.holders'),
                'holders' => $holders, 
                'cerList' => $cerList,
                'warehouses' => $warehouses,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journey $journey)
    {
        Gate::authorize('update', $journey);

        $validated = $request->validate([
            // date/ora effettive del viaggio
            'real_dt_start'            => ['nullable','date'],
            'real_dt_end'              => ['nullable','date'],

            // scarico a magazzino (1° e 2°)
            'warehouse_id_1'           => ['nullable','exists:warehouses,id'],
            'warehouse_download_dt_1'  => ['nullable','date'],

            'warehouse_id_2'           => ['nullable','exists:warehouses,id'],
            'warehouse_download_dt_2'  => ['nullable','date'],

            // flag
            'is_temporary_storage'     => ['required','boolean'],
            'is_double_load'           => ['required','boolean'],
        ]);

        // normalizza i boolean (gestisce "true"/"false" string)
        $validated['is_temporary_storage'] = (bool) ($validated['is_temporary_storage'] ?? false);
        $validated['is_double_load']       = (bool) ($validated['is_double_load'] ?? false);

        // se non è doppio scarico, azzera i campi del secondo scarico
        if (!$validated['is_double_load']) {
            $validated['warehouse_id_2'] = null;
            $validated['warehouse_download_dt_2'] = null;
        }

        $validated['state'] = JourneysState::STATE_EXECUTED;

        // aggiorna
        $journey->update($validated);

        /*
        // Update each order
        foreach ($orders as $order) {
            $order->update([
                'journey_id' => null,
                'state' => OrdersState::STATE_CREATED,
                'truck_location' => null,
            ]);
        }
        */

        return redirect()
            ->route('driver.journey.index')
            ->with('success', 'Viaggio modificato con successo!');
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Journey $journey)
    {
        Gate::authorize('delete', $journey);


        // Retrieve all orders associated with the journey
        $orders = $journey->orders;

        // Update each order
        foreach ($orders as $order) {
            $order->update([
                'journey_id' => null,
                'state' => OrdersState::STATE_CREATED,
                'truck_location' => null,
            ]);
        }


        $journey->deleteOrFail();

        return redirect()->back()->with('success', 'Viaggio cancellato con successo!');
    }

    public function restore(Journey $journey){
        $journey->restore();
        return redirect()->back()->with('success', 'Viaggio ripristinato con successo!');
    }


}
