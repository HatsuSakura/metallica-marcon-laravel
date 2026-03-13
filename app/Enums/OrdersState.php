<?php

namespace App\Enums;

enum OrdersState: string
{
    case STATUS_CREATED = 'creato';
    case STATUS_PLANNED = 'pianificato';
    case STATUS_EXECUTED = 'eseguito';
    case STATUS_DOWNLOADED = 'scaricato'; 
    // conta come scaricato anche se in "messa a terra" [GROUNDING] e "da trasbordare" [TRANSSHIPMENT]
    // in quanto il trasporto è stato completato e un ordine potrebbe essere suddiviso fra motrice e rimorchio
    case STATUS_CLOSED = 'chiuso';
    // passa a chiuso solo quando tutti gli order_items riferiti a questo ordine sono stati pesati e classificati

    public function canTransitionTo(self $nextState): bool
    {
        $transitions = [
            self::STATUS_CREATED->value => [self::STATUS_PLANNED],
            self::STATUS_PLANNED->value => [self::STATUS_EXECUTED],
            self::STATUS_EXECUTED->value => [self::STATUS_DOWNLOADED],
            self::STATUS_DOWNLOADED->value => [self::STATUS_CLOSED],
        ];

        return isset($transitions[$this->value]) && in_array($nextState, $transitions[$this->value], true);
    }
}
