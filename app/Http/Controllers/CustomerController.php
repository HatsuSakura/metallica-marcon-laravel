<?php

namespace App\Http\Controllers;

use App\Enums\CustomerJobType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Site;
use App\Models\User;
use App\Support\Audit\AuditTrailPresenter;
use App\Services\CalculateRiskService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    private function resolveReturnTo(?string $returnTo, string $fallback): string
    {
        if (!is_string($returnTo) || trim($returnTo) === '') {
            return $fallback;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $isSafeReturnTo = Str::startsWith($returnTo, '/')
            || ($appUrl !== '' && Str::startsWith($returnTo, $appUrl));

        return $isSafeReturnTo ? $returnTo : $fallback;
    }

    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request)
    {
        $filters = [
            'deleted' => $request->boolean('deleted'),
            'continuativo' => $request->boolean('continuativo', true),
            'occasionale' => $request->boolean('occasionale'),
            'localTable' => $request->boolean('local_table', false),
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
                'orders as open_orders_count' => fn ($q) => $q->whereNot('status', OrderStatus::STATUS_CLOSED->value),
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

        $customer->makeHidden([
            'responsabile_smaltimenti',
            'telefono_principale',
        ]);

        $customer->load(
            'sites',
            'sites.customer',
            'sites.areas',
            'sites.internalContacts',
            'sites.withdraws',
            'sites.withdraws.driver',
            'sites.withdraws.vehicle',
            'sites.timetable'
        );

        return inertia('Customer/Show', [
            'customer' => $customer,
            'areas' => $areas,
            'audits' => auth()->user()?->is_admin ? AuditTrailPresenter::forCustomer($customer, 100) : [],
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
            'is_occasional_customer' => 'nullable|boolean',
            'company_name' => 'required|string',
            'vat_number' => 'required|string',
            'tax_code' => 'required|string',
            'legal_address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'seller_id' => 'required|numeric|exists:users,id',
            'sdi_code' => 'required|string',
            'business_type' => ['required', 'string', Rule::in(array_column(CustomerJobType::cases(), 'value'))],
            'sales_email' => 'nullable|email',
            'administrative_email' => 'nullable|email',
            'certified_email' => 'required|email',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create([
            'is_occasional_customer' => $validatedData['is_occasional_customer'] ?? null,
            'company_name' => $validatedData['company_name'],
            'vat_number' => $validatedData['vat_number'],
            'tax_code' => $validatedData['tax_code'],
            'seller_id' => $validatedData['seller_id'],
            'legal_address' => $validatedData['legal_address'],
            'sdi_code' => $validatedData['sdi_code'],
            'business_type' => $validatedData['business_type'],
            'sales_email' => $validatedData['sales_email'] ?? null,
            'administrative_email' => $validatedData['administrative_email'] ?? null,
            'certified_email' => $validatedData['certified_email'],
            'notes' => $validatedData['notes'] ?? null,
        ]);

        $customer->sites()->save(new Site([
            'name' => 'Principale',
            'address' => $validatedData['legal_address'],
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
        ]));

        return redirect()->route('customer.index')->with('success', 'Cliente creato con successo!');
    }

    public function edit(Request $request, Customer $customer)
    {
        Gate::authorize('update', $customer);

        $customer->load([
            'mainSite',
            'sites' => fn ($query) => $query
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->select('id', 'customer_id', 'name', 'is_main', 'address', 'latitude', 'longitude', 'notes', 'has_muletto', 'has_electric_pallet_truck', 'has_manual_pallet_truck', 'other_machines', 'has_adr_consultant'),
        ]);

        $managers = User::where('role', UserRole::MANAGER->value)->get();
        $jobTypes = array_map(fn ($type) => [
            'value' => $type->value,
            'label' => ucfirst($type->value),
        ], CustomerJobType::cases());

        $fallbackReturnTo = route('customer.index');
        $returnTo = $this->resolveReturnTo($request->query('return_to'), $fallbackReturnTo);

        return inertia('Customer/Edit', [
            'customer' => $customer,
            'managers' => $managers,
            'jobTypes' => $jobTypes,
            'returnTo' => $returnTo,
            'audits' => auth()->user()?->is_admin ? AuditTrailPresenter::forCustomer($customer, 100) : [],
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validatedData = $request->validate([
            'is_occasional_customer' => 'nullable|boolean',
            'company_name' => 'required|string',
            'vat_number' => 'required|string',
            'tax_code' => 'required|string',
            'legal_address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'seller_id' => 'required|numeric|exists:users,id',
            'sdi_code' => 'required|string',
            'business_type' => ['required', 'string', Rule::in(array_column(CustomerJobType::cases(), 'value'))],
            'sales_email' => 'nullable|email',
            'administrative_email' => 'nullable|email',
            'certified_email' => 'required|email',
            'notes' => 'nullable|string',
            'return_to' => 'nullable|string',
        ]);

        $customer->update([
            'is_occasional_customer' => $validatedData['is_occasional_customer'] ?? null,
            'company_name' => $validatedData['company_name'],
            'vat_number' => $validatedData['vat_number'],
            'tax_code' => $validatedData['tax_code'],
            'seller_id' => $validatedData['seller_id'],
            'legal_address' => $validatedData['legal_address'],
            'sdi_code' => $validatedData['sdi_code'],
            'business_type' => $validatedData['business_type'],
            'sales_email' => $validatedData['sales_email'] ?? null,
            'administrative_email' => $validatedData['administrative_email'] ?? null,
            'certified_email' => $validatedData['certified_email'],
            'notes' => $validatedData['notes'] ?? null,
        ]);

        $customer->mainSite()->update([
            'address' => $validatedData['legal_address'],
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
        ]);

        $fallbackReturnTo = route('customer.index');
        $returnTo = $this->resolveReturnTo($validatedData['return_to'] ?? null, $fallbackReturnTo);

        return redirect()->to($returnTo)->with('success', 'Cliente modificato con successo!');
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

    public function recalculateRisk(Customer $customer, CalculateRiskService $calculateRiskService)
    {
        Gate::authorize('update', $customer);

        $updatedSites = $calculateRiskService->recalculateCustomerRisk([
            'customerId' => (int) $customer->id,
        ]);

        return response()->json([
            'message' => 'Customer risk recalculated successfully.',
            'sites' => collect($updatedSites)->map(fn (Site $site) => [
                'id' => $site->id,
                'calculated_risk_factor' => $site->calculated_risk_factor,
                'days_until_next_withdraw' => $site->days_until_next_withdraw,
            ])->values(),
        ], 200);
    }
}
