<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Trailer;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Enums\VehicleTipologia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RelatorVehicleController extends Controller
{

    public function __consruct(){
        $this->authorizeResource(Vehicle::class, 'vehicle');
    }

    public function index(Request $request){
        $filters = [
            'deleted' => $request->boolean('deleted'),
            ...$request->only(['by', 'order']) // ... is like "merge array"
        ];

        return inertia(
            'Relator/Vehicle/Index',
            [
                'filters' => $filters,
                'vehicles' => Vehicle::query()
                //->alphabetic()
                //->withCount('sites')
                ->with(['usualDriver', 'preferredTrailer'])
                //->filter($filters)
                ->paginate(25)
                ->withQueryString()
            ]);
    }

    public function show(Vehicle $vehicle){
        return inertia(
            'Relator/Vehicle/Show',
            ['vehicle' => $vehicle->load('offers', 'offers.bidder')],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Vehicle::class);

        $trailers = Trailer::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
 

        return inertia(
            'Relator/Vehicle/Create',
            [
                'trailers' => $trailers,
                'drivers' => $drivers,
                'types' => array_map(fn($type) => ['value' => $type->value, 'label' => ucfirst($type->value)], VehicleTipologia::cases()),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // sostistuisco "//Vehicle::create([" con questa nuova riga per generare direttamente il LISTING associato all'utente che lo crea
        Vehicle::create(
            $request->validate([
                'name' => 'required',
                'description' => 'required',
                'plate' => 'required',
                'type' => 'required|min:1',
                'has_trailer' => 'required|boolean',
                'load_capacity' => 'required|integer|min:1000|max:50000',
                'driver_id' => 'nullable',
                'trailer_id' => 'nullable',
            ])
        );

        return redirect()->route('relator.vehicle.index')->with('success', 'Automezzo inserito con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        Gate::authorize('update', $vehicle);

        $trailers = Trailer::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();


        return inertia(
            'Relator/Vehicle/Edit',
            [
                'vehicle' => $vehicle,
                'trailers' => $trailers,
                'drivers' => $drivers,
                'types' => array_map(fn($type) => ['value' => $type->value, 'label' => ucfirst($type->value)], VehicleTipologia::cases()),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'plate' => 'required',
            'type' => 'required|min:1',
            'has_trailer' => 'required|boolean',
            'load_capacity' => 'required|integer|min:1000|max:50000',
            'driver_id' => 'nullable',
            'trailer_id' => 'nullable',
        ]);
        
        $vehicle->update($validated);

        return redirect()->route('relator.vehicle.index')->with('success', 'Automezzo modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        Gate::authorize('delete', $vehicle);
        $vehicle->deleteOrFail();

        return redirect()->back()->with('success', 'Vehicle was deleted');
    }

    public function restore(Vehicle $vehicle){
        $vehicle->restore();
        return redirect()->back()->with('success', 'Vehicle was restored');
    }

}
