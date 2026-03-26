<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case STATUS_CREATED = 'creato';
    case STATUS_LOADED = 'caricato';
    case STATUS_DOWNLOADED = 'scaricato';
    case STATUS_PROGRESS = 'lavorazione';
    case STATUS_CLASSIFIED = 'classificato';
    case STATUS_CLOSED = 'chiuso';
    // da STATUS_DOWNLOADED può anche passare a trasbordo per poi creare un altro viaggio (LOADED)
    case STATUS_TRANSHIPMENT = 'trasbordo';

    public function canTransitionTo(self $nextState): bool
    {
        return in_array($nextState, $this->allowedTransitions(), true);
    }

    /**
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::STATUS_CREATED => [self::STATUS_LOADED],
            self::STATUS_LOADED => [self::STATUS_DOWNLOADED],
            self::STATUS_DOWNLOADED => [self::STATUS_PROGRESS, self::STATUS_TRANSHIPMENT],
            self::STATUS_PROGRESS => [self::STATUS_CLASSIFIED],
            self::STATUS_CLASSIFIED => [self::STATUS_CLOSED],
            self::STATUS_TRANSHIPMENT => [self::STATUS_LOADED],
            self::STATUS_CLOSED => [],
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
