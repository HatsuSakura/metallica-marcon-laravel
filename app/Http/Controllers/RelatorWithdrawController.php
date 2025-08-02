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
use App\Enums\WithdrawsState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RelatorWithdrawController extends Controller
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

        return inertia('Relator/Withdraw/Create',[
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

        $request->validate([
            'dataRitiro' => 'required|date',
            'percentualeResidua'=> 'required|numeric|min:0|max:100',
            'customer_id'=> 'required',
            'site_id'=> 'required',
            'user_id'=> 'required',
            'vehicle_id'=> 'required',
            'trailer_id' => 'required',
            'driver_id'=> 'required',
            'insManuale' => 'required',
            'items' => 'nullable|array', // Make items optional
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $withdraw = Withdraw::create(
           $request
        );

        // Create the items
        foreach ($request['items'] as $item) {
            $withdraw->items()->create($item);
        }

        /*
        return response()->json([
            'message' => 'Order created successfully!',
            'order' => $withdraw->load('items'),
        ]);
        */

        //return redirect()->route('relator.withdraw.index')->with('success', 'Ritiro inserito con successo!');
        return redirect()->back()->with('success', 'Ritiro inserito con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Withdraw $withdraw)
    {
        Gate::authorize('update', $withdraw);
        return inertia(
            'Relator/Withdraw/Edit',
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
        $withdraw->update([
            $request->validate([
                'dataRitiro' => 'required|date',
                'percentualeResidua'=> 'required|numeric|min:0|max:100',
                'customer_id'=> 'required',
                'site_id'=> 'required',
                'user_id'=> 'required',
                'vehicle_id'=> 'required',
                'driver_id'=> 'required',
                'insManuale' => 'required',
            ])
        ]);

        //return redirect()->route('relator.withdraw.index')->with('success', 'Ritiro modificato con successo!');
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
