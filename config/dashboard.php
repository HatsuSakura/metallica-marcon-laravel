<?php

use App\Enums\UserRole;

/*
 * in riferimento a app/Http/Middleware/RedirectToRoleHome.php
 */

return [
    // mapping primario per ruolo → route name
    'homes' => [
        UserRole::MANAGER->value =>'manager.home',
        UserRole::LOGISTIC->value =>'logistic.home',
        UserRole::DRIVER->value =>'driver.home',
        UserRole::WAREHOUSE_CHIEF->value =>'warehouse.home',
        UserRole::WAREHOUSE_MANAGER->value =>'warehouse.home',
        UserRole::WAREHOUSE_WORKER->value =>'warehouse.home',
        UserRole::CUSTOMER->value =>'customer.home',
        UserRole::PROGRAMMER->value =>'programmer.home',
    ],
    'fallback' => 'login', // se ruolo non mappato
];



