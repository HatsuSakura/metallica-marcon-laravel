<?php

namespace App\Services\JourneyExecution;

class ExecutionUpdateChanges
{
    private const LABELS = [
        'notes' => 'Note', 'description' => 'Descrizione', 'address_text' => 'Indirizzo', 'sequence' => 'Posizione tappa',
        'journey_status' => 'Stato viaggio', 'order_item_group_label' => 'Gruppo materiale', 'hidden_from_driver' => 'Nascosto dalla lista materiali',
        'status' => 'Stato tappa', 'reason_code' => 'Motivo', 'driver_notes' => 'Note autista', 'cer_code' => 'CER',
        'holder' => 'Contenitore', 'holder_quantity' => 'Contenitori previsti', 'weight_declared' => 'Peso previsto (kg)',
        'actual_containers' => 'Contenitori ritirati', 'actual_weight_kg' => 'Peso ritirato (kg)', 'is_bulk' => 'Sfuso',
        'cargo_location' => 'Carico', 'warehouse' => 'Magazzino', 'removed' => 'Materiale rimosso',
        'is_loaded' => 'Caricato', 'vehicle_containers' => 'Contenitori in motrice', 'trailer_containers' => 'Contenitori in rimorchio',
        'weight_gross' => 'Peso lordo (kg)', 'weight_tare' => 'Tara (kg)', 'weight_net' => 'Peso netto (kg)', 'warehouse_status' => 'Stato magazzino', 'warehouse_notes' => 'Note magazzino',
        'total_holders_count' => 'Contenitori da consegnare previsti', 'actual_delivered' => 'Contenitori consegnati',
    ];

    public function between(array $before, array $after, bool $againstPlan = false): array
    {
        $changes = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            $meta = $new ?? $old;
            if (! $old || ! $new) {
                $changes[] = array_diff_key($meta, ['values' => true]) + ['record_key' => $key, 'field' => 'record', 'field_label' => $old ? 'Rimosso' : 'Aggiunto', 'before' => $old ? 'Presente' : null, 'after' => $new ? 'Presente' : null];
                if (! $new) {
                    continue;
                }
            }
            foreach ($new['values'] as $field => $value) {
                if ($field === 'is_loaded' || ($againstPlan && in_array($field, ['status', 'journey_status', 'reason_code', 'driver_notes', 'hidden_from_driver'], true))) {
                    continue;
                }
                $previous = $old['values'][$field] ?? null;
                if ($this->same($previous, $value)) {
                    continue;
                }
                $changes[] = array_diff_key($meta, ['values' => true]) + ['record_key' => $key, 'field' => $field, 'field_label' => self::LABELS[$field] ?? $field, 'before' => $previous, 'after' => $value];
            }
        }

        return $changes;
    }

    private function same(mixed $a, mixed $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if (($a === null || $a === '') && ($b === null || $b === '')) {
            return true;
        }

        return is_numeric($a) && is_numeric($b) && (float) $a === (float) $b;
    }
}
