<?php

namespace App\Enums;

enum DispatchStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case MANAGED = 'managed';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public static function fromMixed(self|string $value): self
    {
        return $value instanceof self ? $value : self::from((string) $value);
    }

    public static function tryFromMixed(mixed $value): ?self
    {
        if ($value instanceof self) return $value;
        if (!is_string($value) && !is_int($value)) return null;
        return self::tryFrom((string) $value);
    }

    /**
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::ON_HOLD, self::MANAGED],
            self::ON_HOLD => [self::IN_PROGRESS, self::MANAGED],
            self::MANAGED => [],
        };
    }
}
