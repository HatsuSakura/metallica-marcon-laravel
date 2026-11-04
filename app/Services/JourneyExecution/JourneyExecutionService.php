<?php

namespace App\Services\JourneyExecution;

use App\Enums\JourneyStatus;
use App\Models\Journey;
use App\Models\JourneyBaseline;
use App\Models\JourneyEvent;
use App\Models\JourneyExecutionHolder;
use App\Models\JourneyExecutionItem;
use App\Models\JourneyOrderExecution;
use App\Models\JourneyStop;
use App\Models\Order;
use App\Models\Site;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class JourneyExecutionService
{
    public function __construct(private PlanSnapshot $snapshots) {}

    public function start(Journey $journey, int $actor): Journey
    {
        return DB::transaction(function () use ($journey, $actor) {
            // One lock order for departure/reopening: driver, journey, then orders.
            DB::table('users')->where('id', $journey->driver_id)->lockForUpdate()->first();
            $j = Journey::query()->lockForUpdate()->findOrFail($journey->id);
            abort_unless((int) $j->driver_id === (int) $journey->driver_id, 409, 'Autista cambiato: ricarica il viaggio.');
            if ($j->baseline()->exists()) {
                return $j;
            } // Idempotent: never recapture or advance a second time.
            abort_unless($j->status === JourneyStatus::STATUS_CREATED, 409, 'Viaggio legacy già avviato: nessuna baseline originale disponibile.');
            $this->assertDriverAvailable($j);
            abort_unless($j->stops()->exists(), 422, 'Il piano non contiene tappe strutturate.');
            $orders = $j->orders()->lockForUpdate()->get();
            foreach ($orders as $order) {
                $this->claim($order, $j->id, 'active');
                abort_unless(app(\App\Services\OrderDocumentGenerationService::class)->isCurrent($order), 422, 'Genera documenti aggiornati per tutti gli ordini prima di partire.');
            }
            $snapshot = $this->snapshots->capture($j);
            JourneyBaseline::create(['journey_id' => $j->id, 'schema_version' => 1, 'data' => $snapshot,
                'checksum' => $this->snapshots->checksum($snapshot), 'captured_at' => now(), 'captured_by_user_id' => $actor]);
            $byId = collect($snapshot['orders'])->keyBy('id');
            foreach ($snapshot['stops'] as $stop) {
                foreach ($stop['orders'] as $id) {
                    $this->createOrderExecution($j->id, $stop['id'], $byId[$id], $id);
                }
            }
            $first = $j->stops()->orderBy('sequence')->first();
            if ($first) {
                DB::table('journey_stops')->where('id', $first->id)->update(['status' => 'in_progress', 'started_at' => now()]);
            }
            DB::table('journeys')->where('id', $j->id)->update(['status' => 'attivo', 'actual_start_at' => now(), 'execution_revision' => 1]);
            // The files prepared before departure remain valid for the identical initial execution.
            $started = $j->fresh();
            foreach ($started->orderExecutions()->get() as $execution) {
                $source = $byId[$execution->source_order_id];
                $effective = app(ExecutionProjection::class)->order($execution, $started);
                $execution->forceFill([
                    'documents_status' => 'generated', 'documents_version' => $source['documents_version'],
                    'documents_generated_at' => $source['documents_generated_at'],
                    'documents_base_path' => $source['documents_base_path'] ?? ('orders/documents/order-'.$execution->source_order_id),
                    'documents_input_hash' => app(DocumentRevision::class)->hash($effective),
                ])->save();
            }
            JourneyEvent::create(['journey_id' => $j->id, 'created_by_user_id' => $actor, 'payload' => [
                'event' => 'execution_started', 'revision' => 1, 'baseline_checksum' => $this->snapshots->checksum($snapshot)]]);

            return $j->fresh();
        });
    }

    private function assertDriverAvailable(Journey $journey): void
    {
        // Current locking read: do not use a stale REPEATABLE READ snapshot after waiting for the driver mutex.
        $active = Journey::where('driver_id', $journey->driver_id)->where('id', '!=', $journey->id)
            ->where('status', 'attivo')->where('execution_revision', '>', 0)->lockForUpdate()->first();
        abort_if($active, 409, 'Autista già impegnato su un altro viaggio.');
    }

    public function claim(Order $order, int $journeyId, string $phase = 'planned'): void
    {
        abort_if($order->journey_id && (int) $order->journey_id !== $journeyId, 409, 'Ordine assegnato a un altro viaggio.');
        abort_if(in_array($order->status, [\App\Enums\OrderStatus::STATUS_EXECUTED, \App\Enums\OrderStatus::STATUS_DOWNLOADED, \App\Enums\OrderStatus::STATUS_CLOSED], true) && (int) $order->journey_id !== $journeyId, 409, 'Ordine già eseguito.');
        $existing = DB::table('order_commitments')->where('order_id', $order->id)->lockForUpdate()->first();
        abort_if($existing && (int) $existing->journey_id !== $journeyId, 409, 'Ordine già impegnato su un altro viaggio.');
        if (! $existing) {
            DB::table('order_commitments')->insert(['order_id' => $order->id, 'journey_id' => $journeyId, 'phase' => $phase]);
        } else {
            DB::table('order_commitments')->where('order_id', $order->id)->update(['phase' => $phase]);
        }
    }

    public function command(Journey $journey, int $actor, int $expectedRevision, string $operation, array $input): Journey
    {
        return DB::transaction(function () use ($journey, $actor, $expectedRevision, $operation, $input) {
            if ($operation === 'reopen') {
                DB::table('users')->where('id', $journey->driver_id)->lockForUpdate()->first();
            }
            $j = Journey::query()->lockForUpdate()->findOrFail($journey->id);
            abort_unless($j->baseline()->exists(), 409, 'Questo viaggio non ha un contesto esecutivo.');
            abort_unless((int) $j->execution_revision === $expectedRevision, 409, 'Il viaggio è stato aggiornato. Ricarica prima di modificare.');
            abort_if($j->status === JourneyStatus::STATUS_CLOSED, 422, 'Il viaggio è chiuso.');
            abort_unless($j->status === JourneyStatus::STATUS_ACTIVE || in_array($operation, ['reopen', 'warehouse_item', 'close'], true), 422, 'Riapri una tappa prima di modificare un viaggio terminato.');
            $before = $this->state($j);
            $extra = [];
            switch ($operation) {
                case 'set_item_allocations':
                    app(ItemLoad::class)->split($j, $this->item($j, $input['item_id']), $input);
                    break;
                case 'set_item_loaded':
                    app(ItemLoad::class)->check($this->item($j, $input['item_id']), (bool) $input['is_loaded']);
                    break;
                case 'reorder': $this->reorder($j, $input['stop_ids']);
                    break;
                case 'complete': $this->complete($j, $input);
                    break;
                case 'skip': $this->skip($j, $input);
                    break;
                case 'reopen':
                    $this->assertDriverAvailable($j);
                    $extra = app(StopReopening::class)->reopen($j, $input['stop_id']);
                    break;
                case 'add_stop': $this->addStop($j, $input);
                    break;
                case 'update_journey':
                    abort_if(array_diff(array_keys($input), ['notes']), 422, 'Il convoglio è fissato alla partenza: motrice e rimorchio non possono essere modificati durante il viaggio.');
                    $data = $j->execution_data ?? [];
                    $changed = $this->changedValues($data + ($j->baseline->data['journey'] ?? []), $input);
                    if ($changed) {
                        DB::table('journeys')->where('id', $j->id)->update(['execution_data' => json_encode(array_replace($data, $changed), JSON_THROW_ON_ERROR)]);
                    }
                    break;
                case 'update_order':
                    $e = $this->orderExecution($j, $input['execution_order_id']);
                    $e->data = array_replace($e->data, $this->changedValues($e->data, Arr::only($input, ['notes', 'cargo_location'])));
                    $e->save();
                    break;
                case 'add_item':
                    $e = $this->orderExecution($j, $input['execution_order_id']);
                    $this->validateItemCargo($j, $input);
                    JourneyExecutionItem::create(['order_execution_id' => $e->id, 'data' => $this->itemData($input)] + Arr::only($input, ['actual_containers', 'actual_weight_kg']));
                    break;
                case 'update_item':
                    $item = $this->item($j, $input['item_id']);
                    $this->validateItemCargo($j, $input);
                    abort_if(($input['is_bulk'] ?? false) && $item->allocations()->exists(), 422, 'Assegna prima tutto il carico a un mezzo per passare a materiale sfuso.');
                    if (array_key_exists('cargo_location', $input)) {
                        $item->allocations()->delete();
                    }
                    $item->data = array_replace($item->data, $this->itemData($this->changedValues(app(ItemCargoLocation::class)->data($j, $item), $input)));
                    foreach (['actual_containers', 'actual_weight_kg'] as $field) {
                        if (array_key_exists($field, $input)) {
                            $item->$field = $input[$field];
                        }
                    }
                    $item->save();
                    break;
                case 'remove_item': $item = $this->item($j, $input['item_id']);
                    $item->removed = true;
                    $item->save();
                    break;
                case 'add_holder':
                    $e = $this->orderExecution($j, $input['execution_order_id']);
                    JourneyExecutionHolder::create(['order_execution_id' => $e->id, 'data' => $this->holderData($input)]);
                    break;
                case 'update_holder':
                    $holder = JourneyExecutionHolder::whereIn('order_execution_id', $j->orderExecutions()->where('status', 'pending')->select('id'))->findOrFail($input['holder_id']);
                    $holder->data = array_replace($holder->data, $this->changedValues($holder->data, Arr::only($input, ['total_holders_count'])));
                    if (array_key_exists('actual_delivered', $input)) {
                        $holder->actual_delivered = $input['actual_delivered'];
                    }
                    $holder->save();
                    break;
                case 'restore_original': case 'restore_previous':
                    $extra = app(ExecutionRestoration::class)->restore($j, $operation, $input);
                    break;
                case 'undo_restore':
                    $extra = app(ExecutionRestoration::class)->undo($j, $input['event_id']);
                    break;
                case 'warehouse_item':
                    $item = JourneyExecutionItem::whereIn('order_execution_id', $j->orderExecutions()->where('status', 'completed')->select('id'))->where('removed', false)->findOrFail($input['item_id']);
                    $values = Arr::except($input, ['item_id']);
                    if (in_array($values['warehouse_status'], ['weighed', 'classified'], true)) {
                        abort_unless(isset($values['weight_gross'], $values['weight_tare']), 422, 'Peso lordo e tara richiesti.');
                        abort_if($values['weight_tare'] > $values['weight_gross'], 422, 'La tara supera il peso lordo.');
                        $values['weight_net'] = $values['weight_gross'] - $values['weight_tare'];
                    }
                    $values = $this->changedValues($item->data, $values);
                    if (! $values) {
                        break;
                    }
                    $values['warehouse_recorded_by'] = $actor;
                    $values['warehouse_recorded_at'] = now()->toIso8601String();
                    $item->data = array_replace($item->data, $values);
                    $item->save();
                    break;
                case 'close':
                    abort_unless($j->status === JourneyStatus::STATUS_EXECUTED, 422, 'Termina il viaggio prima della chiusura.');
                    $items = JourneyExecutionItem::whereIn('order_execution_id', $j->orderExecutions()->where('status', 'completed')->select('id'))->where('removed', false)->get();
                    abort_if($items->contains(fn ($i) => ($i->data['warehouse_status'] ?? null) !== 'classified'), 422, 'Completa ricezione, pesatura e classificazione dei materiali.');
                    DB::table('journeys')->where('id', $j->id)->update(['status' => 'chiuso']);
                    DB::table('orders')->where('journey_id', $j->id)->whereIn('id', $j->orderExecutions()->where('status', 'completed')->select('source_order_id'))->update(['status' => 'chiuso']);
                    break;
                case 'finish':
                    abort_if($j->stops()->whereIn('status', ['planned', 'in_progress'])->exists(), 422, 'Ci sono ancora tappe da svolgere.');
                    DB::table('journeys')->where('id', $j->id)->update(['status' => 'eseguito', 'actual_end_at' => now()]);
                    break;
                default: abort(422, 'Operazione esecutiva non supportata.');
            }
            if (in_array($operation, ['update_item', 'remove_item', 'restore_original', 'restore_previous', 'undo_restore'], true)) {
                $this->invalidateLoadChecks($j, $before);
            }
            $after = $this->state($j->fresh());
            $changes = $this->diff($before, $after);
            if (! $changes) {
                return $j->fresh();
            }
            $revision = $expectedRevision + 1;
            DB::table('journeys')->where('id', $j->id)->update(['execution_revision' => $revision]);
            JourneyEvent::create(['journey_id' => $j->id, 'journey_stop_id' => $input['stop_id'] ?? null, 'created_by_user_id' => $actor,
                'payload' => ['event' => 'execution_changed', 'operation' => $operation, 'revision' => $revision,
                    'previous_revision' => $expectedRevision, 'changes' => $changes, 'reason_code' => $input['reason_code'] ?? null,
                    'driver_notes' => $input['driver_notes'] ?? null] + $extra]);

            return $j->fresh();
        });
    }

    private function createOrderExecution(int $journeyId, int $stopId, array $data, ?int $sourceId): JourneyOrderExecution
    {
        $items = $data['items'] ?? [];
        $holders = $data['holders'] ?? [];
        unset($data['items'], $data['holders']);
        $e = JourneyOrderExecution::create(['journey_id' => $journeyId, 'journey_stop_id' => $stopId, 'source_order_id' => $sourceId, 'data' => $data]);
        foreach ($items as $item) {
            $e->items()->create(['source_order_item_id' => $item['id'] ?? null, 'data' => $item]);
        }
        foreach ($holders as $holder) {
            $e->holders()->create(['source_order_holder_id' => $holder['id'] ?? null, 'data' => $holder]);
        }

        return $e;
    }

    private function orderExecution(Journey $j, int $id): JourneyOrderExecution
    {
        return $j->orderExecutions()->where('status', 'pending')->findOrFail($id);
    }

    private function item(Journey $j, int $id): JourneyExecutionItem
    {
        return JourneyExecutionItem::whereIn('order_execution_id', $j->orderExecutions()->where('status', 'pending')->select('id'))->findOrFail($id);
    }

    private function reorder(Journey $j, array $ids): void
    {
        $stops = $j->stops()->orderBy('sequence')->get();
        $pending = $stops->whereIn('status', [\App\Enums\JourneyStopStatus::Planned, \App\Enums\JourneyStopStatus::InProgress])->pluck('id')->all();
        abort_unless(count($ids) === count(array_unique($ids)) && collect($ids)->sort()->values()->all() === collect($pending)->sort()->values()->all(), 422, 'Sequenza tappe non valida.');
        $queue = array_values($ids);
        $first = true;
        foreach ($stops as $index => $stop) {
            if (! in_array($stop->id, $pending, true)) {
                continue;
            }
            $id = array_shift($queue);
            DB::table('journey_stops')->where('id', $id)->update(['sequence' => $index + 1, 'status' => $first ? 'in_progress' : 'planned']);
            $first = false;
        }
    }

    private function current(Journey $j, int $id): JourneyStop
    {
        return $j->stops()->where('status', 'in_progress')->findOrFail($id);
    }

    private function advance(Journey $j): void
    {
        if ($j->stops()->where('status', 'in_progress')->exists()) {
            return;
        }
        $next = $j->stops()->where('status', 'planned')->orderBy('sequence')->first();
        if ($next) {
            DB::table('journey_stops')->where('id', $next->id)->update(['status' => 'in_progress', 'started_at' => $next->started_at ?? now()]);
        }
    }

    private function complete(Journey $j, array $input): void
    {
        $stop = $this->current($j, $input['stop_id']);
        foreach ($j->orderExecutions()->where('journey_stop_id', $stop->id)->where('status', 'pending')->get() as $e) {
            foreach ($e->items()->where('removed', false)->get() as $item) {
                if ($item->actual_containers === null && ! ($item->data['is_bulk'] ?? false)) {
                    abort_unless($input['confirm_current_quantities'] ?? false, 422, 'Registra o conferma le quantità ritirate.');
                    $item->actual_containers = $item->data['holder_quantity'] ?? null;
                    abort_if($item->actual_containers === null, 422, 'Quantità contenitori mancante.');
                }
                if ($item->actual_weight_kg === null && ($input['confirm_current_quantities'] ?? false)) {
                    $item->actual_weight_kg = $item->data['weight_declared'] ?? null;
                }
                abort_unless(app(ItemLoad::class)->valid($item), 422, 'Aggiorna la suddivisione del carico prima di completare la tappa.');
                $item->save();
            }
            foreach ($e->holders as $holder) {
                if ($holder->actual_delivered === null) {
                    abort_unless($input['confirm_current_quantities'] ?? false, 422, 'Registra o conferma i contenitori consegnati.');
                    $holder->actual_delivered = $holder->data['total_holders_count'] ?? null;
                    abort_if($holder->actual_delivered === null, 422, 'Quantità consegnata mancante.');
                    $holder->save();
                }
            }
            $e->status = 'completed';
            $e->save();
            if ($e->source_order_id) {
                DB::table('order_commitments')->where('order_id', $e->source_order_id)->where('journey_id', $j->id)->update(['phase' => 'fulfilled']);
                DB::table('orders')->where('id', $e->source_order_id)->where('journey_id', $j->id)->update(['status' => 'eseguito']);
            }
        }
        DB::table('journey_stops')->where('id', $stop->id)->update(['status' => 'done', 'completed_at' => now()]);
        $this->advance($j);
    }

    private function skip(Journey $j, array $input): void
    {
        $stop = $this->current($j, $input['stop_id']);
        foreach ($j->orderExecutions()->where('journey_stop_id', $stop->id)->where('status', 'pending')->get() as $e) {
            abort_if($e->items()->where(fn ($q) => $q->where('actual_containers', '>', 0)->orWhere('actual_weight_kg', '>', 0)->orWhere('is_loaded', true))->exists()
                || $e->holders()->where('actual_delivered', '>', 0)->exists(), 422, 'Movimentazione parziale: non è un salto totale.');
            $e->status = 'released';
            $e->save();
            if ($e->source_order_id) {
                DB::table('order_commitments')->where('order_id', $e->source_order_id)->where('journey_id', $j->id)->delete();
                DB::table('orders')->where('id', $e->source_order_id)->where('journey_id', $j->id)->update([
                    'journey_id' => null, 'cargo_location' => null, 'status' => 'creato', 'documents_status' => 'not_generated']);
            }
        }
        DB::table('journey_stops')->where('id', $stop->id)->update(['status' => 'skipped', 'completed_at' => now(), 'reason_code' => $input['reason_code'], 'driver_notes' => $input['driver_notes'] ?? null]);
        $this->advance($j);
    }

    private function addStop(Journey $j, array $input): void
    {
        $data = $input;
        $order = null;
        if (! empty($input['source_order_id'])) {
            $order = Order::query()->lockForUpdate()->findOrFail($input['source_order_id']);
            abort_if($order->journey_id && (int) $order->journey_id !== (int) $j->id, 409, 'Ordine già pianificato.');
            abort_if($j->orderExecutions()->where('source_order_id', $order->id)->exists(), 422, 'Ordine già presente: riapri la sua tappa.');
            $this->claim($order, $j->id, 'active');
            $data['customer_id'] = $order->customer_id;
        }
        $stop = JourneyStop::create(Arr::only($data, ['kind', 'customer_id', 'technical_action_id', 'description', 'address_text', 'driver_notes']) + [
            'journey_id' => $j->id, 'sequence' => ((int) $j->stops()->max('sequence')) + 1, 'planned_sequence' => 0, 'status' => 'planned']);
        if ($order) {
            $this->createOrderExecution($j->id, $stop->id, $this->snapshots->order($order), $order->id);
            DB::table('orders')->where('id', $order->id)->update(['journey_id' => $j->id]);
        } elseif (($input['kind'] ?? '') === 'customer') {
            $site = Site::with(['customer.seller', 'internalContacts', 'timetable'])->findOrFail($input['site_id']);
            abort_unless((int) $site->customer_id === (int) $input['customer_id'], 422, 'Sede non appartenente al cliente.');
            $this->createOrderExecution($j->id, $stop->id, ['customer_id' => $site->customer_id, 'site_id' => $site->id,
                'legacy_code' => 'J'.$j->id.'-T'.$stop->id, 'notes' => '', 'customer' => $site->customer->toArray(), 'site' => $site->toArray()], null);
        }
        $this->advance($j);
    }

    private function changedValues(array $current, array $input): array
    {
        return array_filter($input, function ($value, $key) use ($current) {
            $old = $current[$key] ?? null;
            if ($key === 'is_bulk' && (bool) $old === (bool) $value) {
                return false;
            }
            if ($old === $value) {
                return false;
            }
            if (($old === null || $old === '') && ($value === null || $value === '')) {
                return false;
            }
            if (in_array($key, ['holder_quantity', 'weight_declared', 'weight_gross', 'weight_tare', 'weight_net', 'warehouse_id', 'total_holders_count', 'cer_code_id', 'holder_id'], true)
                && is_numeric($old) && is_numeric($value) && (float) $old === (float) $value) {
                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function itemData(array $input): array
    {
        $data = Arr::only($input, ['cer_code_id', 'holder_id', 'holder_quantity', 'weight_declared', 'description', 'is_bulk', 'cargo_location', 'order_item_group_label', 'warehouse_id']);
        if (isset($input['cer_code_id'])) {
            $data['cer_code'] = (array) DB::table('cer_codes')->find($input['cer_code_id']);
        }
        if (array_key_exists('holder_id', $input)) {
            $data['holder'] = $input['holder_id'] ? (array) DB::table('holders')->find($input['holder_id']) : [];
        }
        if (array_key_exists('order_item_group_label', $input)) {
            $data['order_item_group_id'] = null;
            $data['order_item_group'] = ['label' => $input['order_item_group_label']];
        }

        return $data;
    }

    private function holderData(array $input): array
    {
        return Arr::only($input, ['holder_id', 'total_holders_count']) + ['holder' => (array) DB::table('holders')->find($input['holder_id'])];
    }

    public function state(Journey $j): array
    {
        return ['journey' => Arr::only($j->getAttributes(), ['status', 'actual_end_at']) + ['execution_data' => $j->execution_data ?? []],
            'stops' => DB::table('journey_stops')->where('journey_id', $j->id)->orderBy('id')->get()->keyBy('id')->map(fn ($s) => Arr::except((array) $s, ['created_at', 'updated_at']))->all(),
            'orders' => $j->orderExecutions()->get()->keyBy('id')->map(fn ($e) => ['status' => $e->status, 'data' => $e->data])->all(),
            'items' => JourneyExecutionItem::whereIn('order_execution_id', $j->orderExecutions()->select('id'))->with(['orderExecution', 'allocations'])->get()->keyBy('id')->map(fn ($i) => ['is_loaded' => (bool) $i->is_loaded, 'allocations' => $i->allocations->mapWithKeys(fn ($allocation) => [$allocation->cargo_location => $allocation->containers])->all(), 'data' => app(ItemCargoLocation::class)->data($j, $i), 'actual_containers' => $i->actual_containers, 'actual_weight_kg' => $i->actual_weight_kg, 'removed' => $i->removed])->all(),
            'holders' => JourneyExecutionHolder::whereIn('order_execution_id', $j->orderExecutions()->select('id'))->get()->keyBy('id')->map(fn ($h) => ['data' => $h->data, 'actual_delivered' => $h->actual_delivered])->all()];
    }

    private function invalidateLoadChecks(Journey $journey, array $before): void
    {
        $after = $this->state($journey);
        foreach ($after['items'] as $id => $item) {
            if (! $item['is_loaded'] || ! isset($before['items'][$id])) {
                continue;
            }
            $old = $before['items'][$id];
            $fields = ['holder_quantity', 'holder_id', 'cer_code_id', 'is_bulk', 'cargo_location'];
            $changed = Arr::only($old['data'], $fields) !== Arr::only($item['data'], $fields)
                || $old['allocations'] !== $item['allocations'] || $old['removed'] !== $item['removed']
                || ($old['actual_containers'] ?? $old['data']['holder_quantity'] ?? null) !== ($item['actual_containers'] ?? $item['data']['holder_quantity'] ?? null);
            if ($changed) {
                JourneyExecutionItem::whereKey($id)->update(['is_loaded' => false]);
            }
        }
    }

    private function validateItemCargo(Journey $journey, array $input): void
    {
        if (! array_key_exists('cargo_location', $input)) {
            return;
        }
        abort_unless($input['cargo_location'] === null || in_array($input['cargo_location'], ['vehicle', 'trailer'], true), 422, 'Seleziona motrice o rimorchio per il materiale.');
        abort_if($input['cargo_location'] === 'trailer' && ! $journey->trailer_id, 422, 'Questo viaggio non ha un rimorchio.');
    }

    private function diff(array $before, array $after, string $prefix = ''): array
    {
        $changes = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $path = ltrim($prefix.'.'.$key, '.');
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            if (is_array($old) && is_array($new)) {
                $changes = array_merge($changes, $this->diff($old, $new, $path));
            } elseif ($old !== $new || array_key_exists($key, $before) !== array_key_exists($key, $after)) {
                $changes[] = ['path' => $path, 'before' => $old, 'after' => $new, 'before_exists' => array_key_exists($key, $before), 'after_exists' => array_key_exists($key, $after)];
            }
        }

        return $changes;
    }
}
