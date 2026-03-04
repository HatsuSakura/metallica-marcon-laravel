<?php

namespace App\Http\Controllers;

use App\Enums\CustomerJobType;
use App\Enums\OrdersState;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function __consruct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request)
    {
        $filters = [
            'deleted' => $request->boolean('deleted'),
            'continuativo' => $request->boolean('continuativo', true),
            'occasionale' => $request->boolean('occasionale'),
            'localTable' => $request->boolean('localTable', false),
            ...$request->only(['chiave']),
        ];

        $transform = function ($customer) {
            return [
                ...$customer->toArray(),
                'sites_count' => (int) $customer->sites_count,
                'open_orders_count' => (int) ($customer->open_orders_count ?? 0),
                'total_orders_count' => (int) ($customer->total_orders_count ?? 0),
                'can' => [
                    'createOrder' => Gate::allows('create', Order::class),
                ],
            ];
        };

        $base = Customer::query()
            ->alphabetic()
            ->withCount('sites')
            ->with(['sites' => fn ($q) => $q->select('id', 'customer_id', 'name', 'is_main')])
            ->withCount([
                'orders as open_orders_count' => fn ($q) => $q->whereNot('status', OrdersState::STATE_CLOSED->value),
                'orders as total_orders_count',
            ])
            ->filter($filters);

        if ($filters['localTable']) {
            $customers = $base->get()->map($transform);
        } else {
            $customers = $base->paginate(25)->withQueryString()->through($transform);
        }

        return inertia('Customer/Index', [
            'filters' => $filters,
            'customers' => $customers,
        ]);
    }

    public function show(Customer $customer)
    {
        $areas = Area::all();

        $customer->load(
            'sites',
            'sites.customer',
            'sites.areas',
            'sites.internalContacts',
            'sites.withdraws',
            'sites.timetable'
        );

        return inertia('Customer/Show', [
            'customer' => $customer,
            'areas' => $areas,
            'orders_by_site' => fn () => Order::query()
                ->whereIn('site_id', $customer->sites()->pluck('id'))
                ->withoutTrashed()
                ->latest()
                ->get()
                ->groupBy('site_id')
                ->map(fn ($c) => $c->values())
                ->toArray(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Customer::class);

        $managers = User::where('role', UserRole::MANAGER->value)->get();
        $jobTypes = array_map(fn ($type) => [
            'value' => $type->value,
            'label' => ucfirst($type->value),
        ], CustomerJobType::cases());

        return inertia('Customer/Create', [
            'managers' => $managers,
            'jobTypes' => $jobTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'isOccasionalCustomer' => 'nullable|boolean',
            'companyName' => 'required|string',
            'vatNumber' => 'required|string',
            'taxCode' => 'required|string',
            'legalAddress' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'sellerId' => 'required|numeric|exists:users,id',
            'sdiCode' => 'required|string',
            'businessType' => 'required|string',
            'salesEmail' => 'required|email',
            'administrativeEmail' => 'required|email',
            'certifiedEmail' => 'required|email',
            'responsabileSmaltimenti' => 'nullable|string',
            'telefonoPrincipale' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'is_occasional_customer' => $validatedData['isOccasionalCustomer'] ?? null,
            'company_name' => $validatedData['companyName'],
            'vat_number' => $validatedData['vatNumber'],
            'tax_code' => $validatedData['taxCode'],
            'seller_id' => $validatedData['sellerId'],
            'legal_address' => $validatedData['legalAddress'],
            'sdi_code' => $validatedData['sdiCode'],
            'business_type' => $validatedData['businessType'],
            'sales_email' => $validatedData['salesEmail'],
            'administrative_email' => $validatedData['administrativeEmail'],
            'certified_email' => $validatedData['certifiedEmail'],
            'responsabile_smaltimenti' => $validatedData['responsabileSmaltimenti'] ?? null,
            'telefono_principale' => $validatedData['telefonoPrincipale'] ?? null,
        ]);

        $customer->sites()->save(new Site([
            'name' => 'Principale',
            'address' => $validatedData['legalAddress'],
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
        ]));

        return redirect()->route('customer.index')->with('success', 'Cliente creato con successo!');
    }

    public function edit(Customer $customer)
    {
        Gate::authorize('update', $customer);

        $customer->load('mainSite');

        $managers = User::where('role', UserRole::MANAGER->value)->get();
        $jobTypes = array_map(fn ($type) => [
            'value' => $type->value,
            'label' => ucfirst($type->value),
        ], CustomerJobType::cases());

        return inertia('Customer/Edit', [
            'customer' => $customer,
            'managers' => $managers,
            'jobTypes' => $jobTypes,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validatedData = $request->validate([
            'isOccasionalCustomer' => 'nullable|boolean',
            'companyName' => 'required|string',
            'vatNumber' => 'required|string',
            'taxCode' => 'required|string',
            'legalAddress' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'sellerId' => 'required|numeric|exists:users,id',
            'sdiCode' => 'required|string',
            'businessType' => 'required|string',
            'salesEmail' => 'required|email',
            'administrativeEmail' => 'required|email',
            'certifiedEmail' => 'required|email',
            'responsabileSmaltimenti' => 'nullable|string',
            'telefonoPrincipale' => 'nullable|string',
        ]);

        $customer->update([
            'is_occasional_customer' => $validatedData['isOccasionalCustomer'] ?? null,
            'company_name' => $validatedData['companyName'],
            'vat_number' => $validatedData['vatNumber'],
            'tax_code' => $validatedData['taxCode'],
            'seller_id' => $validatedData['sellerId'],
            'legal_address' => $validatedData['legalAddress'],
            'sdi_code' => $validatedData['sdiCode'],
            'business_type' => $validatedData['businessType'],
            'sales_email' => $validatedData['salesEmail'],
            'administrative_email' => $validatedData['administrativeEmail'],
            'certified_email' => $validatedData['certifiedEmail'],
            'responsabile_smaltimenti' => $validatedData['responsabileSmaltimenti'] ?? null,
            'telefono_principale' => $validatedData['telefonoPrincipale'] ?? null,
        ]);

        $customer->mainSite()->update([
            'address' => $validatedData['legalAddress'],
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
        ]);

        return redirect()->route('customer.index')->with('success', 'Cliente modificato con successo!');
    }

    public function destroy(Customer $customer)
    {
        Gate::authorize('delete', $customer);
        $customer->deleteOrFail();

        return redirect()->back()->with('success', 'Cliente cancellato con successo');
    }

    public function restore(Customer $customer)
    {
        $customer->restore();

        return redirect()->back()->with('success', 'Cliente ripristinato con successo');
    }
}

