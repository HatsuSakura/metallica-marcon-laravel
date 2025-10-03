<?php
// app/Services/JourneyCargoService.php

namespace App\Services;

use App\Models\Journey;
use App\Models\JourneyCargo;
use App\Models\OrderItem;
use App\Enums\OrderItemsState;
use App\Enums\OrdersTruckLocation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JourneyCargoService
{
    public function createCargoForJourney(Journey $journey, array $truckData, array $trailerData): array
    {
        return DB::transaction(function () use ($journey, $truckData, $trailerData) {

            // ATTENZIONE: assicurati che questi value coincidano col DB ('vehicle'/'trailer' oppure 'motrice'/'rimorchio')
            $locTruck   = OrdersTruckLocation::TRUCK_MOTRICE->value;   // es. 'vehicle'
            $locTrailer = OrdersTruckLocation::TRUCK_RIMORCHIO->value; // es. 'trailer'

            // Crea sempre il cassone TRUCK
            $truckCargo = JourneyCargo::create([
                'journey_id'        => $journey->id,
                'cargo_id'          => $journey->cargo_for_vehicle_id,
                'truck_location'    => $locTruck,
                'warehouse_id'      => $truckData['warehouse_id'],
                'is_grounding'      => (bool) ($truckData['is_grounding'] ?? false),
                'download_sequence' => $truckData['download_sequence'],
                'state'             => 'creato',
            ]);

            // 1) Sincronizza gli item in pivot (crea o aggiorna senza staccare gli altri)
            $truckMap = $this->buildPivotMap($truckData['items'] ?? []);
            if (!empty($truckMap)) {
                $truckCargo->items()->syncWithoutDetaching($truckMap);
                // 2) Applica la transizione di stato sugli item coinvolti
                $this->transitionItemsToLoaded(array_keys($truckMap));
            }

            // Trailer opzionale: crealo solo se c'è un warehouse valido
            $trailerCargo = null;
            if (!empty($trailerData) && !empty($trailerData['warehouse_id'])) {
                $trailerCargo = JourneyCargo::create([
                    'journey_id'        => $journey->id,
                    'cargo_id'          => $journey->cargo_for_trailer_id,
                    'truck_location'    => $locTrailer,
                    'warehouse_id'      => $trailerData['warehouse_id'],
                    'is_grounding'      => (bool) ($trailerData['is_grounding'] ?? false),
                    'download_sequence' => $trailerData['download_sequence'],
                    'state'             => 'creato',
                ]);

                $trailerMap = $this->buildPivotMap($trailerData['items'] ?? []);
                if (!empty($trailerMap)) {
                    $trailerCargo->items()->syncWithoutDetaching($trailerMap);
                    $this->transitionItemsToLoaded(array_keys($trailerMap));
                }
            }

            Log::info('Created cargos', [
                'journey_id' => $journey->id,
                'truck_cargo_id' => $truckCargo->id,
                'trailer_cargo_id' => $trailerCargo?->id,
            ]);

            return [$truckCargo->fresh('items'), $trailerCargo?->fresh('items')];
        });
    }

    public function updateCargoForJourney(Journey $journey, array $truckData, array $trailerData): array
    {
        return DB::transaction(function () use ($journey, $truckData, $trailerData) {

            // TRUCK
            $truckCargo = JourneyCargo::query()->findOrFail($truckData['journey_cargo_id']);
            $truckCargo->update([
                'warehouse_id'      => $truckData['warehouse_id'],
                'is_grounding'      => (bool) ($truckData['is_grounding'] ?? false),
                'download_sequence' => $truckData['download_sequence'],
            ]);

            $truckMap = $this->buildPivotMap($truckData['items'] ?? []);
            if (!empty($truckMap)) {
                // upsert pivot (crea se non c'è, aggiorna se c'è)
                $truckCargo->items()->sync($truckMap, false); // upsert + update pivot
                $this->transitionItemsToLoaded(array_keys($truckMap));
            }

            // TRAILER (se presente)
            $trailerCargo = null;
            if (!empty($trailerData) && !empty($trailerData['journey_cargo_id'])) {
                $trailerCargo = JourneyCargo::query()->findOrFail($trailerData['journey_cargo_id']);
                $trailerCargo->update([
                    'warehouse_id'      => $trailerData['warehouse_id'],
                    'is_grounding'      => (bool) ($trailerData['is_grounding'] ?? false),
                    'download_sequence' => $trailerData['download_sequence'],
                ]);

                $trailerMap = $this->buildPivotMap($trailerData['items'] ?? []);
                if (!empty($trailerMap)) {
                    $trailerCargo->items()->sync($trailerMap, false);
                    $this->transitionItemsToLoaded(array_keys($trailerMap));
                }
            }

            return [$truckCargo->fresh('items'), $trailerCargo?->fresh('items')];
        });
    }

    /**
     * Accetta sia:
     *  - [12, 34]
     *  - [['id'=>12,'is_double_load'=>true,'warehouse_download_id'=>3], ...]
     *  - [['order_item_id'=>12, ...], ...]
     */
    private function buildPivotMap(array $items): array
    {
        $map = [];

        foreach ($items as $row) {
            // id: root id o order_item_id o pivot.order_item_id
            $id =   Arr::get($row, 'id',
                    Arr::get($row, 'order_item_id',
                    Arr::get($row, 'pivot.order_item_id')));
            if (!$id) continue;

            // is_double_load: preferisci root-level, altrimenti pivot
            $isDouble = Arr::get($row, 'is_double_load',
                        Arr::get($row, 'pivot.is_double_load', 0));
            $isDouble = (int) !!$isDouble;

            // warehouse_download_id: preferisci root-level, altrimenti pivot
            $wd =   Arr::get($row, 'warehouse_download_id',
                    Arr::get($row, 'pivot.warehouse_download_id'));

            if ($wd === '' || $wd === false) {
                $wd = null;
            } elseif ($wd !== null) {
                $wd = (int) $wd;
            }

            $map[(int)$id] = [
                'is_double_load'        => $isDouble,
                'warehouse_download_id' => $wd,
            ];
        }

        return $map;
    }


    /**
     * Carica DAVVERO gli OrderItem e applica la transizione di stato.
     */
    private function transitionItemsToLoaded(array $ids): void
    {
        if (empty($ids)) return;

        $items = OrderItem::whereIn('id', $ids)->get(['id','state']);
        foreach ($items as $item) {
            $current = OrderItemsState::from($item->state);
            if ($current->canTransitionTo(OrderItemsState::STATE_LOADED)) {
                $item->state = OrderItemsState::STATE_LOADED;
                $item->save();
            } else {
                Log::warning("Invalid state transition from {$current->value} for order_item {$item->id}");
            }
        }
    }
}
