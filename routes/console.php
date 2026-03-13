<?php

use App\Services\CalculateRiskService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('risk:global', function (CalculateRiskService $riskService) {
    $result = $riskService->recalculateGlobalRisk();

    $customersProcessed = count($result);
    $sitesProcessed = array_sum(array_map('count', $result));

    $this->info("Risk recalculation completed. Customers: {$customersProcessed}, Sites: {$sitesProcessed}.");
})->purpose('Recalculate risk factors for all customers/sites');

Artisan::command('risk:customer {customerId}', function (CalculateRiskService $riskService, int $customerId) {
    $result = $riskService->recalculateCustomerRisk(['customerId' => $customerId]);
    $sitesProcessed = count($result);

    $this->info("Risk recalculation completed for customer {$customerId}. Sites: {$sitesProcessed}.");
})->purpose('Recalculate risk factors for all sites of a single customer');

Artisan::command('risk:site {siteId}', function (CalculateRiskService $riskService, int $siteId) {
    $site = $riskService->recalculateSiteRisk(['siteId' => $siteId]);

    $this->info(
        sprintf(
            'Risk recalculation completed for site %d. Risk factor: %.4f, Days until next withdraw: %d.',
            $site->id,
            (float) ($site->calculated_risk_factor ?? 0),
            (int) ($site->days_until_next_withdraw ?? 0)
        )
    );
})->purpose('Recalculate risk factor for a single site');

Schedule::command('auth:clear-resets')->everyFourHours();
Schedule::command('risk:global')->dailyAt('00:00');
