<?php

namespace App\Enums;

enum JourneyStatus: string
{
    case STATUS_CREATED = 'creato';
    case STATUS_ACTIVE = 'attivo';
    case STATUS_EXECUTED = 'eseguito';
    // conta come scaricato anche se in "messa a terra" [GROUNDING] e "da trasbordare" [TRANSSHIPMENT]
    // in quanto il trasporto è stato completato e un ordine potrebbe essere suddiviso fra motrice e rimorchio
    case STATUS_CLOSED = 'chiuso';
    // passa a chiuso solo quando tutti gli order_items riferiti a questo ordine sono stati pesati e classificati

    public function canTransitionTo(self $nextState): bool
    {
        $transitions = [
            self::STATUS_CREATED->value => [self::STATUS_ACTIVE],
            self::STATUS_ACTIVE->value => [self::STATUS_EXECUTED],
            self::STATUS_EXECUTED->value => [self::STATUS_CLOSED],
        ];

        return isset($transitions[$this->value]) && in_array($nextState, $transitions[$this->value], true);
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
