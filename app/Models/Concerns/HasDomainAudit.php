<?php

namespace App\Models\Concerns;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;

trait HasDomainAudit
{
    use Auditable;

    public function transformAudit(array $data): array
    {
        foreach (['old_values', 'new_values'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = $this->normalizeAuditPayload($data[$key]);
                ksort($data[$key]);
            }
        }

        return $data;
    }

    public function generateTags(): array
    {
        return [$this->getTable(), class_basename(static::class)];
    }

    protected function normalizeAuditPayload(array $values): array
    {
        foreach ($values as $attribute => $value) {
            $values[$attribute] = $this->normalizeAuditValue($attribute, $value);
        }

        return $values;
    }

    protected function normalizeAuditValue(string $attribute, mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }

        if ($this->isAuditBooleanAttribute($attribute, $value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
        }

        if ($this->isAuditDateAttribute($attribute) && filled($value)) {
            try {
                return Carbon::parse($value)->toDateTimeString();
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    protected function isAuditBooleanAttribute(string $attribute, mixed $value): bool
    {
        if ($this->hasCast($attribute, ['bool', 'boolean'])) {
            return true;
        }

        if (in_array($attribute, ['adr', 'is_urgent'], true)) {
            return true;
        }

        if (str_starts_with($attribute, 'is_') || str_starts_with($attribute, 'has_')) {
            return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
        }

        return false;
    }

    protected function isAuditDateAttribute(string $attribute): bool
    {
        return $this->hasCast($attribute, [
            'date',
            'datetime',
            'immutable_date',
            'immutable_datetime',
            'custom_datetime',
        ]);
    }
}
