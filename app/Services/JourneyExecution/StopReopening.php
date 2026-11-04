<?php

namespace App\Services\JourneyExecution;

use App\Enums\JourneyStatus;
use App\Enums\JourneyStopStatus;
use App\Models\Journey;
use App\Models\JourneyStop;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StopReopening
{
    public function previews(Journey $journey, Collection $executions): array
    {
        [$sources,$commitments] = $this->sources($executions);
        $otherActive = Journey::where('driver_id', $journey->driver_id)->where('id', '!=', $journey->id)
            ->where('status', 'attivo')->where('execution_revision', '>', 0)->exists();
        $result = [];
        foreach ($journey->stops as $stop) {
            $reason = $this->blockReason($journey, $stop, $executions->where('journey_stop_id', $stop->id), $sources, $commitments);
            if (! $reason && $otherActive) {
                $reason = 'Autista già impegnato su un altro viaggio.';
            }
            $result[$stop->id] = ['available' => $reason === null, 'reason' => $reason];
        }

        return $result;
    }

    /** Called inside the execution transaction, after locking driver and journey. */
    public function reopen(Journey $journey, int $stopId): array
    {
        $stop = $journey->stops()->findOrFail($stopId);
        $executions = $journey->orderExecutions()->where('journey_stop_id', $stopId)->with('items')->get();
        [$sources,$commitments] = $this->sources($executions, true);
        $reason = $this->blockReason($journey, $stop, $executions, $sources, $commitments);
        abort_if($reason, 409, $reason);
        foreach ($executions as $execution) {
            if ($execution->source_order_id) {
                $source = $sources[$execution->source_order_id];
                app(JourneyExecutionService::class)->claim($source, $journey->id, 'active');
                DB::table('orders')->where('id', $source->id)->update(['journey_id' => $journey->id, 'status' => 'pianificato']);
            }
            $execution->status = 'pending';
            $execution->save();
        }
        $deferred = $journey->stops()->where('status', 'in_progress')->where('id', '!=', $stopId)->pluck('id')->all();
        // Correct an accidental completion by returning focus to that stop, with a single current stop.
        DB::table('journey_stops')->whereIn('id', $deferred)->update(['status' => 'planned', 'updated_at' => now()]);
        DB::table('journey_stops')->where('id', $stopId)->update(['status' => 'in_progress', 'completed_at' => null,
            'started_at' => $stop->started_at ?? now(), 'reason_code' => null, 'updated_at' => now()]);
        DB::table('journeys')->where('id', $journey->id)->update(['status' => 'attivo', 'actual_end_at' => null, 'updated_at' => now()]);

        return ['reopened_from' => $stop->status->value, 'reopened_to' => 'in_progress', 'deferred_stop_ids' => $deferred, 'recorded_quantities_preserved' => true];
    }

    private function sources(Collection $executions, bool $lock = false): array
    {
        $ids = $executions->pluck('source_order_id')->filter()->unique()->all();
        $sources = Order::withTrashed()->whereIn('id', $ids)->orderBy('id');
        $commitments = DB::table('order_commitments')->whereIn('order_id', $ids)->orderBy('order_id');
        if ($lock) {
            $sources->lockForUpdate();
            $commitments->lockForUpdate();
        }

        return [$sources->get()->keyBy('id'), $commitments->get()->keyBy('order_id')];
    }

    private function blockReason(Journey $journey, JourneyStop $stop, Collection $executions, Collection $sources, Collection $commitments): ?string
    {
        if ($journey->status === JourneyStatus::STATUS_CLOSED) {
            return 'Il viaggio è chiuso: le rettifiche richiedono la gestione dei movimenti di magazzino.';
        }
        if (! in_array($stop->status, [JourneyStopStatus::Done, JourneyStopStatus::Skipped, JourneyStopStatus::Cancelled], true)) {
            return 'La tappa è già aperta.';
        }
        foreach ($executions as $execution) {
            if ($execution->items->contains(fn ($item) => ! empty($item->data['warehouse_status']))) {
                return 'Materiali già ricevuti a magazzino: occorre rettificare prima la ricezione.';
            }
            if (! $execution->source_order_id) {
                continue;
            }
            $source = $sources->get($execution->source_order_id);
            if (! $source || $source->trashed()) {
                return 'Ordine non più disponibile: la tappa non può essere riaperta.';
            }
            if (($source->journey_id && (int) $source->journey_id !== (int) $journey->id)
                || ($commitments->has($source->id) && (int) $commitments[$source->id]->journey_id !== (int) $journey->id)) {
                return 'Ordine già ripianificato su un altro viaggio: la tappa non può essere riaperta.';
            }
        }

        return null;
    }
}
