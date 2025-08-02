<?php

namespace App\Enums;

enum SiteTipologia: string
{
    case FULLY_OPERATIVE = 'fully_operative';
    case ONLY_LEGAL = 'only_legal';
    case ONLY_STOCK = 'only_stock';
}
