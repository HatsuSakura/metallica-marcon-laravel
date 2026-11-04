<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\JourneyExecutionItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/** The preview and the write use exactly the same saved history and baseline. */
class ExecutionRestoration
{
    public const FIELDS = ['holder_quantity', 'weight_declared', 'description', 'actual_containers', 'actual_weight_kg', 'cargo_location', 'removed', 'cer_code_id', 'holder_id', 'is_bulk'];

    private const COLUMNS = ['actual_containers', 'actual_weight_kg', 'removed', 'is_loaded'];

    public function previews(Journey $journey, Collection $items): array
    {
        $events = $journey->events()->orderByDesc('id')->get();
        $originals = app(ItemCargoLocation::class)->originals($journey);
        $result = [];
        foreach ($items as $item) {
            foreach (self::FIELDS as $field) {
                $preview = $this->options($item, $field, $originals->get($item->source_order_item_id), $events);
                foreach (['original', 'previous'] as $choice) {
                    unset($preview[$choice]['metadata']);
                }
                $result[$item->id][$field] = $preview;
            }
        }

        return $result;
    }

    public function restore(Journey $journey, string $operation, array $input): array
    {
        $item = $this->item($journey, $input['item_id']);
        $field = $input['field'];
        abort_unless(in_array($field, self::FIELDS, true), 422, 'Campo non ripristinabile.');
        $original = app(ItemCargoLocation::class)->originals($journey)->get($item->source_order_item_id);
        $events = $journey->events()->orderByDesc('id')->get();
        $options = $this->options($item, $field, $original, $events);
        $choice = $options[$operation === 'restore_original' ? 'original' : 'previous'];
        abort_unless($choice['available'], 422, $choice['reason']);
        $related = $this->related($field);
        if (in_array($field, self::COLUMNS, true)) {
            $item->$field = $choice['value'];
        } else {
            if ($field === 'cargo_location') {
                $item->allocations()->delete();
            }
            $item->data = array_replace($item->data, [$field => $choice['value']], $related ? [$related => $choice['metadata']] : []);
        }
        $item->save();
        $extra = ['restore_target' => ['item_id' => $item->id, 'field' => $field]];
        if ($operation === 'restore_previous') {
            $extra += ['reverts_event_id' => $choice['event_id'], 'reverts_path' => $this->path($item, $field)];
        }

        return $extra;
    }

    public function undo(Journey $journey, int $eventId): array
    {
        $event = $journey->events()->findOrFail($eventId);
        $payload = $event->payload;
        abort_unless(in_array($payload['operation'] ?? '', ['restore_original', 'restore_previous'], true)
            && ($payload['revision'] ?? null) === $journey->execution_revision, 409, 'Il viaggio è cambiato: questo ripristino non può più essere annullato direttamente.');
        $target = $payload['restore_target'] ?? null;
        abort_unless($target, 422, 'Ripristino privo di riferimento annullabile.');
        $item = $this->item($journey, $target['item_id']);
        $prefix = 'items.'.$item->id.'.';
        foreach ($payload['changes'] as $change) {
            abort_unless(str_starts_with($change['path'], $prefix), 422, 'Contesto del ripristino non valido.');
            $path = substr($change['path'], strlen($prefix));
            if (str_starts_with($path, 'allocations.')) {
                $location = substr($path, strlen('allocations.'));
                if (($change['before_exists'] ?? true) === false) {
                    $item->allocations()->where('cargo_location', $location)->delete();
                } else {
                    $item->allocations()->updateOrCreate(['cargo_location' => $location], ['containers' => $change['before']]);
                }
            } elseif (str_starts_with($path, 'data.')) {
                $data = $item->data;
                if (($change['before_exists'] ?? true) === false) {
                    Arr::forget($data, substr($path, 5));
                } else {
                    Arr::set($data, substr($path, 5), $change['before']);
                }
                $item->data = $data;
            } else {
                abort_unless(in_array($path, self::COLUMNS, true), 422, 'Campo del ripristino non valido.');
                $item->$path = $change['before'];
            }
        }
        $item->save();

        return ['cancels_restore_event_id' => $event->id, 'restore_target' => $target];
    }

    private function options(JourneyExecutionItem $item, string $field, ?array $original, Collection $events): array
    {
        $current = in_array($field, self::COLUMNS, true) ? $item->$field : ($item->data[$field] ?? null);
        if ($field === 'cargo_location') {
            $current = app(ItemCargoLocation::class)->data($item->orderExecution->journey, $item)['cargo_location'];
        }
        $related = $this->related($field);
        $metadata = $related ? ($item->data[$related] ?? []) : null;
        $originalValue = match ($field) {
            'actual_containers','actual_weight_kg' => null, 'removed' => false, default => $original[$field] ?? null
        };
        $originalMetadata = $related ? ($original[$related] ?? []) : null;
        $previous = $this->previous($item, $field, $current, $metadata, $events);
        $originalAvailable = $original !== null && ! $this->same($field, $current, $originalValue, $metadata, $originalMetadata);
        $originalChoice = ['available' => $originalAvailable, 'value' => $originalValue,
            'label' => $this->label($field, $originalValue, $originalMetadata), 'metadata' => $originalMetadata,
            'exists' => $original !== null,
            'reason' => $original === null ? 'Materiale aggiunto durante il viaggio: nessun valore alla partenza.' : 'Il valore coincide già con quello alla partenza.'];

        return ['current' => ['value' => $current, 'label' => $this->label($field, $current, $metadata)],
            'original' => $originalChoice, 'previous' => $previous,
            'same_targets' => $previous['available'] && $originalAvailable && $this->same($field, $previous['value'], $originalValue, $previous['metadata'], $originalMetadata)];
    }

