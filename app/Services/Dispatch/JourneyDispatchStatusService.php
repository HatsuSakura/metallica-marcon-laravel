<?php

namespace App\Services\Dispatch;

use App\Enums\DispatchStatus;
use App\Models\Journey;
use App\Models\JourneyEvent;

class JourneyDispatchStatusService
{
    public const STATUS_PENDING = DispatchStatus::PENDING->value;
    public const STATUS_IN_PROGRESS = DispatchStatus::IN_PROGRESS->value;
    public const STATUS_ON_HOLD = DispatchStatus::ON_HOLD->value;
    public const STATUS_MANAGED = DispatchStatus::MANAGED->value;

    public function resolveCurrentStatus(Journey $journey): string
    {
        $snapshotStatus = $journey->dispatch_status;
        $resolved = DispatchStatus::tryFromMixed($snapshotStatus);
        if ($resolved instanceof DispatchStatus) {
            return $resolved->value;
        }

        $eventCode = JourneyEvent::query()
            ->where('journey_id', $journey->id)
            ->orderByDesc('id')
            ->get(['payload'])
            ->map(fn ($row) => is_array($row->payload) ? ($row->payload['event'] ?? null) : null)
            ->first(fn ($event) => is_string($event) && $this->isDispatchEvent($event));

        return $this->statusFromEvent($eventCode);
    }

    public function resolveForJourneyIds(array $journeyIds): array
    {
        if (empty($journeyIds)) {
            return [];
        }

        $statusByJourney = Journey::query()
            ->whereIn('id', $journeyIds)
            ->get(['id', 'dispatch_status'])
            ->mapWithKeys(function (Journey $journey) {
                $value = DispatchStatus::tryFromMixed($journey->dispatch_status);
                if ($value instanceof DispatchStatus) {
                    return [(int) $journey->id => $value->value];
                }
                return [(int) $journey->id => null];
            })
            ->all();

        JourneyEvent::query()
            ->whereIn('journey_id', $journeyIds)
            ->orderByDesc('id')
            ->get(['journey_id', 'payload'])
            ->each(function ($event) use (&$statusByJourney) {
                $journeyId = (int) $event->journey_id;
                if (isset($statusByJourney[$journeyId])) {
                    return;
                }

                $eventCode = is_array($event->payload) ? ($event->payload['event'] ?? null) : null;
                if (!is_string($eventCode) || !$this->isDispatchEvent($eventCode)) {
                    return;
                }

                $statusByJourney[$journeyId] = $this->statusFromEvent($eventCode);
            });

        foreach ($journeyIds as $journeyId) {
            $journeyId = (int) $journeyId;
            if (!array_key_exists($journeyId, $statusByJourney)) {
                $statusByJourney[$journeyId] = self::STATUS_PENDING;
            }
        }

        return $statusByJourney;
    }

    private function isDispatchEvent(string $eventCode): bool
    {
        return in_array($eventCode, ['dispatch_hold', 'dispatch_resume', 'dispatch_plan_updated'], true);
    }

    private function statusFromEvent(?string $eventCode): string
    {
        if ($eventCode === 'dispatch_hold') {
            return self::STATUS_ON_HOLD;
        }

        if ($eventCode === 'dispatch_resume' || $eventCode === 'dispatch_plan_updated') {
            return self::STATUS_IN_PROGRESS;
        }

        return self::STATUS_PENDING;
    }
}
