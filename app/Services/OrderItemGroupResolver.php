<?php

namespace App\Services;

use App\Models\CerCode;
use App\Models\Order;
use App\Models\OrderItemGroup;

class OrderItemGroupResolver
{
    public function resolveForOrder(Order $order, array $items): array
    {
        $resolved = [];

        foreach ($items as $item) {
            $cerCodeId = (int) ($item['cer_code_id'] ?? 0);
            if (!$cerCodeId) {
                $resolved[] = $item;
                continue;
            }

            $groupId = isset($item['order_item_group_id']) && $item['order_item_group_id'] !== ''
                ? (int) $item['order_item_group_id']
                : null;
            $groupLabel = isset($item['order_item_group_label'])
                ? trim((string) $item['order_item_group_label'])
                : null;

            $group = null;

            if ($groupId) {
                $group = OrderItemGroup::query()
                    ->where('id', $groupId)
                    ->where('order_id', $order->id)
                    ->where('cer_code_id', $cerCodeId)
                    ->first();
            }

            if (!$group) {
                if ($groupLabel === '') {
                    $groupLabel = null;
                }

                if (!$groupLabel) {
                    $groupLabel = $this->nextDefaultLabel($order->id, $cerCodeId);
                }

                $group = OrderItemGroup::firstOrCreate([
                    'order_id' => $order->id,
                    'cer_code_id' => $cerCodeId,
                    'label' => $groupLabel,
                ]);
            }

            $item['order_item_group_id'] = $group->id;
            unset($item['order_item_group_label']);
            $resolved[] = $item;
        }

        return $resolved;
    }

    public function cleanupUnusedGroups(Order $order): void
    {
        OrderItemGroup::query()
            ->where('order_id', $order->id)
            ->whereDoesntHave('items')
            ->delete();
    }

    private function nextDefaultLabel(int $orderId, int $cerCodeId): string
    {
        $cerCode = CerCode::query()->where('id', $cerCodeId)->value('code') ?? (string) $cerCodeId;

        $labels = OrderItemGroup::query()
            ->where('order_id', $orderId)
            ->where('cer_code_id', $cerCodeId)
            ->pluck('label');

        $next = 1;
        foreach ($labels as $label) {
            if (preg_match('/^' . preg_quote($cerCode, '/') . '\.(\d+)$/', (string) $label, $m)) {
                $next = max($next, ((int) $m[1]) + 1);
            }
        }

        return "{$cerCode}.{$next}";
    }
}
