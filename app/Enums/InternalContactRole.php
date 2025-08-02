<?php

namespace App\Enums;

enum InternalContactRole: string
{
    case RESPONSABILE_SMALTIMENTI = 'smaltimenti';
    case SEGRETERIA = 'segreteria';
    case MAGAZZINO = 'magazzino';
    case INTERMEDIARIO = 'inrtermediario';
}
