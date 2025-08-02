<?php

namespace App\Enums;

enum OrdersState: string
{
    case STATE_CREATED = 'creato';
    case STATE_PLANNED = 'pianificato';
    case STATE_EXECUTED = 'eseguito';
    case STATE_DOWNLOADED = 'scaricato'; 
    // conta come scaricato anche se in "messa a terra" [GROUNDING] e "da trasbordare" [TRANSSHIPMENT]
    // in quanto il trasporto è stato completato e un ordine potrebbe essere suddiviso fra motrice e rimorchio
    case STATE_CLOSED = 'chiuso';
    // passa a chiuso solo quando tutti gli order_items riferiti a questo ordine sono stati pesati e classificati

    public function canTransitionTo(self $nextState): bool
    {
        $transitions = [
            self::STATE_CREATED->value => [self::STATE_PLANNED],
            self::STATE_PLANNED->value => [self::STATE_EXECUTED],
            self::STATE_EXECUTED->value => [self::STATE_DOWNLOADED],
            self::STATE_DOWNLOADED->value => [self::STATE_CLOSED],
        ];

        return isset($transitions[$this->value]) && in_array($nextState, $transitions[$this->value], true);
    }
}
