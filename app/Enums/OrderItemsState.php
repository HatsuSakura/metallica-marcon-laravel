<?php

namespace App\Enums;

enum OrderItemsState: string
{
    case STATE_CREATED = 'creato';
    case STATE_LOADED = 'caricato';
    case STATE_DOWNLOADED = 'scaricato';
    case STATE_PROGRESS = 'lavorazione';
    case STATE_CLASSIFIED = 'classificato';
    case STATE_CLOSED = 'chiuso';
    // da STATE_DOWNLOADED può anche passare a trasbordo per poi creare un altro viaggio (LOADED)
    case STATE_TRANSHIPMENT = 'trasbordo';

    public function canTransitionTo(self $nextState): bool
    {
        $transitions = [
            self::STATE_CREATED->value => [self::STATE_LOADED],
            self::STATE_LOADED->value => [self::STATE_DOWNLOADED],
            self::STATE_DOWNLOADED->value => [self::STATE_PROGRESS],
            self::STATE_PROGRESS->value => [self::STATE_CLASSIFIED],
            self::STATE_CLASSIFIED->value => [self::STATE_CLOSED],
            // CICLO di TRASBORDO
            self::STATE_DOWNLOADED->value => [self::STATE_TRANSHIPMENT],
            self::STATE_TRANSHIPMENT->value => [self::STATE_LOADED],
        ];

        return isset($transitions[$this->value]) && in_array($nextState, $transitions[$this->value], true);
    }
}
