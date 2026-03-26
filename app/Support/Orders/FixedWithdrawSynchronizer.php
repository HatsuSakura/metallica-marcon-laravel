<?php

namespace App\Support\Orders;

class FixedWithdrawSynchronizer
{
    public static function synchronize(array $validatedData): array
    {
        $fixedWithdrawAt = $validatedData['fixed_withdraw_at'] ?? null;

        if ($fixedWithdrawAt) {
            $validatedData['expected_withdraw_at'] = $fixedWithdrawAt;
        }

        return $validatedData;
    }
}
