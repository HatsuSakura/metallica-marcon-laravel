<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\JourneyExecutionUpdate;
use App\Models\User;
use App\Services\Driver\DriverJourneyReadModel;
use Illuminate\Support\Facades\DB;

class ExecutionInterventions
{
    public const BATCH_OPERATIONS = ['reorder', 'add_stop', 'cancel_stop', 'update_journey', 'update_order', 'add_item', 'update_item',
        'remove_item', 'restore_item', 'hide_removed_item', 'add_holder', 'update_holder', 'set_item_allocations', 'set_item_loaded', 'restore_original', 'restore_previous'];

    public function run(Journey $journey, User $actor, int $revision, string $id, array $commands, bool $preview = false, bool $batch = false): array
    {
        $hash = hash('sha256', json_encode($this->canonical(['revision' => $revision, 'commands' => $commands]), JSON_THROW_ON_ERROR));
        try {
            return DB::transaction(function () use ($journey, $actor, $revision, $id, $commands, $preview, $batch, $hash) {
                // Same lock order as reopen: driver first, then journey.
                if (collect($commands)->contains('operation', 'reopen')) {
                    DB::table('users')->where('id', $journey->driver_id)->lockForUpdate()->first();
                }
                $locked = Journey::lockForUpdate()->findOrFail($journey->id);
                $existing = JourneyExecutionUpdate::find($id);
                if ($existing) {
                    abort_unless((int) $existing->actor_id === (int) $actor->id && (int) $existing->journey_id === (int) $locked->id && hash_equals($existing->request_hash ?? '', $hash), 409, 'Identificativo intervento già utilizzato.');

                    return ['journey' => $locked, 'references' => [], 'intervention_id' => $id, 'replayed' => true];
                }
                abort_unless((int) $locked->execution_revision === $revision, 409, 'Il viaggio è cambiato. Ricarica prima di confermare le modifiche.');
                $snapshot = app(ExecutionUpdateSnapshot::class);
                $before = $snapshot->capture($locked);
                $resolved = [];
                $references = [];
                foreach ($commands as $command) {
                    $operation = $command['operation'];
                    abort_if($batch && ! in_array($operation, self::BATCH_OPERATIONS, true), 422, 'Questa azione va eseguita separatamente dalle modifiche in bozza.');
                    $input = $this->resolve($operation, $command['input'], $resolved);
                    $input = app(ExecutionCommandValidator::class)->validate($actor, $operation, $input);
                    $oldIds = $this->ids($locked);
                    $locked = app(JourneyExecutionService::class)->command($locked, $actor->id, (int) $locked->execution_revision, $operation, $input, false, $id);
                    foreach ($this->ids($locked) as $entity => $ids) {
                        foreach (array_values(array_diff($ids, $oldIds[$entity])) as $index => $createdId) {
                            $token = '@'.$command['id'].':'.$entity.':'.$index;
                            $resolved[$token] = ['type' => $entity, 'id' => $createdId];
                            $references[$entity][(string) $createdId] = $token;
                        }
                    }
                }
                if ($preview) {
                    $view = app(DriverJourneyReadModel::class)->load($locked)->toArray();
                    // All commands, reservations and events disappear with this rollback.
                    throw new ExecutionPreviewResult(['journey' => $view, 'references' => $references, 'intervention_id' => $id]);
                }
                $after = $snapshot->capture($locked);
                $operations = array_column($commands, 'operation');
                $stopId = count($commands) === 1 ? ($commands[0]['input']['stop_id'] ?? null) : null;
                if ((int) $locked->execution_revision !== $revision && (! $batch || $before !== $after)) {
                    app(ExecutionUpdatePublisher::class)->record($locked, $actor->id, $id, $before, $after, $operations, is_numeric($stopId) ? (int) $stopId : null, $hash);
                } else {
                    JourneyExecutionUpdate::create(['id' => $id, 'journey_id' => $locked->id, 'actor_id' => $actor->id, 'revision' => $locked->execution_revision,
                        'request_hash' => $hash, 'operations' => $operations, 'snapshot' => $after, 'payload' => ['noop' => true], 'created_at' => now()]);
                }

                return ['journey' => $locked, 'references' => $references, 'intervention_id' => $id, 'replayed' => false];
            });
        } catch (ExecutionPreviewResult $result) {
            return $result->result;
        }
    }

    private function ids(Journey $journey): array
    {
        $orders = $journey->orderExecutions()->pluck('id');

        return ['stop' => $journey->stops()->orderBy('id')->pluck('id')->all(), 'order' => $orders->all(),
            'item' => DB::table('journey_execution_items')->whereIn('order_execution_id', $orders)->orderBy('id')->pluck('id')->all(),
            'holder' => DB::table('journey_execution_holders')->whereIn('order_execution_id', $orders)->orderBy('id')->pluck('id')->all()];
    }

    private function resolve(string $operation, array $input, array $references): array
    {
        $fields = ['stop_id' => 'stop', 'stop_ids' => 'stop', 'execution_order_id' => 'order', 'item_id' => 'item'];
        if ($operation === 'update_holder') {
            $fields['holder_id'] = 'holder';
        }
        foreach ($fields as $field => $type) {
            if (! array_key_exists($field, $input)) {
                continue;
            }
            $resolve = function ($value) use ($type, $references) {
                if (! is_string($value) || ! str_starts_with($value, '@')) {
                    return $value;
                }
                abort_unless(isset($references[$value]) && $references[$value]['type'] === $type, 422, 'Riferimento di bozza non valido.');

                return $references[$value]['id'];
            };
            $input[$field] = is_array($input[$field]) ? array_map($resolve, $input[$field]) : $resolve($input[$field]);
        }

        return $input;
    }

    private function canonical(array $data): array
    {
        if (! array_is_list($data)) {
            ksort($data);
        }

        return array_map(fn ($value) => is_array($value) ? $this->canonical($value) : $value,$data);
    }
}
