<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Site;
use App\Models\Vehicle;
use App\Models\Withdraw;
use App\Services\CalculateRiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WithdrawController extends Controller
{
        /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Withdraw::class);

        $validatedQuery = $request->validate([
            'site' => 'required|integer|exists:sites,id',
            'customer' => 'required|integer|exists:customers,id',
        ]);

        $vehicles = Vehicle::query()->select('id', 'name', 'plate')->orderBy('name')->get();
        $drivers = User::query()
            ->select('id', 'name', 'surname')
            ->where('role', UserRole::DRIVER->value)
            ->orderBy('name')
            ->get();

        $selectedSite = Site::query()
            ->with([
                'customer:id,company_name,legal_address',
                'timetable:id,site_id,hours_json',
            ])
            ->select([
                'id',
                'customer_id',
                'name',
                'address',
                'calculated_risk_factor',
                'days_until_next_withdraw',
            ])
            ->findOrFail((int) $validatedQuery['site']);

        if ((int) $selectedSite->customer_id !== (int) $validatedQuery['customer']) {
            throw ValidationException::withMessages([
                'site' => 'La sede selezionata non appartiene al cliente indicato.',
            ]);
        }

        return inertia('Withdraw/Create',[
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'selectedSite' => $selectedSite,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, CalculateRiskService $calculateRiskService)
    {
        $validated = $request->validate([
            'withdrawn_at' => 'required|date',
            'residue_percentage'=> 'nullable|numeric|min:0|max:100',
            'customer_id'=> 'required|integer|exists:customers,id',
            'site_id'=> 'required|integer|exists:sites,id',
            'vehicle_id'=> 'nullable|integer|exists:vehicles,id',
            'driver_id'=> 'nullable|integer|exists:users,id',
            'is_manual_entry' => 'nullable|boolean',
        ]);

        $site = Site::query()
            ->select('id', 'customer_id')
            ->findOrFail($validated['site_id']);

        if ((int) $site->customer_id !== (int) $validated['customer_id']) {
            throw ValidationException::withMessages([
                'site_id' => 'La sede selezionata non appartiene al cliente indicato.',
            ]);
        }

        Withdraw::create([
            'withdrawn_at' => $validated['withdrawn_at'],
            'residue_percentage' => $validated['residue_percentage'] ?? 0,
            'customer_id' => $validated['customer_id'],
            'site_id' => $validated['site_id'],
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'is_manual_entry' => $validated['is_manual_entry'] ?? true,
            'created_by_user_id' => (int) $request->user()->id,
        ]);

        $calculateRiskService->recalculateSiteRisk([
            'siteId' => (int) $validated['site_id'],
        ]);

        return redirect()
            ->route('customer.show', ['customer' => $validated['customer_id']])
            ->with('success', 'Ritiro inserito con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Withdraw $withdraw)
    {
        Gate::authorize('update', $withdraw);

        $vehicles = Vehicle::query()->select('id', 'name', 'plate')->orderBy('name')->get();
        $drivers = User::query()
            ->select('id', 'name', 'surname')
            ->where('role', UserRole::DRIVER->value)
            ->orderBy('name')
            ->get();

        $withdraw->loadMissing([
            'site.customer:id,company_name,legal_address',
        ]);

        return inertia('Withdraw/Edit', [
            'withdraw' => $withdraw,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Withdraw $withdraw, CalculateRiskService $calculateRiskService)
    {
        $validated = $request->validate([
            'withdrawn_at' => 'required|date',
            'residue_percentage'=> 'nullable|numeric|min:0|max:100',
            'customer_id'=> 'required|integer|exists:customers,id',
            'site_id'=> 'required|integer|exists:sites,id',
            'vehicle_id'=> 'nullable|integer|exists:vehicles,id',
            'driver_id'=> 'nullable|integer|exists:users,id',
            'is_manual_entry' => 'nullable|boolean',
        ]);

        $site = Site::query()
            ->select('id', 'customer_id')
            ->findOrFail($validated['site_id']);

        if ((int) $site->customer_id !== (int) $validated['customer_id']) {
            throw ValidationException::withMessages([
                'site_id' => 'La sede selezionata non appartiene al cliente indicato.',
            ]);
        }

        $withdraw->update([
            'withdrawn_at' => $validated['withdrawn_at'],
            'residue_percentage' => $validated['residue_percentage'] ?? 0,
            'customer_id' => $validated['customer_id'],
            'site_id' => $validated['site_id'],
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'is_manual_entry' => $validated['is_manual_entry'] ?? true,
        ]);

        $calculateRiskService->recalculateSiteRisk([
            'siteId' => (int) $validated['site_id'],
        ]);

        return redirect()
            ->route('customer.show', ['customer' => $validated['customer_id']])
            ->with('success', 'Ritiro modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Withdraw $withdraw, CalculateRiskService $calculateRiskService)
    {
        Gate::authorize('delete', $withdraw);
        try {
            $siteId = (int) $withdraw->site_id;
            $customerId = (int) $withdraw->customer_id;

            $withdraw->deleteOrFail();

            if ($siteId > 0) {
                $calculateRiskService->recalculateSiteRisk([
                    'siteId' => $siteId,
                ]);
            }

            return redirect()
                ->route('customer.show', ['customer' => $customerId])
                ->with('success', 'Ritiro cancellato con successo!');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Errore durante la cancellazione del ritiro.');
        }
    }

    public function restore(Withdraw $withdraw){
        $withdraw->restore();
        return redirect()->back()->with('success', 'Ritiro ripristinato con successo!');
    }

}
