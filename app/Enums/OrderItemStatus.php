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
        $transitions = [
            self::STATUS_CREATED->value => [self::STATUS_LOADED],
            self::STATUS_LOADED->value => [self::STATUS_DOWNLOADED],
            self::STATUS_DOWNLOADED->value => [self::STATUS_PROGRESS],
            self::STATUS_PROGRESS->value => [self::STATUS_CLASSIFIED],
            self::STATUS_CLASSIFIED->value => [self::STATUS_CLOSED],
            // CICLO di TRASBORDO
            self::STATUS_DOWNLOADED->value => [self::STATUS_TRANSHIPMENT],
            self::STATUS_TRANSHIPMENT->value => [self::STATUS_LOADED],
        ];

        return isset($transitions[$this->value]) && in_array($nextState, $transitions[$this->value], true);
    }
}

