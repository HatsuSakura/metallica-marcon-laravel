<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cargo;
use App\Models\Order;
use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\Trailer;
use App\Models\Vehicle;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Enums\OrderDocumentsStatus;
use App\Enums\JourneyStatus;
use App\Models\JourneyCargo;
use Illuminate\Http\Request;
use App\Enums\OrderStatus;
use App\Enums\OrdersTruckLocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Services\JourneyCargoService;

class JourneyCargoController extends Controller
{

    protected $journeyCargoService;
    public function __construct(JourneyCargoService $journeyCargoService)
    {
        $this->journeyCargoService = $journeyCargoService;
    }


    public function index(Request $request){

        $query = Journey::query()
        //->alphabetic()
        //->withCount('orders')
        //->filter($filters)
        ->with('driver')
        ->with('vehicle')
        ->with('trailer')
        ->paginate(25)
        ->withQueryString();


        return inertia(
            'Journey/Index',
            [
                //'filters' => $filters,
                'journeys' => $query
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(JourneyCargo $journeyCargo)
    {
        //Gate::authorize('view', $journeyCargo);

        return inertia(
            'JourneyCargo/Show',
            [
                'journeyCargo' => $journeyCargo->load('items', 'items.holder', 'items.cerCode', 'cargo', 'journey', 'warehouse'),
            ]
        );
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(Journey $journey)
    {
        Gate::authorize('create', Journey::class);

        // Fetch trucks and drivers (assuming Driver is a special type of User)
        $warehouses = Warehouse::all();
        $orders = Order::query()
            ->where('journey_id', $journey->id)
            ->with([
                'logistic:id,name',
                'customer:id,company_name',
                'site',
                'items',
                'items.cerCode:id,code,is_dangerous',
                'items.holder:id,name,volume',
                'items.warehouse:id,name',
                'items.order:id,customer_id',
                'items.order.customer:id,company_name'
            ])
            ->get();

        return inertia('JourneyCargo/Create', 
        [
            'journey' => $journey->load('driver', 'vehicle', 'cargoForVehicle', 'trailer', 'cargoForTrailer'),
            'warehouses' => $warehouses,
            'orders' => $orders,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Journey $journey)
    {
        $validated = $request->validate([
            'journey_id'  => 'required',
            'logistics_user_id' => 'nullable',
            'warehouse_id_truck' => 'required|numeric',
            'is_grounding_truck' => 'required|boolean',
            'download_sequence_truck' => 'required|numeric',
            'warehouse_id_trailer' => 'nullable|numeric',
            'is_grounding_trailer' => 'nullable|boolean',
            'download_sequence_trailer' => 'nullable|numeric',
            'items_truck'    => 'nullable|array',
            'items_trailer'  => 'nullable|array',
            'items_fulfill' => 'nullable|array',
        ]);

        $journey = Journey::findOrFail($request->input('journey_id'));

        $existingTruckCargo = JourneyCargo::where('journey_id', $journey->id)
            ->where('cargo_location', OrdersTruckLocation::TRUCK_MOTRICE->value)
            ->first();
        $existingTrailerCargo = JourneyCargo::where('journey_id', $journey->id)
            ->where('cargo_location', OrdersTruckLocation::TRUCK_RIMORCHIO->value)
            ->first();

        // Build the complex data arrays
        $truckData = [
            'journey_cargo_id'  => optional($existingTruckCargo)->id, // returns null if not found
            'warehouse_id'      => $validated['warehouse_id_truck'],
            'items'             => $validated['items_truck'] ?? [],
            'is_grounded'       => $validated['is_grounding_truck'] ?? false,
            'download_sequence' => $validated['download_sequence_truck'],
        ];

        $trailerData = [
            'journey_cargo_id'  => optional($existingTrailerCargo)->id, // returns null if not found
            'warehouse_id'      => $validated['warehouse_id_trailer'] ?? null,
            'items'             => $validated['items_trailer'] ?? [],
            'is_grounded'       => $validated['is_grounding_trailer'] ?? false,
            'download_sequence' => $validated['download_sequence_trailer'] ?? null,
        ];

        if(!$existingTruckCargo){
            // Use the service to create the cargo objects
            $cargoes = $this->journeyCargoService->createCargoForJourney($journey, $truckData, $trailerData);
        }
        else{
            // Use the service to update the cargo objects
            $cargoes = $this->journeyCargoService->updateCargoForJourney($journey, $truckData, $trailerData);
        }
        
/*
        // Return a response (could be an Inertia response, JSON, or redirect)
        return response()->json([
            'message' => 'Journey cargos created successfully',
            'data' => $cargoes,
        ]);
*/
        return redirect()->route('dashboard')->with('success', 'Gestione cassoni aggiornata con successo');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Journey $journey)
    {
        Gate::authorize('update', $journey);

        $warehouses = Warehouse::all();
        $orders = Order::query()
        ->where('journey_id', $journey->id)
        ->with([
            'logistic:id,name',
                'customer:id,company_name',
            'site',
            'items',
            'items.cerCode:id,code,is_dangerous',
            'items.holder:id,name,volume',
            'items.warehouse:id,name',
            'items.order:id,customer_id',
            'items.order.customer:id,company_name'
        ])
        ->get();

        $journeyCargos = JourneyCargo::query()
        ->where('journey_id', $journey->id)
        ->with([
            //'logistic:id,name',
            'items',
            'items.cerCode:id,code,is_dangerous',
            'items.holder:id,name,volume',
            'items.warehouse:id,name',
            'items.order:id,customer_id',
            'items.order.customer:id,company_name',
        ])
        ->get();

        return inertia(
            'JourneyCargo/Edit',
            [
                'journey' => $journey->load('driver', 'vehicle', 'cargoForVehicle', 'trailer', 'cargoForTrailer', 'journeyCargos'),
                'warehouses' => $warehouses,
                'orders' => $orders,
                'journeyCargos' => $journeyCargos,
            ]
        );
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function manage(JourneyCargo $journeyCargo)
    {
        //Gate::authorize('update', $journeyCargo);

        $otherJourneyCargo = $journeyCargo->otherCargo();

        $warehouseWorkers = User::query()
        ->where('role', UserRole::WAREHOUSE_WORKER)
        ->get();

        $warehouseManagers = User::query()
        ->where('role', UserRole::WAREHOUSE_MANAGER)
        ->get();

        return inertia(
            'JourneyCargo/Manage',
            [
                'journeyCargo'      => $journeyCargo->load('cargo', 'journey', 'warehouse'),
                'journeyCargoItems' => $journeyCargo->load('items', 'items.holder', 'items.warehouse', 'items.cerCode', 'items.order.customer'),
                'otherJourneyCargo'      => $otherJourneyCargo->load('cargo', 'journey', 'warehouse'),
                'otherJourneyCargoItems' => $otherJourneyCargo->load('items', 'items.holder', 'items.warehouse', 'items.cerCode',  'items.order.customer'),
                'warehouseWorkers' => $warehouseWorkers,
                'warehouseManagers' => $warehouseManagers,
            ]
        );
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JourneyCargo $journeyCargo)
    {
        $journeyCargo->update([
            $request->validate([
                'customer_id'=> 'required',
                'site_id'=> 'required',
                'logistics_user_id' => 'nullable',
                'items' => 'nullable|array', // Make items optional
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ])
        ]);

        //return redirect()->route('journey.index')->with('success', 'Ritiro modificato con successo!');
        return redirect()->back()->with('success', 'Cassone aggiornato con successo!');
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
                'status' => $this->statusAfterJourneyDetach($order),
                'cargo_location' => null,
                'documents_status' => OrderDocumentsStatus::NOT_GENERATED->value,
                'documents_generated_at' => null,
                'documents_error' => null,
            ]);
        }

        $journey->deleteOrFail();

        return redirect()->back()->with('success', 'Viaggio cancellato con successo!');
    }

    public function restore(Journey $journey){
        $journey->restore();
        return redirect()->back()->with('success', 'Viaggio ripristinato con successo!');
    }

    private function statusAfterJourneyDetach(Order $order): string
    {
        return OrderStatus::STATUS_CREATED->value;
    }




}







