<?php

namespace App\Services\Dispatch;

use App\Models\Journey;
use App\Models\JourneyEvent;
use Illuminate\Support\Facades\DB;

class JourneyDispatchPlanService
{
    public function setPlan(Journey $journey, array $data, ?int $actorUserId): Journey
    {
        return DB::transaction(function () use ($journey, $data, $actorUserId) {
            $journey = Journey::query()
                ->whereKey($journey->id)
                ->lockForUpdate()
                ->firstOrFail();

            $journey->is_double_load = (bool) ($data['is_double_load'] ?? false);
            $journey->is_temporary_storage = (bool) ($data['is_temporary_storage'] ?? false);
            $journey->primary_warehouse_id = $data['primary_warehouse_id'] ?? null;
            $journey->secondary_warehouse_id = $data['secondary_warehouse_id'] ?? null;
            $journey->primary_warehouse_download_at = $data['primary_warehouse_download_at'] ?? null;
            $journey->secondary_warehouse_download_at = $data['secondary_warehouse_download_at'] ?? null;
            $journey->plan_version = (int) ($journey->plan_version ?? 0) + 1;
            $journey->save();

            JourneyEvent::create([
                'journey_id' => $journey->id,
                'status' => (string) $journey->status,
                'payload' => [
                    'event' => 'dispatch_plan_updated',
                    'plan_version' => $journey->plan_version,
                    'is_double_load' => $journey->is_double_load,
                    'is_temporary_storage' => $journey->is_temporary_storage,
                    'primary_warehouse_id' => $journey->primary_warehouse_id,
                    'secondary_warehouse_id' => $journey->secondary_warehouse_id,
                ],
                'created_by_user_id' => $actorUserId,
            ]);

            return $journey->fresh([
                'driver:id,name,surname',
                'vehicle:id,plate,name',
                'trailer:id,plate,name',
                'primaryWarehouse:id,name',
                'secondaryWarehouse:id,name',
            ]);
        });
    }

    public function addOperationalEvent(Journey $journey, string $eventCode, ?string $notes, ?int $actorUserId): void
    {
        JourneyEvent::create([
            'journey_id' => $journey->id,
            'status' => (string) $journey->status,
            'payload' => [
                'event' => $eventCode,
                'notes' => $notes,
            ],
            'created_by_user_id' => $actorUserId,
        ]);
    }
}
