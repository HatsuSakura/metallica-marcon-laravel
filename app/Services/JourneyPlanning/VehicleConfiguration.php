<?php

namespace App\Services\JourneyPlanning;

use App\Models\Cargo;
use App\Models\Trailer;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

class VehicleConfiguration
{
    public function validate(array $input): void
    {
        $vehicle = Vehicle::find($input['vehicle_id'] ?? null);
        $cargo = Cargo::find($input['vehicle_cargo_id'] ?? null);
        $trailer = empty($input['trailer_id']) ? null : Trailer::find($input['trailer_id']);
        $trailerCargo = empty($input['trailer_cargo_id']) ? null : Cargo::find($input['trailer_cargo_id']);
        $errors = [];
        $fixed = ['sponda' => 'Sponda', 'furgone' => 'Furgone'][$vehicle?->type ?? ''] ?? null;
        if (!$vehicle) $errors['vehicle_id'] = 'Seleziona una motrice valida.';
        if (!$cargo) $errors['vehicle_cargo_id'] = 'Seleziona il cassone della motrice.';
        elseif ($fixed ? $cargo->name !== $fixed : in_array($cargo->name, ['Sponda', 'Furgone'], true)) $errors['vehicle_cargo_id'] = 'Cassone non compatibile con la motrice.';
        if (!empty($input['trailer_id'])) {
            if (!$vehicle || $fixed || !$vehicle->has_trailer) $errors['trailer_id'] = 'Questa motrice non può avere un rimorchio.';
            elseif (!$trailer) $errors['trailer_id'] = 'Seleziona un rimorchio valido.';
            if (!$trailerCargo) $errors['trailer_cargo_id'] = 'Seleziona il cassone del rimorchio.';
            elseif (in_array($trailerCargo->name, ['Sponda', 'Furgone'], true)) $errors['trailer_cargo_id'] = 'Cassone non compatibile con il rimorchio.';
            elseif ($cargo?->is_long && $trailerCargo->is_long) $errors['trailer_cargo_id'] = 'Il convoglio non può avere due cassoni lunghi.';
        } else {
            if (!empty($input['trailer_cargo_id'])) $errors['trailer_cargo_id'] = 'Il cassone rimorchio richiede un rimorchio.';
            if (!empty($input['orders_trailer']) || !empty($input['orders_fulfill'])) $errors['orders_trailer'] = 'Riassegna gli ordini del rimorchio e del riempimento prima di rimuoverlo.';
        }
        if ($errors) throw ValidationException::withMessages($errors);
    }
}
