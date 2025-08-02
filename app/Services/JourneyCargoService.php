<?php
// app/Services/JourneyCargoService.php

namespace App\Services;

use App\Models\Cargo;
use App\Models\Journey;
use App\Models\OrderItem;
use App\Models\JourneyCargo;
use App\Enums\OrderItemsState;
use App\Enums\OrdersTruckLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JourneyCargoService
{
    /**
     * Create the two JourneyCargo objects associated with a Journey.
     *
     * @param Journey $journey
     * @param array $truckData
     * @param array $trailerData
     * @return array
     */
    public function createCargoForJourney(Journey $journey, array $truckData, array $trailerData): array
    {
        return DB::transaction(function () use ($journey, $truckData, $trailerData) {

            // Check if the mandatory truck cargo already exists
            $existingTruckCargo = JourneyCargo::where('journey_id', $journey->id)
            ->where('truck_location', OrdersTruckLocation::TRUCK_MOTRICE->value)
            ->first();
        
            if ($existingTruckCargo) {
                // Option 1: Throw an exception so that the controller can handle it (e.g., show an error message)
                throw new \Exception('Cargo for the truck has already been created for this journey.');
            
                // Option 2: Alternatively, you could simply return the existing cargos:
                // $existingTrailerCargo = JourneyCargo::where('journey_id', $journey->id)
                //     ->where('truck_location', OrdersTruckLocation::TRUCK_RIMORCHIO->value)
                //     ->first();
                // return [$existingTruckCargo, $existingTrailerCargo];
            }
        

            
            // ALWAYS CREATE CARGO FOR TRUCK (VEHICLE)
            $truckCargo = JourneyCargo::create([
                'journey_id'        => $journey->id,
                'cargo_id'          => $journey->cargo_for_vehicle_id,
                'truck_location'    => OrdersTruckLocation::TRUCK_MOTRICE->value,
                'warehouse_id'      => $truckData['warehouse_id'], 
                'is_grounding'      => $truckData['is_grounding'],
                'download_sequence' => $truckData['download_sequence'],
            ]);

            // Get all the OrderItems whit the ID in the truck (vehicle) ARRAY
            //$items = OrderItem::whereIn('id', $truckData['items'])->get();
            $items = OrderItem::hydrate( $truckData['items'] );
            Log::info("VEHICLE ITEMS: {$items}");

            foreach ($items as $item) {
                $currentState = OrderItemsState::from($item->state);
                if ($currentState->canTransitionTo(OrderItemsState::STATE_LOADED)) {

                    $pivotData = [
                        'is_double_load'      => isset($item->pivot) && isset($item->pivot->is_double_load)
                                                   ? (bool)$item->pivot->is_double_load
                                                   : false,
                        'warehouse_download_id' => isset($item->pivot) && isset($item->pivot->warehouse_download_id)
                                                   ? $item->pivot->warehouse_download_id
                                                   : null,
                    ];
        
                    Log::info("Pivot data for OrderItem TRUCK {$item->id}: ", $pivotData);
                    $truckCargo->items()->syncWithoutDetaching([
                        $item->id => [
                            'is_double_load'       => $item->pivot['is_double_load'] ?? false,
                            'warehouse_download_id' => $item->pivot['warehouse_download_id'] ?? null,
                        ]
                    ]);
                    $item->state = OrderItemsState::STATE_LOADED;
                    //$item->journey_cargo_id = $truckCargo->id;
                    $item->save();


                } else {
                    Log::warning("Invalid state transition from {$currentState->value} for order ID {$item->id}");
                }
            }

            // IF EXISTS, CREATE CARGO FOR TRAILER
            $trailerCargo = null;
            if (!empty($trailerData)) {
                $trailerCargo = JourneyCargo::create([
                    'journey_id'        => $journey->id,
                    'cargo_id'          => $journey->cargo_for_trailer_id,
                    'truck_location'    => OrdersTruckLocation::TRUCK_RIMORCHIO->value,
                    'warehouse_id'      => $trailerData['warehouse_id'],
                    'is_grounding'      => $trailerData['is_grounding'],
                    'download_sequence' => $trailerData['download_sequence'],
                ]);
            
                //$items = OrderItem::whereIn('id', $trailerData['items'])->get();
                $items = OrderItem::hydrate( $trailerData['items'] );
                Log::info("ITEMS: {$items}");
    
                foreach ($items as $item) {
                    //$currentState = $item->state;
                    $currentState = OrderItemsState::from($item->state);
                    if ($currentState instanceof OrderItemsState ){
                        Log::info("CURRENT STATE: {$item->state}");
                    }
                    Log::info("Transition check from {$currentState->value} to " . OrderItemsState::STATE_LOADED->value);
    
                    $canTransition = $currentState->canTransitionTo(OrderItemsState::STATE_LOADED);
                    Log::info("Can transition? " . ($canTransition ? 'Yes' : 'No'));
    
    
                    if ($currentState->canTransitionTo(OrderItemsState::STATE_LOADED)) {

                        $trailerCargo->items()->syncWithoutDetaching([
                            $item->id => [
                                'is_double_load'       => $item->pivot['is_double_load'] ?? false,
                                'warehouse_download_id' => $item->pivot['warehouse_download_id'] ?? null,
                            ]
                        ]);
                        $item->state = OrderItemsState::STATE_LOADED;
                        //$item->journey_cargo_id = $trailerCargo->id;
                        $item->save();
                    } else {
                        Log::warning("Invalid state transition from {$currentState->value} for order ID {$item->id}");
                    }
                }

            }
            return [$truckCargo, $trailerCargo];
        });
    }

    /**
     * Update the JourneyCargo objects associated with a Journey.
     *
     * @param Journey $journey
     * @param array $truckData
     * @param array $trailerData
     * @return array
     */
    public function updateCargoForJourney(Journey $journey, array $truckData, array $trailerData): array
    {
        return DB::transaction(function () use ($journey, $truckData, $trailerData) {
            
            // ALWAYS UPDATE CARGO FOR TRUCK (VEHICLE)
            $truckCargo = JourneyCargo::where('id', $truckData['journey_cargo_id'] )->first();
            $truckCargo->update([
                'warehouse_id'      => $truckData['warehouse_id'], 
                'is_grounding'      => $truckData['is_grounding'],
                'download_sequence' => $truckData['download_sequence'],
            ]);
            $truckCargo->save();

            // Get all the OrderItems whit the ID in the truck (vehicle) ARRAY
            //$items = OrderItem::whereIn('id', $truckData['items'])->get();
            $items = OrderItem::hydrate( $truckData['items'] );

            foreach ($items as $item) {
                $currentState = OrderItemsState::from($item->state);
                if ($currentState == OrderItemsState::STATE_LOADED) {
                    $truckCargo->items()->updateExistingPivot($item->id, [
                        'is_double_load'       => $item->pivot['is_double_load'] ?? false,
                        'warehouse_download_id' => $item->pivot['warehouse_download_id'] ?? null,
                    ]);
                    $item->save();
                } else {
                    Log::warning("Invalid state transition from {$currentState->value} for order ID {$item->id}");
                }
            }

            // IF EXISTS, CREATE CARGO FOR TRAILER
            $trailerCargo = null;
            if (!empty($trailerData)) {

                $trailerCargo = JourneyCargo::where('id', $trailerData['journey_cargo_id'] )->first();
                $trailerCargo->update([
                    'warehouse_id'      => $trailerData['warehouse_id'], 
                    'is_grounding'      => $trailerData['is_grounding'],
                    'download_sequence' => $trailerData['download_sequence'],
                ]);
                $trailerCargo->save();
            
                //$items = OrderItem::whereIn('id', $trailerData['items'])->get();
                $items = OrderItem::hydrate( $trailerData['items'] );
                //$items = $trailerData['items']; 

                foreach ($items as $item) {
                    //$currentState = $item->state;
                    $currentState = OrderItemsState::from($item->state);
                    if ($currentState == OrderItemsState::STATE_LOADED) {
                        $trailerCargo->items()->updateExistingPivot($item->id, [
                            'is_double_load'       => $item->pivot['is_double_load'] ?? false,
                            'warehouse_download_id' => $item->pivot['warehouse_download_id'] ?? null,
                        ]);
                        $item->save();
                    } else {
                        Log::warning("Invalid state transition from {$currentState->value} for order ID {$item->id}");
                    }
                }
            }

            return [$truckCargo, $trailerCargo];
        });
    }
}
