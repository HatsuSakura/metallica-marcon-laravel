<?php

namespace App\Enums;

enum OrdersTruckLocation: string
{
    case TRUCK_MOTRICE = 'vehicle';
    case TRUCK_RIMORCHIO = 'trailer';
    case TRUCK_RIEMPIMENTO = 'fullfill';
}
