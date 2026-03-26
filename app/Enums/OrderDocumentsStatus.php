<?php

namespace App\Enums;

enum OrderDocumentsStatus: string
{
    case NOT_GENERATED = 'not_generated';
    case GENERATING = 'generating';
    case GENERATED = 'generated';
    case FAILED = 'failed';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NOT_GENERATED => [self::GENERATING],
            self::GENERATING => [self::GENERATED, self::FAILED, self::NOT_GENERATED],
            self::GENERATED => [self::NOT_GENERATED],
            self::FAILED => [self::GENERATING, self::NOT_GENERATED],
        };
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
}
