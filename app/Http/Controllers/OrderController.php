<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CerCode;
use App\Models\Holder;
use App\Models\Order;
use App\Models\Site;
use App\Models\Trailer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Services\Planning\OrderWriter;
use App\Support\Audit\AuditTrailPresenter;
use App\Support\CustomerNameSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    private function buildAuditTrail(Order $order): array
    {
        if (! Auth::user()?->is_admin) {
            return [];
        }

        return AuditTrailPresenter::forOrder($order);
    }

    private function normalizeLegacyEpochDate(mixed $value): mixed
    {
        if (! $value) {
            return null;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse($value);

        return $date->year <= 1970 ? null : $date;
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);
        $filters = $request->validate(['customer' => ['nullable', 'string', 'max:200']]);
        $customerSearch = trim($filters['customer'] ?? '');
        $query = Order::query()
            ->with(['site', 'customer', 'items', 'holders'])
            ->when($customerSearch !== '', fn ($query) => $query->whereIn('customer_id', app(CustomerNameSearch::class)->matchingIds($customerSearch)))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $holders = Holder::all();

        return inertia(
            'Order/Index',
            [
                'filters' => ['customer' => $customerSearch],
                'orders' => $query,
                'holders' => $holders,
            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // 1) Recupera il site_id dalla query (?site=123)
        $siteId = $request->integer('site');

        if (! $siteId) {
            // Backward-compat: se proprio vuoi, puoi leggere da sessione/vecchio store qui
            // $siteId = session('current_site_id'); // opzionale
        }

        if (! $siteId) {
            return back()->with('error', 'Seleziona prima una sede (site) per creare un ordine.');
        }

        // 2) Carica il Site con le relazioni utili
        $site = Site::with(['customer', 'timetable', 'internalContacts'])->find($siteId);
        if (! $site) {
            return back()->with('error', 'La sede selezionata non esiste.');
        }

        Gate::authorize('create', Order::class);

        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
        $cerList = CerCode::where('is_active', true)->orderBy('code')->get(['id', 'code', 'description', 'is_dangerous', 'is_active']);
        $warehouses = Warehouse::all();

        return inertia('Order/Create', [
            'site' => $site,                // la sede selezionata
            'customer' => $site->customer,      // comodo in FE
            'vehicles' => $vehicles,
            'trailers' => $trailers,
            'holders' => $holders,
            'drivers' => $drivers,
            'cerList' => $cerList,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Order::class);
        $order = app(OrderWriter::class)->create($request->all());

        return redirect()->route($request->input('post_action') === 'create_stay' ? 'order.edit' : 'customer.show', $request->input('post_action') === 'create_stay' ? ['order' => $order->id] : ['customer' => $order->customer_id])->with('success', 'Ordine inserito con successo!');
    }

    public function edit(Order $order)
    {
        Gate::authorize('update', $order);

        $order->expected_withdraw_at = $this->normalizeLegacyEpochDate($order->expected_withdraw_at);
        $order->fixed_withdraw_at = $this->normalizeLegacyEpochDate($order->fixed_withdraw_at);

        $order_items = $order->items()->with('orderItemGroup:id,order_id,cer_code_id,label')->get();
        $order_holders = $order->holders()->get();
        $site = $order->site()->with('customer')->with('internalContacts')->with('timetable')->first();
        $vehicles = Vehicle::all();
        $trailers = Trailer::all();
        $holders = Holder::all();
        $drivers = User::where('role', UserRole::DRIVER->value )->get();
        $cerList = CerCode::orderBy('code')->get(['id', 'code', 'description', 'is_dangerous', 'is_active']);
        $warehouses = Warehouse::all();

        return inertia(
            'Order/Edit', [
                'order' => $order,
                'order_items' => $order_items,
                'order_holders' => $order_holders,
                'site' => $site,
                'vehicles' => $vehicles,
                'trailers' => $trailers,
                'holders' => $holders,
                'drivers' => $drivers,
                'cerList' => $cerList,
                'warehouses' => $warehouses,
                'audits' => $this->buildAuditTrail($order),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        Gate::authorize('redirectAfterUpdate', $order);
        app(OrderWriter::class)->update($order, $request->all());

        return $request->input('post_action') === 'save_stay'
            ? redirect()->route('order.edit', $order)->with('success', 'Ordine aggiornato con successo!')
            : redirect()->route('order.index')->with('success', 'Ordine aggiornato con successo!');
    }

    public function destroy(Order $order)
    {
        Gate::authorize('delete', $order);
        $order->deleteOrFail();

        return redirect()->back()->with('success', 'Ordine cancellato con successo!');
    }

    public function restore(Order $order)
    {
        $order->restore();

        return redirect()->back()->with('success', 'Ordine ripristinato con successo!');
    }

    /*
        CUSTOM actions for Order life cycle
    */

    public function updateState(Order $order, Request $request)
    {
        Gate::authorize('update', $order);

        $newState = OrderStatus::from($request->new_state);

        if (! OrderStatus::fromMixed($order->status)->canTransitionTo($newState)) {
            abort(403, 'Invalid state transition.');
        }

        // Add lifecycle-specific logic
        switch ($newState) {
            case OrderStatus::STATUS_PLANNED:
                $order->planned_date = $request->planned_date;
                break;

            case OrderStatus::STATUS_EXECUTED:
                $order->executed_at = now();
                break;

            case OrderStatus::STATUS_DOWNLOADED:
                // Attachments or warehouse updates
                $order->downloaded_files = $request->file('attachments')->store('orders');
                break;
        }

        $order->status = $newState->value;
        $order->save();

        return redirect()->back()->with('success', "Order state updated to {$newState->value}.");
    }
}
