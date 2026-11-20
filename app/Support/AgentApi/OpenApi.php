<?php

namespace App\Support\AgentApi;

class OpenApi
{
    private static function ref(string $name): array
    {
        return ['$ref' => '#/components/schemas/'.$name];
    }

    private static function arrayOf(array $items): array
    {
        return ['type' => 'array', 'items' => $items];
    }

    public function schemas(): array
    {
        $integer = ['type' => 'integer', 'minimum' => 1];
        $number = ['type' => 'number'];
        $string = ['type' => 'string'];
        $nullableId = ['type' => ['integer', 'null']];
        $boolean = ['type' => 'boolean'];
        $item = [
            'id' => $nullableId, 'cer_code_id' => $integer, 'order_item_group_id' => $nullableId, 'order_item_group_label' => ['type' => ['string', 'null'], 'maxLength' => 120],
            'holder_id' => $nullableId, 'holder_quantity' => ['type' => ['integer', 'null'], 'minimum' => 0], 'is_bulk' => $boolean, 'description' => $string, 'weight_declared' => $number, 'warehouse_id' => $integer,
            'custom_l_cm' => $number, 'custom_w_cm' => $number, 'custom_h_cm' => $number, 'adr' => $boolean, 'adr_hp' => $string, 'adr_un_code' => $string, 'adr_lot_code' => $string, 'adr_volume' => $number,
            'is_adr_total' => $boolean, 'has_adr_total_exemption' => $boolean, 'has_adr_partial_exemption' => $boolean,
        ];
        $holder = ['holder_id' => $integer, 'filled_holders_count' => ['type' => 'integer', 'minimum' => 0], 'empty_holders_count' => ['type' => 'integer'], 'total_holders_count' => ['type' => 'integer']];
        $order = [
            'customer_id' => $integer, 'site_id' => $integer, 'logistics_user_id' => $nullableId, 'is_urgent' => $boolean,
            'requested_at' => ['type' => 'string', 'example' => '2026-11-21 09:00:00'],
            'expected_withdraw_at' => ['type' => 'string', 'example' => '2026-11-23 08:00:00'],
            'fixed_withdraw_at' => ['type' => ['string', 'null']], 'notes' => ['type' => ['string', 'null']],
            'items' => self::arrayOf(self::ref('AgentOrderItemInput')), 'holders' => self::arrayOf(self::ref('AgentHolderInput')),
        ];
        $stop = ['kind' => ['type' => 'string', 'enum' => ['customer', 'technical']], 'sequence' => $integer, 'planned_sequence' => $integer, 'customer_id' => $nullableId, 'customer_visit_index' => $integer, 'technical_action_id' => $nullableId, 'location_lat' => $number, 'location_lng' => $number, 'description' => $string, 'address_text' => $string, 'orders' => self::arrayOf($integer)];
        $journey = ['planned_start_at' => ['type' => 'string', 'example' => '2026-11-23 08:00:00'], 'planned_end_at' => ['type' => 'string', 'example' => '2026-11-23 18:00:00'], 'driver_id' => $integer, 'vehicle_id' => $integer, 'vehicle_cargo_id' => $integer, 'trailer_id' => $nullableId, 'trailer_cargo_id' => $nullableId, 'logistics_user_id' => $nullableId, 'notes' => ['type' => ['string', 'null']], 'orders_truck' => self::arrayOf($integer), 'orders_trailer' => self::arrayOf($integer), 'orders_fulfill' => self::arrayOf($integer), 'stops' => self::arrayOf(self::ref('AgentStopInput'))];
        $object = fn ($props, $required = []) => ['type' => 'object', 'properties' => $props, 'required' => $required];
        $version = ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$', 'description' => 'Versione opaca: inviarla in If-Match prima di modificare.'];
        $orderOutput = $order + ['id' => $integer, 'legacy_code' => $string, 'journey_id' => $nullableId, 'cargo_location' => ['type' => ['string', 'null']], 'status' => ['type' => 'string', 'enum' => ['creato', 'pronto', 'pianificato', 'eseguito', 'scaricato', 'chiuso']], 'updated_at' => ['type' => ['string', 'null']], 'version' => $version];
        $journeyOutput = $journey + ['id' => $integer, 'status' => ['type' => 'string', 'enum' => ['creato', 'attivo', 'eseguito', 'chiuso']], 'plan_version' => ['type' => ['integer', 'null']], 'actual_start_at' => ['type' => ['string', 'null']], 'actual_end_at' => ['type' => ['string', 'null']], 'updated_at' => ['type' => ['string', 'null']], 'version' => $version];

        return [
            'AgentOrderItemInput' => $object($item, ['cer_code_id', 'is_bulk', 'description', 'weight_declared', 'warehouse_id']),
            'AgentOrderItemPatch' => $object($item),
            'AgentHolderInput' => $object($holder, array_keys($holder)),
            'AgentOrderCreate' => $object($order, ['customer_id', 'site_id', 'is_urgent', 'requested_at', 'expected_withdraw_at']),
            'AgentOrderPatch' => $object($order),
            'AgentOrder' => $object($orderOutput),
            'AgentJourney' => $object($journeyOutput),
            'AgentStopInput' => $object($stop, ['kind', 'sequence']),
            'AgentJourneyCreate' => $object($journey, ['planned_start_at', 'planned_end_at', 'driver_id', 'vehicle_id', 'vehicle_cargo_id', 'stops']),
            'AgentJourneyPatch' => $object($journey),
            'AgentError' => $object(['message' => $string, 'errors' => ['type' => 'object', 'additionalProperties' => self::arrayOf($string)]]),
        ];
    }

