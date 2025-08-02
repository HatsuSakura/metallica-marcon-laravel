<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class RelatorCargoController extends Controller
{

    public function __consruct(){
        $this->authorizeResource(Cargo::class, 'cargo');
    }

    public function index(Request $request){
        $filters = [
            'deleted' => $request->boolean('deleted'),
            ...$request->only(['by', 'order']) // ... is like "merge array"
        ];

        return inertia(
            'Relator/Cargo/Index',
            [
                'filters' => $filters,
                'cargos' => Cargo::query()
                //->alphabetic()
                //->withCount('sites')
                //->filter($filters)
                ->paginate(25)
                ->withQueryString()
            ]);
    }

    public function show(Cargo $cargo){
        return inertia(
            'Relator/Cargo/Show',
            ['cargo' => $cargo->load('offers', 'offers.bidder')],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Cargo::class);
        return inertia('Relator/Cargo/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([         
            'name'  => 'required',
            'description'  => 'required',
            'is_cargo' => 'required|boolean',
            'is_long'  => 'required|boolean',
            'length' => 'required|numeric',
            'casse'  => 'required|integer|min:0|max:100',
            'spazi_bancale'  => 'required|integer|min:0|max:100',
            'spazi_casse'  => 'required|integer|min:0|max:100',
            'total_count'  => 'required|integer|min:0|max:9999',
        ]);

        Cargo::create(
            $validated
        );

        return redirect()->route('relator.cargo.index')->with('success', 'Tipo cassone creato correttamente!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cargo $cargo)
    {
        Gate::authorize('update', $cargo);

        return inertia(
            'Relator/Cargo/Edit',
            [
                'cargo' => $cargo
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cargo $cargo)
    {
        
        $validated = $request->validate([         
            'name'  => 'required',
            'description'  => 'required',
            'is_cargo' => 'required|boolean',
            'is_long'  => 'required|boolean',
            'length' => 'required|numeric',
            'casse'  => 'required|integer|min:0|max:100',
            'spazi_bancale'  => 'required|integer|min:0|max:100',
            'spazi_casse'  => 'required|integer|min:0|max:100',
            'total_count'  => 'required|integer|min:0|max:9999',
        ]);
        
        $cargo->update(
            $validated
        );

        return redirect()->route('relator.cargo.index')->with('success', 'Cassone modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cargo $cargo)
    {
        Gate::authorize('delete', $cargo);
        $cargo->deleteOrFail();

        return redirect()->back()->with('success', 'Cassone eliminato');
    }

    public function restore(Cargo $cargo){
        $cargo->restore();
        return redirect()->back()->with('success', 'Cassone ripristinato');
    }

}
