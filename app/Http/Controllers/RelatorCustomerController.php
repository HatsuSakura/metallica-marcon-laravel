<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Enums\CustomerJobType;
use App\Models\Area;
use App\Models\InternalContact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RelatorCustomerController extends Controller
{

    public function __consruct(){
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request){
        $filters = [
            'deleted' => $request->boolean('deleted'),
            'continuativo' => $request->boolean('continuativo', true), // SET the default value to TRUE if is not initialized (otherwise it reads it as a false)
            'occasionale' => $request->boolean('occasionale'),
            'localTable' => $request->boolean('localTable', false),
            ...$request->only(['chiave']) // ... is like "merge array"
        ];

        if ($filters['localTable']) {
            $query = Customer::query()
            ->alphabetic()
            ->withCount('sites')
            ->filter($filters)
            ->get(); 
        }
        else{
            $query = Customer::query()
            ->alphabetic()
            ->withCount('sites')
            ->filter($filters)
            ->paginate(25)
            ->withQueryString();
        }

        return inertia(
            'Relator/Customer/Index',
            [
                'filters' => $filters,
                'customers' => $query
            ]);
    }

    public function show(Customer $customer){
        $areas = Area::all();

        return inertia(
            'Relator/Customer/Show',
            //['customer' => $customer->load('sites')],
            [
                'customer' => $customer->load('sites', 'sites.owner', 'sites.areas', 'sites.internalContacts', 'sites.orders', 'sites.withdraws', 'sites.timetable'),
                'areas' => $areas,
            ],
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Customer::class);

        $managers = User::where('role', UserRole::MANAGER->value )->get(); 

        // Convert enum cases to an array
        $jobTypes = array_map(fn($type) => [
            'value' => $type->value,
            'label' => ucfirst($type->value),
        ], CustomerJobType::cases());

        return inertia('Relator/Customer/Create',
            [
                'managers' => $managers,
                'jobTypes' => $jobTypes,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        
            $validatedData = $request->validate([
                'customerOccasionale' => 'nullable|boolean',
                'ragioneSociale' => 'required',
                'partitaIva' => 'required',
                'codiceFiscale' => 'required',
                'indirizzoLegale' => 'required',
                'lat' => 'required',
                'lng' => 'required',
                'seller_id' => 'required',
                'codiceSdi' => 'required',
                'jobType' => 'required',
                'emailCommerciale' => 'required',
                'emailAmministrativa' => 'required',
                'pec' => 'required',  
                'responsabileSmaltimenti' => 'nullable',
                'telefonoPrincipale' => 'nullable',              
            ]);
                      
            $customer = Customer::create($validatedData);

            $customer->sites()->save(
                new Site([
                    'denominazione' => 'Principale',
                    'indirizzo'     => $request['indirizzoLegale'],
                    'lat'           => $request['lat'],
                    'lng'           => $request['lng'],
                ])
            );

        return redirect()->route('relator.customer.index')->with('success', 'Cliente creato con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        Gate::authorize('update', $customer);

        $customer->load('mainSite'); // Carica la sede principale

        $managers = User::where('role', UserRole::MANAGER->value )->get(); 

        // Convert enum cases to an array
        $jobTypes = array_map(fn($type) => [
            'value' => $type->value,
            'label' => ucfirst($type->value),
        ], CustomerJobType::cases());

        return inertia(
            'Relator/Customer/Edit',
            [
                'customer' => $customer,
                'managers' => $managers,
                'jobTypes' => $jobTypes, 
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validatedData = $request->validate([
            'customerOccasionale' => 'nullable|boolean',
            'ragioneSociale' => 'required',
            'partitaIva' => 'required',
            'codiceFiscale' => 'required',
            'indirizzoLegale' => 'required',
            'lat' => 'required',
            'lng' => 'required',
            'seller_id' => 'required',
            'codiceSdi' => 'required',
            'jobType' => 'required',
            'emailCommerciale' => 'required',
            'emailAmministrativa' => 'required',
            'pec' => 'required',  
            'responsabileSmaltimenti' => 'nullable',
            'telefonoPrincipale' => 'nullable',              
        ]);
        
        $customer->update($validatedData);
        
        $customer->mainSite()->update([
            'indirizzo' => $validatedData['indirizzoLegale'] ,
            'lat' => $validatedData['lat'] ,
            'lng' => $validatedData['lng'] ,
        ]);


        return redirect()->route('relator.customer.index')->with('success', 'Cliente modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        Gate::authorize('delete', $customer);
        $customer->deleteOrFail();

        return redirect()->back()->with('success', 'Cliente cancellato con successo');
    }

    public function restore(Customer $customer){
        $customer->restore();
        return redirect()->back()->with('success', 'Cliente ripristinato con successo');
    }

}
