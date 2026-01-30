<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cargo;
use App\Models\Order;
use App\Models\Holder;
use App\Enums\UserRole;
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

class RelatorJourneyController extends Controller
{


    public function index(Request $request){

        $query = Journey::query()
        //->alphabetic()
        //->withCount('orders')
        //->filter($filters)
        ->with('driver')
        ->with('vehicle')
        ->with('trailer')
        ->paginate(25)
        ->withQueryString();


        return inertia(
            'Relator/Journey/Index',
            [
                //'filters' => $filters,
                'journeys' => $query
            ]);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Journey::class);

        // Fetch trucks and drivers (assuming Driver is a special type of User)
        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $cargos = Cargo::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
        $warehouses = Warehouse::all();
        $orders = Order::query()
            ->where('state', OrdersState::STATE_CREATED->value)
            ->with([
                'logistic:id,name',
                'customer:id,ragione_sociale', // Load only 'id' and 'ragione_sociale' from customers
                'site',   // Load 'id' 'indirizzo', 'lat', 'lng' from sites
                'items',
                'items.cerCode:id,code,is_dangerous',
                'items.holder:id,name,volume'
            ])
            ->get();

        return inertia('Relator/Journey/Create', 
        [
            'vehicles' => $vehicles,
            'trailers' => $trailers,
            'cargos' => $cargos,
            'holders' => $holders,
            'drivers' => $drivers,
            'warehouses' => $warehouses,
            'orders' => $orders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dt_start'    => 'required|date',
            'dt_end'      => 'required|date',
            'vehicle_id'  => 'required',
            'trailer_id'  => 'nullable',
            'cargo_for_vehicle_id'  => 'required',
            'cargo_for_trailer_id'  => 'nullable',
            'driver_id'   => 'required',
            'logistic_id' => 'required',
            'orders_truck'    => 'nullable|array',
            'orders_trailer'  => 'nullable|array',
            'orders_fulfill' => 'nullable|array',
        ]);

        $journey = Journey::create($validated);

        // Update order states for orders_truck
        if (!empty($validated['orders_truck'])) {
            $orders = Order::whereIn('id', $validated['orders_truck'])->get();
            //Log::info("ORDERS: {$orders}");

            foreach ($orders as $order) {
                $currentState = $order->state;
                if ($currentState instanceof OrdersState ){
                    //Log::info("CURRENT STATE: {$order->state->value}");
                }
                //Log::info("Transition check from {$currentState->value} to " . OrdersState::STATE_PLANNED->value);

                $canTransition = $currentState->canTransitionTo(OrdersState::STATE_PLANNED);
                //Log::info("Can transition? " . ($canTransition ? 'Yes' : 'No'));


                if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED)) {
                    $order->state = OrdersState::STATE_PLANNED;
                    $order->truck_location = OrdersTruckLocation::TRUCK_MOTRICE->value;
                    $order->journey_id = $journey->id;
                    $order->save();
                } else {
                    Log::warning("Invalid state transition from {$currentState->value} for order ID {$order->id}");
                }
            }
        }



        // Repeat for orders_trailer
        if (!empty($validated['orders_trailer'])) {
            $orders = Order::whereIn('id', $validated['orders_trailer'])->get();

            foreach ($orders as $order) {
                $currentState = $order->state;
                if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED)) {
                    $order->state = OrdersState::STATE_PLANNED->value;
                    $order->truck_location = OrdersTruckLocation::TRUCK_RIMORCHIO->value;
                    $order->journey_id = $journey->id;
                    $order->save();
                } else {
                    Log::warning("Invalid state transition for order ID {$order->id}");
                }
            }
        }

        // Repeat for orders_fulfill
        if (!empty($validated['orders_fulfill'])) {
            $orders = Order::whereIn('id', $validated['orders_fulfill'])->get();

            foreach ($orders as $order) {
                $currentState = $order->state;
                if ($currentState->canTransitionTo(OrdersState::STATE_PLANNED)) {
                    $order->state = OrdersState::STATE_PLANNED->value;
                    $order->truck_location = OrdersTruckLocation::TRUCK_RIEMPIMENTO->value;
                    $order->journey_id = $journey->id;
                    $order->save();
                } else {
                    Log::warning("Invalid state transition for order ID {$order->id}");
                }
            }
        }

        return redirect()->route('relator.journey.index')->with('success', 'Viaggio inserito con successo!');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Journey $journey)
    {
        Gate::authorize('update', $journey);
        return inertia(
            'Relator/Journey/Edit',
            [
                'journey' => $journey
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Journey $journey)
    {
        $journey->update([
            $request->validate([
                'customer_id'=> 'required',
                'site_id'=> 'required',
                'logistic_id'=> 'required',
                'items' => 'nullable|array', // Make items optional
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ])
        ]);

        //return redirect()->route('relator.journey.index')->with('success', 'Ritiro modificato con successo!');
        return redirect()->back()->with('success', 'Viaggio modificato con successo!');
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


/*
    CUSTOM actions for Journey life cycle 
*/


public function updateState(Journey $journey, Request $request)
{
    $newState = JourneysState::from($request->new_state);

    if (!JourneysState::from($journey->state)->canTransitionTo($newState)) {
        abort(403, 'Invalid state transition.');
    }

    // Add lifecycle-specific logic
    switch ($newState) {
        case JourneysState::STATE_CREATED:
            $journey->planned_date = $request->planned_date;
            break;

        case JourneysState::STATE_ACTIVE:
            $journey->executed_at = now();
            break;

        case JourneysState::STATE_EXECUTED:
            // Attachments or warehouse updates
            $journey->downloaded_files = $request->file('attachments')->store('journeys');
            break;
    }

    $journey->state = $newState->value;
    $journey->save();

    return redirect()->back()->with('success', "Journey state updated to {$newState->value}.");
}



}
