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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DriverJourneyController extends Controller
{


    public function index(Request $request){

        $user = $request->user();

        $activeTab = $request->query('tab', 'correnti');

        $currentJourneys = Journey::query()
            ->where('driver_id', $user->id)
            ->whereIn('status', [
                JourneysState::STATUS_CREATED->value,
                JourneysState::STATUS_ACTIVE->value,
            ])
            ->withCount('stops')
            ->with([
                'vehicle',
                'trailer',
                'driver',
                'stops' => fn ($q) => $q->orderBy('sequence'),
                'stops.customer',
                'stops.technicalAction',
                'stops.stopOrders.order.site',
                'stops.stopOrders.order.customer',
                'stops.stopOrders.order.items',
                'stops.stopOrders.order.items.cerCode',
            ])
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [JourneysState::STATUS_ACTIVE->value])
            ->orderByDesc('planned_start_at')
            ->get();

        $historyJourneys = Journey::query()
            ->where('driver_id', $user->id)
            ->where('status', JourneysState::STATUS_EXECUTED->value)
            ->with([
                'vehicle',
                'trailer',
                'driver',
            ])
            ->orderByDesc('actual_end_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->appends($request->query());

        $hasActiveJourney = Journey::query()
            ->where('driver_id', $user->id)
        ->where('status', JourneysState::STATUS_ACTIVE->value)
            ->exists();

        $warehouses = Warehouse::all();

        return inertia(
            'Driver/Journey/Index',
            [
                //'filters' => $filters,
                'currentJourneys' => $currentJourneys,
                'historyJourneys' => $historyJourneys,
                'warehouses' => $warehouses,
                'hasActiveJourney' => $hasActiveJourney,
                'activeTab' => in_array($activeTab, ['correnti', 'storico'], true) ? $activeTab : 'correnti',
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Journey $journey)
    {
        Gate::authorize('view', $journey);

        $returnTo = request()->query('return_to');
        if (!is_string($returnTo) || trim($returnTo) === '') {
            $returnTo = url()->previous();
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $isSafeReturnTo = Str::startsWith($returnTo, '/')
            || ($appUrl !== '' && Str::startsWith($returnTo, $appUrl));

        if (!$isSafeReturnTo) {
            $returnTo = route('driver.journey.index', ['tab' => 'correnti']);
        }

        return inertia(
            'Driver/Journey/Show',
            [
                'journey' => $journey->load(
                    'vehicle',
                    'trailer',
                    'driver',
                    'orders.customer',
                    'orders.site',
                    'orders.items',
                    'orders.holders',
                    'stops.customer',
                    'stops.technicalAction',
                    'stops.stopOrders.order.site',
                    'stops.stopOrders.order.customer',
                    'stops.stopOrders.order.items',
                    'stops.stopOrders.order.items.cerCode'
                )->loadCount('stops'),
                'returnTo' => $returnTo,
            ]
        );
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
        $journeyStopActions = \App\Models\JourneyStopAction::query()
            ->where('is_active', true)
            ->get(['id', 'label']);

        return inertia(
            'Driver/Journey/Edit',
            [
                'journey' => $journey->load(
                    'vehicle',
                    'trailer',
                    'driver',
                    'orders.customer',
                    'orders.site',
                    'orders.items',
                    'orders.holders',
                    'stops.customer',
                    'stops.technicalAction',
                    'stops.stopOrders.order.site',
                    'stops.stopOrders.order.customer',
                    'stops.stopOrders.order.items',
                    'stops.stopOrders.order.items.cerCode'
                )->loadCount('stops'),
                'holders' => $holders, 
                'cerList' => $cerList,
                'warehouses' => $warehouses,
                'journeyStopActions' => $journeyStopActions,
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
            'actual_start_at'          => ['nullable','date'],
            'actual_end_at'            => ['nullable','date'],

            // scarico a magazzino (1° e 2°)
            'primary_warehouse_id'           => ['nullable','exists:warehouses,id'],
            'primary_warehouse_download_at'  => ['nullable','date'],
            'secondary_warehouse_id'         => ['nullable','exists:warehouses,id'],
            'secondary_warehouse_download_at'=> ['nullable','date'],

            // flag
            'is_temporary_storage'     => ['required','boolean'],
            'is_double_load'           => ['required','boolean'],
        ]);

        // normalizza i boolean (gestisce "true"/"false" string)
        $validated['is_temporary_storage'] = (bool) ($validated['is_temporary_storage'] ?? false);
        $validated['is_double_load']       = (bool) ($validated['is_double_load'] ?? false);

        // se non è doppio scarico, azzera i campi del secondo scarico
        if (!$validated['is_double_load']) {
            $validated['secondary_warehouse_id'] = null;
            $validated['secondary_warehouse_download_at'] = null;
        }

        $validated['status'] = JourneysState::STATUS_EXECUTED;

        // aggiorna
        $journey->update($validated);

        /*
        // Update each order
        foreach ($orders as $order) {
            $order->update([
                'journey_id' => null,
                'status' => OrdersState::STATUS_CREATED,
                'cargo_location' => null,
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
                'status' => OrdersState::STATUS_CREATED,
                'cargo_location' => null,
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




