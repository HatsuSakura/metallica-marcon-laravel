<?php

namespace App\Services\JourneyExecution;

use Illuminate\Support\Collection;

/** Outbound containers loaded for the departure, less recorded deliveries. */
class ContainerBalance
{
    public function calculate(array $departureOrders, Collection $executions): array
    {
        $groups = [];
        foreach ($departureOrders as $order) {
            foreach ($order['holders'] ?? [] as $holder) {
                $key = $this->key($holder, $holder['id'] ?? null);
                $groups[$key] ??= $this->group($key, $holder);
                $quantity = $this->quantity($holder['total_holders_count'] ?? null);
                if ($quantity === null) {
                    $groups[$key]['missing'] = true;
                } else {
                    $groups[$key]['initial_quantity'] += $quantity;
                }
            }
        }
        foreach ($executions as $execution) {
            foreach ($execution->holders as $holder) {
                $key = $this->key($holder->data, $holder->source_order_holder_id, $holder->id);
                $groups[$key] ??= $this->group($key, $holder->data);
                $quantity = $this->quantity($holder->actual_delivered);
                if ($quantity !== null) {
                    $groups[$key]['delivered_quantity'] += $quantity;
                } elseif ($execution->status === 'completed') {
                    $groups[$key]['missing'] = true;
                }
            }
        }
        foreach ($groups as &$group) {
            $group['quantity'] = $group['missing'] ? null : $group['initial_quantity'] - $group['delivered_quantity'];
        }
        unset($group);

        return ['groups' => array_values($groups), 'has_shortage' => collect($groups)->contains(fn ($group) => ! $group['missing'] && $group['quantity'] < 0)];
    }

    public function assertAvailable(array $departureOrders, Collection $executions): void
    {
        foreach ($this->calculate($departureOrders, $executions)['groups'] as $group) {
            abort_if($group['missing'] && $group['delivered_quantity'] > 0, 422,
                "Disponibilità di {$group['type']} non determinabile: verifica i vuoti prima di confermare la consegna.");
            abort_if(!$group['missing'] && $group['quantity'] < 0, 422,
                "Non puoi consegnare più {$group['type']} dei vuoti disponibili: dotazione {$group['initial_quantity']}, totale consegnato richiesto {$group['delivered_quantity']}. Riduci la consegna.");
        }
    }

    private function key(array $holder, mixed $sourceId, ?int $executionId = null): string
    {
        if (($holder['holder_id'] ?? null) !== null) {
            return 'holder:'.$holder['holder_id'];
        }

        return $sourceId !== null ? 'unknown:'.$sourceId : 'execution:'.$executionId;
    }

    private function group(string $key, array $holder): array
    {
        return ['key' => $key, 'holder_id' => $holder['holder_id'] ?? null,
            'type' => $holder['holder']['name'] ?? 'Contenitore non specificato',
            'bulk' => false, 'initial_quantity' => 0, 'delivered_quantity' => 0, 'quantity' => 0, 'missing' => false];
    }

    private function quantity(mixed $value): ?int
    {
        if (! is_numeric($value) || $value < 0 || floor((float) $value) !== (float) $value) {
            return null;
        }

        return (int) $value;
    }
}
