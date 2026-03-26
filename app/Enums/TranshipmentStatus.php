<?php

namespace App\Enums;

enum TranshipmentStatus: string
{
    case PROPOSED = 'proposed';
    case APPROVED = 'approved';
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PROPOSED => 'Proposto',
            self::APPROVED => 'Approvato',
            self::PLANNED => 'Pianificato',
            self::IN_PROGRESS => 'In trasferimento',
            self::COMPLETED => 'Completato',
            self::CANCELLED => 'Annullato',
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
            self::PROPOSED => [self::APPROVED, self::CANCELLED],
            self::APPROVED => [self::PLANNED, self::CANCELLED],
            self::PLANNED => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }
}
