<?php

namespace App\Enums;

enum UserRole: string
{
    case MANAGER = 'manager';
    case LOGISTIC = 'logistic';
    case DRIVER = 'driver';
    case WAREHOUSE_CHIEF = 'warehouse_chief';
    case WAREHOUSE_MANAGER = 'warehouse_manager';
    case WAREHOUSE_WORKER = 'warehouse_worker';
    case CUSTOMER = 'customer';
    case DEVELOPER = 'developer';

    public static function values(): array
    {
        return array_map(fn($role) => $role->value, self::cases());
    }

    public static function toArray(): array
    {
        return array_map(fn($role) => ['key' => $role->name, 'value' => $role->value], self::cases());
    }

}
