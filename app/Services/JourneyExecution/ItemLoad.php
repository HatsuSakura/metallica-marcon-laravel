<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\JourneyExecutionItem;

/** One execution material; carrier allocations and the driver's load check are separate facts. */
class ItemLoad
{
    public function total(JourneyExecutionItem $item): ?int
    {
        $value = $item->actual_containers ?? $item->data['holder_quantity'] ?? null;

        return is_numeric($value) && $value >= 0 && floor((float) $value) === (float) $value ? (int) $value : null;
    }

    public function valid(JourneyExecutionItem $item): bool
    {
        $item->loadMissing('allocations');

        return $item->allocations->isEmpty() || (! ($item->data['is_bulk'] ?? false) && $this->total($item) !== null
            && $item->allocations->sum('containers') === $this->total($item));
    }

    public function split(Journey $journey, JourneyExecutionItem $item, array $input): void
    {
        abort_unless($journey->trailer_id, 422, 'Questo viaggio non ha un rimorchio.');
        abort_if($item->removed || ($item->data['is_bulk'] ?? false), 422, 'Suddivisione disponibile soltanto per materiali in contenitori.');
        $total = $this->total($item);
        abort_unless($total !== null && $total > 1, 422, 'Servono almeno due contenitori per suddividere il carico.');
        foreach (['vehicle_containers', 'trailer_containers'] as $field) {
            abort_unless(isset($input[$field]) && is_int($input[$field]) && $input[$field] >= 0, 422, 'Le quantità devono essere interi non negativi.');
        }
        abort_unless($total === $input['vehicle_containers'] + $input['trailer_containers'], 422, 'La somma su motrice e rimorchio deve coincidere con il totale del materiale.');
        $item->loadMissing('allocations');
        $changed = $item->allocations->count() !== 2;
        foreach (['vehicle', 'trailer'] as $location) {
            $quantity = $input[$location.'_containers'];
            $changed = $changed || $item->allocations->firstWhere('cargo_location', $location)?->containers !== $quantity;
            $item->allocations()->updateOrCreate(['cargo_location' => $location], ['containers' => $quantity]);
        }
        $item->data = array_replace($item->data, ['cargo_location' => null]);
        if ($changed) {
            $item->is_loaded = false;
        }
        $item->save();
    }

    public function check(JourneyExecutionItem $item, bool $loaded): void
    {
        abort_if($item->removed, 422, 'Il materiale è stato rimosso.');
        abort_if($loaded && ! $this->valid($item), 422, 'Aggiorna la suddivisione del carico prima di confermare Caricato.');
        $item->is_loaded = $loaded;
        $item->save();
    }
}
