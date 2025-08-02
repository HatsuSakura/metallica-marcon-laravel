<?php

namespace App\Enums;

enum CustomerJobType: string
{
    case GENERICO = 'generico';
    case INDUSTRIALE = 'industriale';
    case COMMERCIALE = 'commerciale';
    case AGRICOLA = 'agricola';
}
