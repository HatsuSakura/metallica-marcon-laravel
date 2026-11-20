<?php

namespace App\Rules;

use App\Models\CerCode;
use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/** Allow a deactivated CER only when retaining the same CER on the same existing row. */
class AvailableCerCode implements ValidationRule, DataAwareRule
{
    private array $data = [];
    private array $existing;

    public function __construct(?Order $order = null)
    {
        $this->existing = $order ? $order->items()->pluck('cer_code_id', 'id')->all() : [];
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rowId = data_get($this->data, preg_replace('/cer_code_id$/', 'id', $attribute));
        if ($rowId && isset($this->existing[$rowId]) && (int) $this->existing[$rowId] === (int) $value) {
            return;
        }
        if (!CerCode::query()->whereKey($value)->where('is_active', true)->exists()) {
            $fail('Seleziona un codice CER attivo.');
        }
    }
}
