<?php

namespace App\Services\Dispatch;

use App\Models\Journey;
use App\Models\JourneyCargo;
use App\Models\JourneyCargoAllocation;
use Illuminate\Support\Facades\DB;

class JourneyWorkspaceInitializer
{
    public function initializeForJourney(Journey $journey): void
    {
        DB::transaction(function () use ($journey) {
            $journey->loadMissing('journeyCargos');

            if ($journey->journeyCargos->isEmpty()) {
                JourneyCargo::query()->create([
                    'journey_id' => $journey->id,
                    'cargo_id' => $journey->vehicle_cargo_id,
                    'cargo_location' => 'vehicle',
                    'warehouse_id' => $journey->primary_warehouse_id,
                    'download_sequence' => 1,
                    'is_grounded' => false,
                    'status' => 'creato',
                ]);

                if (!empty($journey->trailer_id)) {
                    JourneyCargo::query()->create([
                        'journey_id' => $journey->id,
                        'cargo_id' => $journey->trailer_cargo_id,
                        'cargo_location' => 'trailer',
                        'warehouse_id' => $journey->secondary_warehouse_id,
                        'download_sequence' => 2,
                        'is_grounded' => false,
                        'status' => 'creato',
                    ]);
                }
            }

            $hasPlannedAllocations = JourneyCargoAllocation::query()
                ->where('journey_id', $journey->id)
                ->where('source', 'planned')
                ->exists();

            if ($hasPlannedAllocations) {
                return;
            }

            $cargosByLocation = JourneyCargo::query()
                ->where('journey_id', $journey->id)
                ->get(['id', 'cargo_location'])
                ->keyBy('cargo_location');

            $vehicleCargoId = (int) ($cargosByLocation->get('vehicle')?->id ?? 0);
            $trailerCargoId = (int) ($cargosByLocation->get('trailer')?->id ?? 0);

            if ($vehicleCargoId <= 0 && $trailerCargoId <= 0) {
                return;
            }

            $plannedItems = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->where('o.journey_id', $journey->id)
                ->select([
                    'oi.id as order_item_id',
                    'oi.holder_quantity',
                    'o.cargo_location',
                ])
                ->get();

            foreach ($plannedItems as $item) {
                $containers = max(0, (int) $item->holder_quantity);
                if ($containers <= 0) {
                    continue;
                }

                $plannedCargoId = 0;
                if ($item->cargo_location === 'trailer' && $trailerCargoId > 0) {
                    $plannedCargoId = $trailerCargoId;
                } elseif ($item->cargo_location === 'vehicle' && $vehicleCargoId > 0) {
                    $plannedCargoId = $vehicleCargoId;
                } elseif ($vehicleCargoId > 0) {
                    $plannedCargoId = $vehicleCargoId;
                } elseif ($trailerCargoId > 0) {
                    $plannedCargoId = $trailerCargoId;
                }

                if ($plannedCargoId <= 0) {
                    continue;
                }

                JourneyCargoAllocation::query()->updateOrCreate(
                    [
                        'journey_id' => $journey->id,
                        'journey_cargo_id' => $plannedCargoId,
                        'order_item_id' => (int) $item->order_item_id,
                        'source' => 'planned',
                    ],
                    [
                        'allocated_containers' => $containers,
                        'estimated_weight_kg' => null,
                        'is_exception' => false,
                        'exception_reason' => null,
                        'created_by_user_id' => null,
                        'updated_by_user_id' => null,
                    ]
                );
            }
        });
    }
}

