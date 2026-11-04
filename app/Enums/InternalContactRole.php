<?php

namespace App\Enums;

enum InternalContactRole: string
{
    case RESPONSABILE_SMALTIMENTI = 'smaltimenti';
    case SEGRETERIA = 'segreteria';
    case MAGAZZINO = 'magazzino';
    case INTERMEDIARIO = 'inrtermediario';

    public function label(): string
    {
        return match ($this) {
            self::RESPONSABILE_SMALTIMENTI => 'Responsabile smaltimenti',
            self::SEGRETERIA => 'Segreteria',
            self::MAGAZZINO => 'Magazzino',
            self::INTERMEDIARIO => 'Intermediario',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()], self::cases());
    }
}
