<?php

namespace App\Services\Planning;

use App\Enums\OrderStatus;
use App\Models\CerCode;
use App\Models\Order;
use App\Models\OrderItemGroup;
use App\Models\Site;
use App\Services\OrderDocumentGenerationService;
use App\Services\OrderItemGroupResolver;
use App\Support\Orders\FixedWithdrawSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderWriter
{
    public function create(array $input): Order
    {
        return DB::transaction(function () use ($input) {
            $CUSTOM_HOLDER_ID = 1;

            $validator = Validator::make($input, [
                'is_urgent' => 'required|boolean',
                'requested_at' => 'required|date',
                'expected_withdraw_at' => 'required|date',
                'fixed_withdraw_at' => 'nullable|date',
                'notes' => 'nullable|string',
                'customer_id' => 'required|integer|exists:customers,id',
                'site_id' => 'required|integer|exists:sites,id',
                'logistics_user_id' => 'nullable|integer|exists:users,id',
                'post_action' => ['nullable', Rule::in(['create_exit', 'create_stay'])],
                // 'journey_id' => ALLA CREAZIONE, al momento soprattutto, non viene passato e prende il default NULL
                'items' => 'nullable|array', // Make items optional
                'items.*.cer_code_id' => ['required', 'integer', new \App\Rules\AvailableCerCode],
                'items.*.order_item_group_id' => 'nullable|exists:order_item_groups,id',
                'items.*.order_item_group_label' => 'nullable|string|max:120',
                'items.*.is_bulk' => 'required|boolean',
                // se sfuso, ignoriamo il campo; se NON sfuso, è richiesto e min:1
                'items.*.holder_quantity' => 'nullable|integer|min:0',
                'items.*.holder_id' => 'nullable|integer|exists:holders,id',

                'items.*.custom_l_cm' => [
                    'nullable', 'numeric', 'gt:0',
                    "required_if:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                    "exclude_unless:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                    'prohibited_if:items.*.is_bulk,1',
                ],
                'items.*.custom_w_cm' => [
                    'nullable', 'numeric', 'gt:0',
                    "required_if:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                    "exclude_unless:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                    'prohibited_if:items.*.is_bulk,1',
                ],
                'items.*.custom_h_cm' => [
                    'nullable', 'numeric', 'gt:0',
                    "required_if:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                    "exclude_unless:items.*.holder_id,{$CUSTOM_HOLDER_ID}",
                    'prohibited_if:items.*.is_bulk,1',
                ],

                'items.*.description' => 'required|string',
                'items.*.weight_declared' => 'required|numeric|min:0',
                'items.*.weight_gross' => 'nullable|numeric',
                'items.*.weight_tare' => 'nullable|numeric',
                'items.*.weight_net' => 'nullable|numeric',
                'items.*.adr' => 'nullable|boolean',
                'items.*.adr_un_code' => 'nullable|string',
                'items.*.adr_hp' => 'nullable|string',
                'items.*.adr_lot_code' => 'nullable|string',
                'items.*.adr_volume' => 'nullable|numeric',
                'items.*.warehouse_id' => 'required|integer|exists:warehouses,id',
                'items.*.warehouse_notes' => 'nullable|string',
                'items.*.worker_id' => 'nullable|numeric',
                'items.*.selection_duration_minutes' => 'nullable|numeric',
                'items.*.machinery_time_share' => 'nullable|numeric',
                'items.*.recognized_price' => 'nullable|numeric',
                'items.*.recognized_weight' => 'nullable|numeric',
                'items.*.is_adr_total' => 'nullable|boolean',
                'items.*.has_adr_total_exemption' => 'nullable|boolean',
                'items.*.has_adr_partial_exemption' => 'nullable|boolean',
                'holders' => 'nullable|array', // Make holders optional
                'holders.*.holder_id' => 'required|exists:holders,id',
                'holders.*.filled_holders_count' => 'required|integer',
                'holders.*.empty_holders_count' => 'required|integer',
                'holders.*.total_holders_count' => 'required|integer',
            ]);

            $validatedData = $validator->validate();
            $this->validateOrderItemBusinessRules($validatedData);
            if (! Site::whereKey($validatedData['site_id'])->where('customer_id', $validatedData['customer_id'])->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['site_id' => 'La sede non appartiene al cliente.']);
            }

            foreach (($validatedData['items'] ?? []) as $item) {
                if (! empty($item['order_item_group_id'])) {
                    $group = OrderItemGroup::find($item['order_item_group_id']);
                    if (! $group || ! isset($order) || (int) $group->order_id !== (int) $order->id || (int) $group->cer_code_id !== (int) $item['cer_code_id']) {
                        throw ValidationException::withMessages(['items' => 'Gruppo materiale non appartenente a questo ordine o CER.']);
                    }
                }
            }
            $validatedData = FixedWithdrawSynchronizer::synchronize($validatedData);

            $order = Order::create([
                'is_urgent' => $validatedData['is_urgent'] ?? false,
                'requested_at' => $validatedData['requested_at'],
                'expected_withdraw_at' => $validatedData['expected_withdraw_at'] ?? null,
                'fixed_withdraw_at' => $validatedData['fixed_withdraw_at'] ?? null,
                'notes' => $validatedData['notes'] ?? null,
                'customer_id' => $validatedData['customer_id'],
                'site_id' => $validatedData['site_id'],
                'logistics_user_id' => $validatedData['logistics_user_id'] ?? null,
                'status' => OrderStatus::STATUS_CREATED->value,
            ]);

            // If there are items, attach them to the order
            if (! empty($validatedData['items'])) {
                $resolvedItems = app(OrderItemGroupResolver::class)->resolveForOrder($order, $validatedData['items']);
                foreach ($resolvedItems as $item) {
                    $order->items()->create($item);
                }
            }

            // If there are holders, attach them to the order
            if (! empty($validatedData['holders'])) {
                foreach ($validatedData['holders'] as &$holder) {
                    $order->holders()->create($holder);
                }
            }

            return $order;
        });
    }

    public function update(Order $order, array $input): Order
    {
        return DB::transaction(function () use ($order, $input) {
            app(PlanningGuard::class)->order($order);
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_if(array_key_exists('journey_id', $input) && (int) $input['journey_id'] !== (int) $order->journey_id, 422, 'Assegna o rimuovi ordini dalla gestione del viaggio.');
            $validator = Validator::make($input, [
                'is_urgent' => 'required|boolean',
                'requested_at' => 'required|date',
                'expected_withdraw_at' => 'required|date',
                'fixed_withdraw_at' => 'nullable|date',
                'notes' => 'nullable|string',
                'customer_id' => 'required|integer|exists:customers,id',
                'site_id' => 'required|integer|exists:sites,id',
                'logistics_user_id' => 'nullable|integer|exists:users,id',
                'journey_id' => 'nullable|integer',
                'post_action' => ['nullable', Rule::in(['save_exit', 'save_stay'])],
                'items' => 'nullable|array', // Make items optional
                'items.*.id' => 'nullable|integer',
                'items.*.cer_code_id' => ['required', 'integer', new \App\Rules\AvailableCerCode($order)],
                'items.*.order_item_group_id' => 'nullable|exists:order_item_groups,id',
                'items.*.order_item_group_label' => 'nullable|string|max:120',
                'items.*.is_bulk' => 'required|boolean',
                'items.*.holder_id' => 'nullable|integer|exists:holders,id',
                'items.*.holder_quantity' => 'nullable|integer|min:0',
                'items.*.custom_l_cm' => ['nullable', 'numeric', 'gt:0', 'required_if:items.*.holder_id,1', 'exclude_unless:items.*.holder_id,1', 'prohibited_if:items.*.is_bulk,1'],
                'items.*.custom_w_cm' => ['nullable', 'numeric', 'gt:0', 'required_if:items.*.holder_id,1', 'exclude_unless:items.*.holder_id,1', 'prohibited_if:items.*.is_bulk,1'],
                'items.*.custom_h_cm' => ['nullable', 'numeric', 'gt:0', 'required_if:items.*.holder_id,1', 'exclude_unless:items.*.holder_id,1', 'prohibited_if:items.*.is_bulk,1'],
                'items.*.description' => 'required|string',
                'items.*.weight_declared' => 'required|numeric|min:0',
                'items.*.weight_gross' => 'nullable|numeric',
                'items.*.weight_tare' => 'nullable|numeric',
                'items.*.weight_net' => 'nullable|numeric',
                'items.*.adr' => 'nullable|boolean',
                'items.*.adr_un_code' => 'nullable|string',
                'items.*.adr_hp' => 'nullable|string',
                'items.*.adr_lot_code' => 'nullable|string',
                'items.*.adr_volume' => 'nullable|numeric',
                'items.*.warehouse_id' => 'required|integer|exists:warehouses,id',
                'items.*.warehouse_notes' => 'nullable|string',
                'items.*.worker_id' => 'nullable|numeric',
                'items.*.selection_duration_minutes' => 'nullable|numeric',
                'items.*.machinery_time_share' => 'nullable|numeric',
                'items.*.recognized_price' => 'nullable|numeric',
                'items.*.recognized_weight' => 'nullable|numeric',
                'items.*.is_adr_total' => 'nullable|boolean',
                'items.*.has_adr_total_exemption' => 'nullable|boolean',
                'items.*.has_adr_partial_exemption' => 'nullable|boolean',
                'holders' => 'nullable|array', // Make holders optional
                'holders.*.holder_id' => 'required|exists:holders,id',
                'holders.*.filled_holders_count' => 'required|integer|min:0',
                'holders.*.empty_holders_count' => 'required|integer',
                'holders.*.total_holders_count' => 'required|integer',
            ]);

            $validatedData = $validator->validate();
            $this->validateOrderItemBusinessRules($validatedData);
            if (! Site::whereKey($validatedData['site_id'])->where('customer_id', $validatedData['customer_id'])->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['site_id' => 'La sede non appartiene al cliente.']);
            }

            foreach (($validatedData['items'] ?? []) as $item) {
                if (! empty($item['order_item_group_id'])) {
                    $group = OrderItemGroup::find($item['order_item_group_id']);
                    if (! $group || ! isset($order) || (int) $group->order_id !== (int) $order->id || (int) $group->cer_code_id !== (int) $item['cer_code_id']) {
                        throw ValidationException::withMessages(['items' => 'Gruppo materiale non appartenente a questo ordine o CER.']);
                    }
                }
            }
            $validatedData = FixedWithdrawSynchronizer::synchronize($validatedData);

            foreach (($validatedData['items'] ?? []) as $item) {
                if (! empty($item['id']) && ! $order->items()->whereKey($item['id'])->exists()) {
                    throw ValidationException::withMessages(['items' => 'Il materiale non appartiene a questo ordine.']);
                }
            }
            $order->update(array_merge(
                $validatedData,
                [
                    'expected_withdraw_at' => $validatedData['expected_withdraw_at'] ?? null,
                    'fixed_withdraw_at' => $validatedData['fixed_withdraw_at'] ?? null,
                    'logistics_user_id' => $validatedData['logistics_user_id'] ?? null,
                ]
            ));

            // If there are items, update them or create new ones
            if (! empty($validatedData['items'])) {
                $resolvedItems = app(OrderItemGroupResolver::class)->resolveForOrder($order, $validatedData['items']);
                $existingItems = $order->items()->get()->keyBy('id');
                $existingItemIds = $existingItems->keys()->all();
                $newItemIds = array_column($resolvedItems, 'id');

                $itemsToDelete = array_diff($existingItemIds, $newItemIds);
                foreach ($itemsToDelete as $itemId) {
                    $existingItems->get($itemId)?->delete();
                }

                foreach ($resolvedItems as $item) {
                    if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                        $existingItems->get($item['id'])?->update($item);
                    } else {
                        $order->items()->create($item);
                    }
                }
            } else {
                $order->items()->get()->each->delete();
            }

            app(OrderItemGroupResolver::class)->cleanupUnusedGroups($order);

            // If there are holders, update them or create new ones
            if (! empty($validatedData['holders'])) {
                $existingHolders = $order->holders()->get();
                $newHolders = $validatedData['holders'];
                // Log::info($existingHolders);
                // Log::info($newHolders);

                foreach ($newHolders as $index => $holderData) {
                    // Log::info($index);
                    // Log::info($holderData);

                    if (isset($existingHolders[$index])) {
                        // Update existing holder
                        $existingHolders[$index]->update($holderData);
                    } else {
                        // Create new holder
                        $order->holders()->create($holderData);
                    }
                }

                // Delete holders that exceed the new list
                if (count($existingHolders) > count($newHolders)) {
                    $holdersToDelete = $existingHolders->slice(count($newHolders));
                    foreach ($holdersToDelete as $holder) {
                        $holder->delete();
                    }
                }
            } else {
                $order->holders()->get()->each->delete();
            }

            app(OrderDocumentGenerationService::class)->invalidateAfterReadyOrderEdit($order->refresh());

            return $order;
        });
    }

    private function validateOrderItemBusinessRules(array $validatedData): void
    {
        $items = $validatedData['items'] ?? [];
        if (empty($items)) {
            return;
        }

        $cerIds = collect($items)
            ->pluck('cer_code_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $dangerousByCerId = CerCode::query()
            ->whereIn('id', $cerIds)
            ->pluck('is_dangerous', 'id');

        $errors = [];
        foreach ($items as $index => $item) {
            $cerId = (int) ($item['cer_code_id'] ?? 0);
            $isBulk = (bool) ($item['is_bulk'] ?? false);

            if (! $isBulk && empty($item['holder_id'])) {
                $errors["items.$index.holder_id"] = 'Tipo contenitore obbligatorio se il materiale non è sfuso.';
            }

            if (empty($item['warehouse_id'])) {
                $errors["items.$index.warehouse_id"] = 'Magazzino obbligatorio.';
            }

            $requiresHp = (bool) ($dangerousByCerId[$cerId] ?? false);
            $adrHp = trim((string) ($item['adr_hp'] ?? ''));
            if ($requiresHp && $adrHp === '') {
                $errors["items.$index.adr_hp"] = 'HP obbligatorio per CER pericoloso.';
            }

            $adrActive = (bool) ($item['adr'] ?? false);
            if ($adrActive) {
                $adrUnCode = trim((string) ($item['adr_un_code'] ?? ''));
                if ($adrUnCode === '') {
                    $errors["items.$index.adr_un_code"] = 'Codice UN obbligatorio quando ADR è attivo.';
                }

                $hasAnyAdrFlag = (bool) ($item['is_adr_total'] ?? false)
                    || (bool) ($item['has_adr_total_exemption'] ?? false)
                    || (bool) ($item['has_adr_partial_exemption'] ?? false);

                if (! $hasAnyAdrFlag) {
                    $errors["items.$index.adr"] = 'Se ADR è attivo, seleziona almeno una modalità ADR (Totale o Esenzioni).';
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
