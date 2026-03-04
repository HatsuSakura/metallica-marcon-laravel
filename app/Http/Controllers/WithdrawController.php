<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Holder;
use App\Enums\UserRole;
use App\Models\CerCode;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WithdrawController extends Controller
{
        /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Withdraw::class);

        // Fetch trucks and drivers (assuming Driver is a special type of User)
        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
        $cerList = CerCode::select('id', 'code', 'description', 'is_dangerous')->get();

        return inertia('Withdraw/Create',[
            'vehicles' => $vehicles,
            'trailers' => $trailers,
            'holders' => $holders,
            'drivers' => $drivers,
            'cerList' => $cerList
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'withdrawn_at' => 'required|date',
            'residue_percentage'=> 'required|numeric|min:0|max:100',
            'customer_id'=> 'required|integer|exists:customers,id',
            'site_id'=> 'required|integer|exists:sites,id',
            'created_by_user_id'=> 'required|integer|exists:users,id',
            'vehicle_id'=> 'required|integer|exists:vehicles,id',
            'driver_id'=> 'required|integer|exists:users,id',
            'is_manual_entry' => 'required|boolean',
        ]);

        Withdraw::create($validated);

        //return redirect()->route('withdraw.index')->with('success', 'Ritiro inserito con successo!');
        return redirect()->back()->with('success', 'Ritiro inserito con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Withdraw $withdraw)
    {
        Gate::authorize('update', $withdraw);
        return inertia(
            'Withdraw/Edit',
            [
                'withdraw' => $withdraw
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Withdraw $withdraw)
    {
        $validated = $request->validate([
            'withdrawn_at' => 'required|date',
            'residue_percentage'=> 'required|numeric|min:0|max:100',
            'customer_id'=> 'required|integer|exists:customers,id',
            'site_id'=> 'required|integer|exists:sites,id',
            'created_by_user_id'=> 'required|integer|exists:users,id',
            'vehicle_id'=> 'required|integer|exists:vehicles,id',
            'driver_id'=> 'required|integer|exists:users,id',
            'is_manual_entry' => 'required|boolean',
        ]);

        $withdraw->update($validated);

        //return redirect()->route('withdraw.index')->with('success', 'Ritiro modificato con successo!');
        return redirect()->back()->with('success', 'Ritiro modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Withdraw $withdraw)
    {
        Gate::authorize('delete', $withdraw);
        $withdraw->deleteOrFail();

        return redirect()->back()->with('success', 'Ritiro cancellato con successo!');
    }

    public function restore(Withdraw $withdraw){
        $withdraw->restore();
        return redirect()->back()->with('success', 'Ritiro ripristinato con successo!');
    }

}




