<?php

namespace App\Services\JourneyExecution;

use App\Enums\JourneyStatus;
use App\Enums\UserRole;
use App\Models\Journey;
use App\Models\JourneyExecutionUpdate;
use App\Models\User;
use App\Notifications\JourneyExecutionUpdated;
use Illuminate\Support\Facades\Notification;

class ExecutionUpdatePublisher
{
    public function record(Journey $journey, int $actorId, string $id, array $before, array $after, array $operations, ?int $stopId = null, ?string $requestHash = null): JourneyExecutionUpdate
    {
        $actor = User::findOrFail($actorId);
        $changes = app(ExecutionUpdateChanges::class);
        $planned = app(ExecutionUpdateSnapshot::class)->capture($journey, true);
        $baselineChanges = $changes->between($planned, $after, true);
        if ($stopId) {
            $baselineChanges = array_values(array_filter($baselineChanges, fn ($row) => $row['stop_id'] === $stopId));
        }
        $previous = $stopId ? JourneyExecutionUpdate::where('journey_id', $journey->id)->where('stop_id', $stopId)
            ->where(fn ($query) => $query->whereJsonContains('operations', 'complete')->orWhereJsonContains('operations', 'skip'))
            ->orderByDesc('revision')->first() : null;
        $outcome = count($operations) === 1 ? $operations[0] : 'update';
        $titles = ['complete' => 'Tappa conclusa', 'skip' => 'Tappa saltata', 'reopen' => 'Tappa riaperta', 'cancel_stop' => 'Tappa annullata', 'finish' => 'Viaggio terminato', 'close' => 'Viaggio chiuso'];
        $interventionChanges = $changes->between($before, $after);
        $driverOutcome = $actor->role === UserRole::DRIVER && in_array($outcome, ['complete', 'skip', 'reopen', 'finish'], true);
        $changedStops = array_column($interventionChanges, 'stop_id');
        $changedOrders = array_column($interventionChanges, 'execution_order_id');
        $orders = collect($after + $before)->filter(fn ($row, $key) => str_starts_with($key, 'order:') &&
            ($outcome === 'finish' || ($stopId ? $row['stop_id'] === $stopId : (in_array($row['stop_id'], $changedStops, true) || in_array($row['execution_order_id'], $changedOrders, true)))))
            ->map(fn ($row) => ['id' => $row['order_id'], 'execution_order_id' => $row['execution_order_id'], 'stop_id' => $row['stop_id']])->values()->all();
        $previousSnapshot = $previous?->snapshot;
        if ($previous && ($previous->payload['schema_version'] ?? 1) < 2) {
            // Version 1 represented unsplit materials as 0/0 rather than the carrier total.
            // Normalize in memory; never rewrite historical snapshots or personal copies.
            foreach ($previousSnapshot as $key => &$record) {
                $values = &$record['values'];
                $quantity = $values['actual_containers'] ?? $values['holder_quantity'] ?? null;
                if (str_starts_with($key, 'item:') && ! ($values['vehicle_containers'] ?? 0) && ! ($values['trailer_containers'] ?? 0) && $quantity > 0) {
                    $location = $values['cargo_location'] ?? 'vehicle';
                    $values['vehicle_containers'] = $location === 'vehicle' ? $quantity : 0;
                    $values['trailer_containers'] = $location === 'trailer' ? $quantity : 0;
                }
                unset($values);
            }
            unset($record);
        }
        $previousChanges = $previous ? $changes->between($previousSnapshot, $after, true) : null;
        if ($previousChanges !== null && $stopId) {
            $previousChanges = array_values(array_filter($previousChanges, fn ($row) => $row['stop_id'] === $stopId));
        }
        $payload = [
            'schema_version' => 2, 'intervention_id' => $id, 'journey_id' => $journey->id, 'stop_id' => $stopId,
            'revision' => $journey->execution_revision, 'operations' => $operations, 'event' => $outcome,
            'message' => ($titles[$outcome] ?? 'Esecuzione viaggio aggiornata').' · Viaggio #'.$journey->id,
            'actor' => ['id' => $actor->id, 'name' => trim($actor->name.' '.$actor->surname), 'role' => $actor->role?->value],
            'occurred_at' => now()->toIso8601String(), 'orders' => $orders,
            'changes' => $driverOutcome ? $baselineChanges : $interventionChanges, 'baseline_changes' => $baselineChanges,
            'summary_basis' => $driverOutcome ? 'plan' : 'intervention',
            'stop' => $stopId ? ($after['stop:'.$stopId] ?? $before['stop:'.$stopId] ?? null) : null,
            'previous_outcome_changes' => $previousChanges,
        ];
        $update = JourneyExecutionUpdate::create(['id' => $id, 'journey_id' => $journey->id, 'actor_id' => $actorId, 'stop_id' => $stopId,
            'revision' => $journey->execution_revision, 'request_hash' => $requestHash, 'operations' => $operations, 'snapshot' => $after, 'payload' => $payload, 'created_at' => now()]);
        // Keep the immutable intervention for audit/idempotency even when no alert is needed.
        if (($actor->role === UserRole::DRIVER && ! $driverOutcome) || (! $interventionChanges && ! $driverOutcome)) {
            return $update;
        }
        $recipients = User::where('role', UserRole::LOGISTIC->value)->get();
        if ($actor->role === UserRole::LOGISTIC && $journey->status === JourneyStatus::STATUS_ACTIVE && (int) $journey->driver_id !== $actorId) {
            $driver = User::whereKey($journey->driver_id)->where('role', UserRole::DRIVER->value)->first();
            if ($driver) {
                $recipients->push($driver);
            }
        }
        // Database delivery shares the domain transaction: visible only after commit,
        // durable without a worker, and rolled back with any failed operation.
        Notification::sendNow($recipients->unique('id'), new JourneyExecutionUpdated($payload));

        return $update;
    }
}
