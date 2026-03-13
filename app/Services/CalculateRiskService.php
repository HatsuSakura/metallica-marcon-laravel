<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Site;
use App\Models\Withdraw;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class CalculateRiskService
{
    /**
     * @param array<string, mixed> $data
     */
    public function recalculateSiteRisk(array $data): Site
    {
        $today = Carbon::today();
        $weightPeriod3 = 0.15;
        $weightPeriod2 = 0.35;
        $weightStored = 0.50;
        $algorithmCase = 0;
        $daysBetweenThirdAndSecondWithdraw = 0;
        $daysBetweenSecondAndFirstWithdraw = 0;
        $daysSinceLatestWithdraw = 0;
        $residueFactor = 0;
        $withdrawCount = 0;

        $siteId = $this->requirePositiveInt($data, 'siteId');
        $site = Site::find($siteId);

        if (!$site) {
            throw new RuntimeException("Site not found: {$siteId}");
        }

        $daysUntilNextWithdraw = (float) ($site->days_until_next_withdraw ?? 0);

        $withdraws = Withdraw::query()
            ->where('site_id', $siteId)
            ->whereNotNull('withdrawn_at')
            ->orderByDesc('withdrawn_at')
            ->limit(3)
            ->get();

        if (!$withdraws->isEmpty()) {
            $withdrawCount = $withdraws->count();
        }

        $algorithmCase = $withdrawCount;

        if ($withdrawCount > 0) {
            $residueFactor = ((float) ($withdraws[0]->residue_percentage ?? 0)) / 100;
            $daysSinceLatestWithdraw = Carbon::parse($withdraws[0]->withdrawn_at)->diffInDays($today);
        }
        if ($withdrawCount > 1) {
            $daysBetweenSecondAndFirstWithdraw = Carbon::parse($withdraws[1]->withdrawn_at)
                ->diffInDays(Carbon::parse($withdraws[0]->withdrawn_at));
        }
        if ($withdrawCount > 2) {
            $daysBetweenThirdAndSecondWithdraw = Carbon::parse($withdraws[2]->withdrawn_at)
                ->diffInDays(Carbon::parse($withdraws[1]->withdrawn_at));
        }

        // Residue percentage from the latest withdraw contributes additively to the computed risk.
        switch ($algorithmCase) {
            case 3:
                if ($daysUntilNextWithdraw == 0) {
                    $daysUntilNextWithdraw =
                        $daysBetweenThirdAndSecondWithdraw * ($weightPeriod3 + 0.5 * $weightStored)
                        + $daysBetweenSecondAndFirstWithdraw * ($weightPeriod2 + 0.5 * $weightStored);
                } else {
                    $daysUntilNextWithdraw =
                        $daysBetweenThirdAndSecondWithdraw * $weightPeriod3
                        + $daysBetweenSecondAndFirstWithdraw * $weightPeriod2
                        + $daysUntilNextWithdraw * $weightStored;
                }
                break;
            case 2:
                if ($daysUntilNextWithdraw == 0) {
                    $daysUntilNextWithdraw = $daysBetweenSecondAndFirstWithdraw;
                } else {
                    $daysUntilNextWithdraw =
                        $daysBetweenSecondAndFirstWithdraw * ($weightPeriod2 + 0.5 * $weightPeriod3)
                        + $daysUntilNextWithdraw * ($weightStored + 0.5 * $weightPeriod3);
                }
                break;
            case 1:
                if ($daysUntilNextWithdraw == 0) {
                    $daysUntilNextWithdraw = $daysSinceLatestWithdraw * 2;
                } else {
                    $daysUntilNextWithdraw = $daysUntilNextWithdraw;
                }
                break;
            case 0:
                $daysSinceLatestWithdraw = 1;
                $daysUntilNextWithdraw = 2;
                break;
            default:
                $daysUntilNextWithdraw =
                    $daysBetweenThirdAndSecondWithdraw * ($weightPeriod3 + 0.5 * $weightStored)
                    + $daysBetweenSecondAndFirstWithdraw * ($weightPeriod2 + 0.5 * $weightStored);
        }

        if ($daysUntilNextWithdraw <= 0) {
            $daysUntilNextWithdraw = 1;
        }

        $riskFactor = ($daysSinceLatestWithdraw / $daysUntilNextWithdraw) + $residueFactor;
        if ($riskFactor > 1.25) {
            $riskFactor = 1.25;
        }

        $site->calculated_risk_factor = $riskFactor;
        $site->days_until_next_withdraw = (int) round($daysUntilNextWithdraw);
        if ($site->save()) {
            return $site->fresh();
        }

        throw new RuntimeException("Unable to update risk factor for site: {$siteId}");
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, Site>
     */
    public function recalculateCustomerRisk(array $data): array
    {
        $customerId = $this->requirePositiveInt($data, 'customerId');
        $sites = Site::query()
            ->where('customer_id', $customerId)
            ->get();
        $updatedSites = [];

        foreach ($sites as $site) {
            $payload = ['siteId' => $site->id];
            $updatedSite = $this->recalculateSiteRisk($payload);
            $updatedSites[] = $updatedSite;
        }

        return $updatedSites;
    }

    /**
     * @return array<int, array<int, Site>>
     */
    public function recalculateGlobalRisk(): array
    {
        $customers = Customer::query()->get();
        $updatedByCustomer = [];

        foreach ($customers as $customer) {
            $payload = ['customerId' => $customer->id];
            $updatedSites = $this->recalculateCustomerRisk($payload);
            $updatedByCustomer[] = $updatedSites;
        }

        return $updatedByCustomer;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requirePositiveInt(array $data, string $key): int
    {
        if (!array_key_exists($key, $data)) {
            throw new InvalidArgumentException("Missing required key: {$key}");
        }

        $value = filter_var($data[$key], FILTER_VALIDATE_INT);
        if ($value === false || $value <= 0) {
            throw new InvalidArgumentException("Invalid value for {$key}: " . (string) $data[$key]);
        }

        return $value;
    }
}