    private function previous(JourneyExecutionItem $item, string $field, mixed $current, ?array $metadata, Collection $events): array
    {
        $cancelled = $events->pluck('payload.cancels_restore_event_id')->filter()->all();
        $undone = [];
        foreach ($events as $event) {
            if (! in_array($event->id, $cancelled, true) && isset($event->payload['reverts_event_id'])) {
                $undone[] = $event->payload['reverts_event_id'].':'.($event->payload['reverts_path'] ?? '');
            }
        }
        $path = $this->path($item, $field);
        foreach ($events as $event) {
            if (in_array($event->id, $cancelled, true) || in_array($event->id.':'.$path, $undone, true)
                || in_array($event->payload['operation'] ?? '', ['restore_previous', 'undo_restore'], true)) {
                continue;
            }
            foreach ($event->payload['changes'] ?? [] as $change) {
                if ($change['path'] !== $path) {
                    continue;
                }
                $beforeMetadata = $metadata;
                if ($related = $this->related($field)) {
                    $prefix = 'items.'.$item->id.'.data.'.$related;
                    foreach ($event->payload['changes'] as $metadataChange) {
                        if ($metadataChange['path'] === $prefix) {
                            $beforeMetadata = $metadataChange['before'] ?? [];
                        } elseif (str_starts_with($metadataChange['path'], $prefix.'.')) {
                            $key = substr($metadataChange['path'], strlen($prefix) + 1);
                            if (($metadataChange['before_exists'] ?? true) === false) {
                                Arr::forget($beforeMetadata, $key);
                            } else {
                                Arr::set($beforeMetadata, $key, $metadataChange['before']);
                            }
                        }
                    }
                }
                if ($this->same($field, $current, $change['before'], $metadata, $beforeMetadata)) {
                    continue;
                }

                return ['available' => true, 'event_id' => $event->id, 'value' => $change['before'],
                    'label' => $this->label($field, $change['before'], $beforeMetadata), 'metadata' => $beforeMetadata, 'reason' => ''];
            }
        }

        return ['available' => false, 'event_id' => null, 'value' => null, 'label' => '', 'metadata' => null, 'reason' => 'Nessuna modifica precedente da annullare.'];
    }

    private function item(Journey $journey, int $id): JourneyExecutionItem
    {
        return JourneyExecutionItem::whereIn('order_execution_id', $journey->orderExecutions()->where('status', 'pending')->select('id'))->findOrFail($id);
    }

    private function related(string $field): ?string
    {
        return match ($field) {
            'cer_code_id' => 'cer_code', 'holder_id' => 'holder', default => null
        };
    }

    private function path(JourneyExecutionItem $item, string $field): string
    {
        return 'items.'.$item->id.'.'.(in_array($field, self::COLUMNS, true) ? $field : 'data.'.$field);
    }

    private function same(string $field, mixed $left, mixed $right, ?array $leftMeta, ?array $rightMeta): bool
    {
        $equal = in_array($field, ['removed', 'is_bulk'], true) ? (bool) $left === (bool) $right
            : (is_numeric($left) && is_numeric($right) ? (float) $left === (float) $right : ($left ?? '') === ($right ?? ''));
        $clean = fn ($data) => Arr::sortRecursive(Arr::except($data ?? [], ['created_at', 'updated_at', 'deleted_at']));

        return $equal && $clean($leftMeta) === $clean($rightMeta);
    }

    private function label(string $field, mixed $value, ?array $metadata): string
    {
        if ($field === 'removed') {
            return $value ? 'Materiale rimosso' : 'Materiale presente';
        }
        if ($field === 'is_bulk') {
            return $value ? 'Sfuso' : 'In contenitori';
        }
        if ($value === null || $value === '') {
            return in_array($field, ['actual_containers', 'actual_weight_kg'], true) ? 'Non ancora registrato' : 'Non indicato';
        }
        if ($field === 'cer_code_id') {
            return trim(($metadata['code'] ?? '').' — '.($metadata['description'] ?? ''), ' —') ?: 'CER non disponibile';
        }
        if ($field === 'holder_id') {
            return $metadata['name'] ?? 'Contenitore non disponibile';
        }
        if ($field === 'cargo_location') {
            return ['vehicle' => 'Motrice', 'trailer' => 'Rimorchio', 'fulfill' => 'Completamento carico'][$value] ?? (string) $value;
        }
        if (in_array($field, ['weight_declared', 'actual_weight_kg'], true)) {
            return rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',').' kg';
        }

        return (string) $value;
    }
}
