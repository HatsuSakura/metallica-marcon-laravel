<?php

namespace App\Http\Controllers;

use App\Enums\SiteTipologia;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Site::class, 'site');
    }

    public function index(Request $request)
    {
        return redirect()->route('customer.index');
    }

    public function show(Site $site)
    {
        return redirect()->route('customer.show', ['customer' => $site->customer_id]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Site::class);
        return redirect()->route('customer.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:191',
            'site_type' => ['nullable', 'string', Rule::in(array_column(SiteTipologia::cases(), 'value'))],
            'is_main' => 'nullable|boolean',
            'address' => 'required|string|max:191',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'has_muletto' => 'nullable|boolean',
            'has_electric_pallet_truck' => 'nullable|boolean',
            'has_manual_pallet_truck' => 'nullable|boolean',
            'other_machines' => 'nullable|string',
            'has_adr_consultant' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $isMain = (bool) ($validated['is_main'] ?? false);
        $hasNoSites = !Site::query()->where('customer_id', $validated['customer_id'])->exists();

        if ($hasNoSites || $isMain) {
            Site::query()
                ->where('customer_id', $validated['customer_id'])
                ->update(['is_main' => false]);
            $isMain = true;
        }

        Site::create([
            'customer_id' => (int) $validated['customer_id'],
            'name' => $validated['name'],
            'site_type' => $validated['site_type'] ?? null,
            'is_main' => $isMain,
            'address' => $validated['address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'has_muletto' => (bool) ($validated['has_muletto'] ?? false),
            'has_electric_pallet_truck' => (bool) ($validated['has_electric_pallet_truck'] ?? false),
            'has_manual_pallet_truck' => (bool) ($validated['has_manual_pallet_truck'] ?? false),
            'other_machines' => $validated['other_machines'] ?? '',
            'has_adr_consultant' => (bool) ($validated['has_adr_consultant'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Sede creata con successo');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site)
    {
        Gate::authorize('update', $site);
        return redirect()->route('customer.show', ['customer' => $site->customer_id]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:191',
            'site_type' => ['nullable', 'string', Rule::in(array_column(SiteTipologia::cases(), 'value'))],
            'is_main' => 'nullable|boolean',
            'address' => 'required|string|max:191',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'has_muletto' => 'nullable|boolean',
            'has_electric_pallet_truck' => 'nullable|boolean',
            'has_manual_pallet_truck' => 'nullable|boolean',
            'other_machines' => 'nullable|string',
            'has_adr_consultant' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $isMain = (bool) ($validated['is_main'] ?? false);
        if ($isMain) {
            Site::query()
                ->where('customer_id', $validated['customer_id'])
                ->where('id', '!=', $site->id)
                ->update(['is_main' => false]);
        }

        $site->update([
            'customer_id' => (int) $validated['customer_id'],
            'name' => $validated['name'],
            'site_type' => $validated['site_type'] ?? null,
            'is_main' => $isMain,
            'address' => $validated['address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'has_muletto' => (bool) ($validated['has_muletto'] ?? false),
            'has_electric_pallet_truck' => (bool) ($validated['has_electric_pallet_truck'] ?? false),
            'has_manual_pallet_truck' => (bool) ($validated['has_manual_pallet_truck'] ?? false),
            'other_machines' => $validated['other_machines'] ?? '',
            'has_adr_consultant' => (bool) ($validated['has_adr_consultant'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Sede aggiornata con successo');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Site $site)
    {
        Gate::authorize('delete', $site);
        $customerId = $site->customer_id;
        $wasMain = (bool) $site->is_main;
        $site->deleteOrFail();

        if ($wasMain) {
            $replacement = Site::query()
                ->where('customer_id', $customerId)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();

            if ($replacement) {
                $replacement->update(['is_main' => true]);
            }
        }

        return redirect()->back()->with('success', 'Sede eliminata');
    }

    public function restore(Site $site)
    {
        $site->restore();
        return redirect()->back()->with('success', 'Sede ripristinata');
    }

}




