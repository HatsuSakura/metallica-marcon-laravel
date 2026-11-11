<?php

namespace App\Services\JourneyExecution;

use App\Enums\JourneyStatus;
use App\Models\Journey;
use App\Models\JourneyEvent;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

class JourneySiteNotes
{
    public static function version(?string $notes): string
    {
        return hash('sha256', json_encode($notes, JSON_THROW_ON_ERROR));
    }

    public function update(Journey $journey, Site $site, int $actor, ?string $notes, string $version): void
    {
        DB::transaction(function () use ($journey, $site, $actor, $notes, $version) {
            $lockedJourney = Journey::lockForUpdate()->findOrFail($journey->id);
            abort_unless($lockedJourney->status === JourneyStatus::STATUS_ACTIVE && $lockedJourney->baseline()->exists(), 422, 'Le note della sede sono modificabili durante un viaggio in corso.');
            $orders = $lockedJourney->orderExecutions()->whereIn('status', ['pending', 'completed'])->get(['id', 'journey_stop_id', 'data']);
            $matching = $orders->filter(fn ($order) => (int) ($order->data['site']['id'] ?? $order->data['site_id'] ?? 0) === (int) $site->id);
            abort_if($matching->isEmpty(), 404, 'Sede non appartenente al viaggio operativo.');
            $lockedSite = Site::lockForUpdate()->findOrFail($site->id);
            abort_unless(hash_equals(self::version($lockedSite->notes), $version), 409, 'Le note della sede sono cambiate. Ricarica per consultare la versione aggiornata prima di salvare.');
            $before = $lockedSite->notes;
            if ($before === $notes) {
                return;
            }
            // Update the shared site record, preserving frozen execution and document snapshots.
            $lockedSite->notes = $notes;
            $lockedSite->save();
            JourneyEvent::create(['journey_id' => $lockedJourney->id, 'created_by_user_id' => $actor, 'payload' => [
                'event' => 'site_notes_updated', 'operation' => 'update_site_notes', 'site_id' => $site->id,
                'stop_ids' => $matching->pluck('journey_stop_id')->unique()->values()->all(),
                'changes' => [['path' => 'sites.'.$site->id.'.notes', 'before' => $before, 'after' => $notes]],
            ]]);
        });
    }
}
