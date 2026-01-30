<?php

namespace App\Http\Controllers;

use App\Models\Holder;
use Illuminate\Http\Request;

class HolderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Holder::class, 'holder');
    }

    public function index(Request $request)
    {
        return inertia('Holder/Index', [
            'holders' => Holder::query()
                ->where('is_custom', false)
                ->with('equivalentHolder')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function show(Holder $holder)
    {
        return inertia('Holder/Show', [
            'holder' => $holder->load('equivalentHolder'),
        ]);
    }

    public function create()
    {
        return inertia('Holder/Create', [
            'holders' => Holder::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'volume' => 'nullable|numeric',
            'equivalent_holder_id' => 'nullable|exists:holders,id',
            'equivalent_units' => 'nullable|integer|min:1|required_with:equivalent_holder_id',
        ]);

        if (empty($validated['equivalent_holder_id'])) {
            $validated['equivalent_holder_id'] = null;
            $validated['equivalent_units'] = null;
        }

        $validated['is_custom'] = false;

        Holder::create($validated);

        return redirect()
            ->route('holder.index')
            ->with('success', 'Contenitore creato correttamente!');
    }

    public function edit(Holder $holder)
    {
        return inertia('Holder/Edit', [
            'holder' => $holder,
            'holders' => Holder::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Holder $holder)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'volume' => 'nullable|numeric',
            'equivalent_holder_id' => 'nullable|exists:holders,id',
            'equivalent_units' => 'nullable|integer|min:1|required_with:equivalent_holder_id',
        ]);

        if (empty($validated['equivalent_holder_id'])) {
            $validated['equivalent_holder_id'] = null;
            $validated['equivalent_units'] = null;
        }

        $validated['is_custom'] = false;

        $holder->update($validated);

        return redirect()
            ->route('holder.index')
            ->with('success', 'Contenitore modificato con successo!');
    }

    public function destroy(Holder $holder)
    {
        $holder->deleteOrFail();

        return redirect()
            ->back()
            ->with('success', 'Contenitore eliminato');
    }
}
