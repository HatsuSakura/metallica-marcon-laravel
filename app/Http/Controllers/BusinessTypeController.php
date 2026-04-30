<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BusinessTypeController extends Controller
{
    public function index()
    {
        Gate::authorize('accessBackofficeArea');

        return inertia('BusinessType/Index', [
            'businessTypes' => BusinessType::orderBy('name')->withCount('customers')->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('accessBackofficeArea');

        return inertia('BusinessType/Create');
    }

    public function store(Request $request)
    {
        Gate::authorize('accessBackofficeArea');

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:business_types,name',
            'description' => 'nullable|string',
        ]);

        BusinessType::create($validated);

        return redirect()->route('business-type.index')
            ->with('success', 'Tipologia attività creata con successo.');
    }

    public function edit(BusinessType $businessType)
    {
        Gate::authorize('accessBackofficeArea');

        return inertia('BusinessType/Edit', [
            'businessType' => $businessType,
        ]);
    }

    public function update(Request $request, BusinessType $businessType)
    {
        Gate::authorize('accessBackofficeArea');

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('business_types', 'name')->ignore($businessType->id)],
            'description' => 'nullable|string',
        ]);

        $businessType->update($validated);

        return redirect()->route('business-type.index')
            ->with('success', 'Tipologia attività aggiornata.');
    }

    public function destroy(BusinessType $businessType)
    {
        Gate::authorize('accessBackofficeArea');

        if ($businessType->customers()->exists()) {
            return back()->withErrors([
                'general' => "Impossibile eliminare: {$businessType->customers()->count()} clienti usano questa tipologia.",
            ]);
        }

        $businessType->delete();

        return redirect()->route('business-type.index')
            ->with('success', 'Tipologia attività eliminata.');
    }
}
