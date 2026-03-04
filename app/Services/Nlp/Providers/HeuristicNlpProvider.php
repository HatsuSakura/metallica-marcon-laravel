<?php

namespace App\Services\Nlp\Providers;

use App\Models\Site;

class HeuristicNlpProvider implements NlpProvider
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function parseLogistics(string $query, array $context = []): array
    {
        $normalized = $this->normalizeText($query);
        $scenario = $this->extractScenario($normalized);
        $referenceCustomer = $this->extractReferenceCustomer($normalized);
        $radiusKm = $this->extractRadiusKm($normalized);
        $riskRange = $this->extractRiskRange($normalized);
        $daysFilter = $this->extractDaysToNextPickup($normalized);
        $statuses = $this->extractStatuses($normalized);
        $hazardous = $this->extractHazardous($normalized);

        return [
            'scenario' => $scenario,
            'reference' => [
                'customer' => $referenceCustomer,
                'site' => null,
                'coordinates' => null,
            ],
            'geo' => [
                'origin' => $referenceCustomer ? 'customer' : 'coordinates',
                'radius_km' => $radiusKm,
            ],
            'time' => [
                'target_date' => $this->extractAbsoluteDate($normalized),
                'from' => null,
                'to' => null,
            ],
            'site_filters' => [
                'risk_min' => $riskRange['risk_min'],
                'risk_max' => $riskRange['risk_max'],
                'days_to_next_pickup_min' => $daysFilter['days_to_next_pickup_min'],
                'days_to_next_pickup_max' => $daysFilter['days_to_next_pickup_max'],
                'customer_ids' => null,
                'exclude_customer_ids' => null,
            ],
            'order_filters' => [
                'statuses' => $statuses,
                'hazardous' => $hazardous,
                'material_types' => null,
                'min_weight_kg' => null,
                'max_weight_kg' => null,
                'requested_from' => null,
                'requested_to' => null,
            ],
            'sort' => [
                'mode' => 'distance',
                'weights' => null,
            ],
            'limit' => [
                'sites' => 200,
                'orders' => 200,
            ],
        ];
    }

    private function normalizeText(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function extractScenario(string $text): string
    {
        $hasOrderWords = (bool) preg_match('/\b(ordini|ordine|richiest[ae]|ritir[oi]\s+richiest[oi])\b/ui', $text);
        $hasSitePlanningWords = (bool) preg_match('/\b(rischio|critico|prossim[oi]\s+ritir[oi]|giorn[io]|programmazione|pianifica)\b/ui', $text);

        if ($hasOrderWords && $hasSitePlanningWords) {
            return 'hybrid';
        }

        if ($hasOrderWords) {
            return 'order_requests';
        }

        return 'planning_sites';
    }

    /**
     * @return array{id:int, name:string}|null
     */
    private function extractReferenceCustomer(string $text): ?array
    {
        $customers = Site::query()
            ->with('customer:id,company_name')
            ->whereNotNull('customer_id')
            ->get()
            ->map(function (Site $site): ?array {
                $customerName = $site->customer?->company_name;
                if (!$site->customer || !$customerName) {
                    return null;
                }

                return [
                    'id' => (int) $site->customer_id,
                    'name' => (string) $customerName,
                ];
            })
            ->filter()
            ->unique('id')
            ->values();

        foreach ($customers as $customer) {
            if (str_contains($text, mb_strtolower($customer['name']))) {
                return $customer;
            }
        }

        return null;
    }

    private function extractRadiusKm(string $text): ?float
    {
        if (!preg_match('/(\d{1,3})\s*km/ui', $text, $matches)) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * @return array{risk_min:float|null, risk_max:float|null}
     */
    private function extractRiskRange(string $text): array
    {
        if (str_contains($text, 'critico')) {
            return ['risk_min' => 0.85, 'risk_max' => 1.0];
        }
        if (str_contains($text, 'alto')) {
            return ['risk_min' => 0.75, 'risk_max' => 0.85];
        }
        if (str_contains($text, 'medio')) {
            return ['risk_min' => 0.50, 'risk_max' => 0.75];
        }
        if (str_contains($text, 'basso')) {
            return ['risk_min' => 0.0, 'risk_max' => 0.50];
        }

        return ['risk_min' => null, 'risk_max' => null];
    }

    /**
     * @return array{days_to_next_pickup_min:int|null, days_to_next_pickup_max:int|null}
     */
    private function extractDaysToNextPickup(string $text): array
    {
        if (!preg_match('/\bentro\s+(\d{1,3})\s+giorn/ui', $text, $matches)) {
            return [
                'days_to_next_pickup_min' => null,
                'days_to_next_pickup_max' => null,
            ];
        }

        return [
            'days_to_next_pickup_min' => null,
            'days_to_next_pickup_max' => (int) $matches[1],
        ];
    }

    private function extractHazardous(string $text): ?bool
    {
        if (str_contains($text, 'non pericol')) {
            return false;
        }
        if (str_contains($text, 'pericol')) {
            return true;
        }

        return null;
    }

    /**
     * @return array<int, string>|null
     */
    private function extractStatuses(string $text): ?array
    {
        $statuses = [];
        if (str_contains($text, 'richiest')) {
            $statuses[] = 'requested';
        }
        if (str_contains($text, 'pianificat')) {
            $statuses[] = 'planned';
        }
        if (str_contains($text, 'eseguit')) {
            $statuses[] = 'executed';
        }
        if (str_contains($text, 'chius')) {
            $statuses[] = 'closed';
        }

        return empty($statuses) ? null : array_values(array_unique($statuses));
    }

    private function extractAbsoluteDate(string $text): ?string
    {
        if (preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/ui', $text, $isoMatch)) {
            return $isoMatch[1];
        }

        if (preg_match('/\b(\d{1,2})\/(\d{1,2})\/(20\d{2})\b/ui', $text, $itMatch)) {
            $day = str_pad($itMatch[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($itMatch[2], 2, '0', STR_PAD_LEFT);
            $year = $itMatch[3];

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }
}