    public function operation(string $name, string $method): array
    {
        if (str_starts_with($name, 'cer-codes.')) return $this->cerOperation($name);

        $read = $method === 'GET';
        $item = str_starts_with($name, 'items.');
        $journey = str_starts_with($name, 'journeys.');
        $reference = $name === 'references.index';
        $resource = $journey ? 'journeys' : 'orders';
        $action = explode('.', $name)[1];
        $ability = $reference ? 'reference:read' : $resource.':'.($read ? 'read' : ($action === 'store' && ! $item ? 'create' : 'update'));
        $summary = [
            'orders.index' => 'Elenca ordini', 'orders.show' => 'Leggi ordine con materiali e versione', 'orders.store' => 'Crea ordine, materiali e contenitori in una transazione', 'orders.update' => 'Modifica ordine prima della partenza',
            'journeys.index' => 'Elenca viaggi', 'journeys.show' => 'Leggi piano e versione viaggio', 'journeys.store' => 'Crea piano di viaggio', 'journeys.update' => 'Modifica piano prima della partenza',
            'items.show' => 'Leggi materiale e versione ordine', 'items.index' => 'Elenca materiali e versione ordine', 'items.store' => 'Aggiungi materiale all’ordine', 'items.update' => 'Modifica materiale dell’ordine', 'items.destroy' => 'Rimuovi materiale conservando lo storico',
            'references.index' => 'Consulta anagrafiche operative',
        ];
        $op = ['tags' => [$reference ? 'API agenti: Anagrafiche' : ($item ? 'API agenti: Materiali ordini' : ($journey ? 'API agenti: Viaggi' : 'API agenti: Ordini'))],
            'summary' => $summary[$name], 'operationId' => 'agent'.str_replace(' ', '', ucwords(str_replace('.', ' ', $name))),
            'security' => [['agentBearer' => []]], 'x-required-ability' => $ability,
            'description' => "Bearer token agente obbligatorio. Permesso richiesto sia sull’agente sia sul token: `{$ability}`. Nessun accesso tramite sessione browser. Le scritture applicano direttamente dati operativi: il workflow email di bozze/proposte e approvazione è separato e non è implementato da questi endpoint.",
            'parameters' => [], 'responses' => []];
        $status = ! $read && $action === 'store' ? '201' : '200';
        $shape = $journey ? self::ref('AgentJourney') : self::ref('AgentOrder');
        if ($reference) {
            $shape = ['type' => 'object', 'description' => 'Record con campi specifici dell’anagrafica selezionata. Nessuna credenziale o dato di autenticazione.'];
        }
        $output = ['type' => 'object', 'properties' => ['data' => $shape]];
        if ($action === 'index') {
            $output = ['type' => 'object', 'properties' => ['data' => self::arrayOf($item ? self::ref('AgentOrderItemInput') : $shape)]];
        }
        if ($action === 'index' && ! $item) {
            $output['properties'] += ['current_page' => ['type' => 'integer'], 'last_page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'], 'total' => ['type' => 'integer'], 'next_page_url' => ['type' => ['string', 'null']], 'prev_page_url' => ['type' => ['string', 'null']]];
        }
        if ($item && $action === 'show') {
            $output['properties']['data'] = self::ref('AgentOrderItemInput');
        }
        if ($item && $read) {
            $output['properties']['order_version'] = ['type' => 'string'];
        }
        $op['responses'][$status] = ['description' => 'Operazione completata', 'content' => ['application/json' => ['schema' => $output]], 'headers' => ['ETag' => ['description' => 'Versione ordine/viaggio, nelle risposte di dettaglio e scrittura', 'schema' => ['type' => 'string']]]];
        foreach (['401' => 'Token mancante, scaduto o revocato', '403' => 'Agente disabilitato o permesso insufficiente', '404' => 'Risorsa non trovata o materiale di un altro ordine', '409' => 'Versione superata, chiave idempotenza riutilizzata, ordine già assegnato o piano non modificabile', '422' => 'Payload non valido o Idempotency-Key mancante', '428' => 'If-Match mancante', '429' => 'Limite richieste superato'] as $code => $description) {
            $op['responses'][$code] = ['description' => $description, 'content' => ['application/json' => ['schema' => self::ref('AgentError')]]];
        }
        if (! $read) {
            $op['parameters'][] = ['name' => 'Idempotency-Key', 'in' => 'header', 'required' => true, 'schema' => ['type' => 'string', 'minLength' => 8, 'maxLength' => 200], 'description' => 'Chiave univoca per operazione. Ripetere la stessa richiesta con la stessa chiave evita duplicazioni. Una chiave diversa esegue una nuova operazione.'];
            if ($action !== 'store' || $item) {
                $op['parameters'][] = ['name' => 'If-Match', 'in' => 'header', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Versione letta con GET (ETag o campo version dell’ordine).'];
            }
            foreach (['X-Agent-Run-Id', 'X-Source-Reference'] as $header) {
                $op['parameters'][] = ['name' => $header, 'in' => 'header', 'required' => false, 'schema' => ['type' => 'string', 'maxLength' => 200], 'description' => 'Riferimento opaco per tracciatura; non inserire contenuti email o segreti.'];
            }
            if ($method !== 'DELETE') {
                $schema = $item ? ($action === 'update' ? 'AgentOrderItemPatch' : 'AgentOrderItemInput') : ($journey ? 'AgentJourney' : 'AgentOrder').($action === 'store' ? 'Create' : 'Patch');
                $op['requestBody'] = ['required' => true, 'content' => ['application/json' => ['schema' => self::ref($schema)]]];
            }
            $op['description'] .= ' Le liste items e holders inviate in PATCH ordine sostituiscono interamente le liste precedenti; se omesse vengono conservate. Le modifiche ai materiali richiedono la versione dell’ordine padre. HP è obbligatorio per CER pericolosi; warehouse_id è richiesto quando si aggiornano materiali. Le date devono usare la convenzione temporale dell’applicazione.';
        }
        if ($action === 'index' && ! $item) {
            $op['parameters'][] = ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 25]];
            $op['parameters'][] = ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]];
            if (! $journey) {
                $op['parameters'][] = ['name' => 'customer_id', 'in' => 'query', 'schema' => ['type' => 'integer']];
            }
            if ($reference) {
                $op['parameters'][] = ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string', 'maxLength' => 200]];
            } else {
                $op['parameters'][] = ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']];
            }
        }

        if ($name === 'references.index') {
            // Resolve enum rather than leaving resource as an unconstrained string.
            $op['description'] .= ' Risorse: customers, sites, cer-codes, holders, vehicles, trailers, cargos, warehouses, drivers, technical-actions. Nessun elenco utenti generico.';
        }
        if ($name === 'orders.store') {
            $op['requestBody']['content']['application/json']['example'] = [
                'customer_id' => 1, 'site_id' => 1, 'is_urgent' => false,
                'requested_at' => '2026-11-21 09:00:00', 'expected_withdraw_at' => '2026-11-23 08:00:00',
                'notes' => 'Esempio: sostituire gli ID con quelli delle anagrafiche', 'items' => [], 'holders' => [],
            ];
        }
        if ($name === 'journeys.store') {
            $op['requestBody']['content']['application/json']['example'] = [
                'planned_start_at' => '2026-11-23 08:00:00', 'planned_end_at' => '2026-11-23 18:00:00',
                'driver_id' => 1, 'vehicle_id' => 1, 'vehicle_cargo_id' => 1, 'orders_truck' => [7],
                'stops' => [['kind' => 'customer', 'sequence' => 1, 'customer_id' => 1, 'orders' => [7]]],
            ];
        }
        return $op;
    }
    private function cerOperation(string $name): array
    {
        $list = $name === 'cer-codes.index';
        $record = ['type' => 'object', 'required' => ['id', 'code', 'description', 'is_dangerous', 'is_active'], 'properties' => [
            'id' => ['type' => 'integer'], 'code' => ['type' => 'string'], 'description' => ['type' => 'string'], 'is_dangerous' => ['type' => 'boolean'], 'is_active' => ['type' => 'boolean'],
        ]];
        $body = ['type' => 'object', 'properties' => ['data' => $list ? self::arrayOf($record) : $record]];
        if ($list) $body['properties'] += ['current_page' => ['type' => 'integer'], 'last_page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'], 'total' => ['type' => 'integer'], 'next_page_url' => ['type' => ['string', 'null']]];
        $op = [
            'tags' => ['API agenti: Codici CER'], 'summary' => $list ? 'Cerca ed elenca codici CER' : 'Leggi codice CER',
            'operationId' => $list ? 'agentCerCodesIndex' : 'agentCerCodesShow',
            'description' => 'Sola lettura. Richiede Bearer token valido e permesso cer-codes:read sia sull’agente sia sul token. Non permette inserimento, modifica o eliminazione dei CER. Gli ID sono gli stessi utilizzati nei materiali degli ordini. is_active distingue i codici utilizzabili in nuove righe da quelli disattivati e conservati per lo storico. Una riga esistente può mantenere il proprio CER disattivato; nuovi abbinamenti sono rifiutati.',
            'security' => [['agentBearer' => []]], 'x-required-ability' => 'cer-codes:read', 'parameters' => [],
            'responses' => ['200' => ['description' => 'Codici CER', 'content' => ['application/json' => ['schema' => $body]]]],
        ];
        foreach (['401' => 'Token mancante, scaduto o revocato', '403' => 'Permesso insufficiente o agente disabilitato', '404' => 'Codice CER non trovato', '422' => 'Filtri non validi', '429' => 'Limite richieste superato'] as $status => $description) {
            $op['responses'][$status] = ['description' => $description, 'content' => ['application/json' => ['schema' => self::ref('AgentError')]]];
        }
        if ($list) $op['parameters'] = [
            ['name' => 'search', 'in' => 'query', 'description' => 'Codice o descrizione. Il codice può essere cercato anche senza spazi o asterisco.', 'schema' => ['type' => 'string', 'maxLength' => 200]],
            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
            ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 25]],
        ];
        return $op;
    }

}
