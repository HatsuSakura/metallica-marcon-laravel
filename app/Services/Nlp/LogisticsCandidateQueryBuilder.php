<?php

namespace App\Services\Nlp;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LogisticsCandidateQueryBuilder
{
    // NLP schema statuses → DB enum values
    private const STATUS_MAP = [
        'requested' => ['creato', 'pronto'],
        'planned'   => ['pianificato'],
        'executed'  => ['eseguito', 'scaricato'],
        'closed'    => ['chiuso'],
    ];

    // risk_min threshold above which a site is considered "high risk / hazardous"
    // TODO: replace with order-level ADR flag when materials are tracked on orders
    private const HAZARDOUS_RISK_THRESHOLD = 0.75;

    /**
     * @param  array<string, mixed>  $query  validated LogisticsQuery
     * @return array{sites: Collection, orders: Collection, meta: array<string, mixed>}
     */
    public function build(array $query): array
    {
        $scenario = $query['scenario'];
        $origin   = $this->resolveGeoOrigin($query);

        $sites  = collect();
        $orders = collect();

        if (in_array($scenario, ['planning_sites', 'hybrid'])) {
            $sites = $this->buildSiteCandidates($query, $origin);
        }

        if (in_array($scenario, ['order_requests', 'hybrid'])) {
            $orders = $this->buildOrderCandidates($query, $origin);
        }

        return [
            'sites'  => $sites,
            'orders' => $orders,
            'meta'   => [
                'scenario'      => $scenario,
                'origin'        => $origin,
                'sites_count'   => $sites->count(),
                'orders_count'  => $orders->count(),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // GEO ORIGIN RESOLUTION
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $query
     * @return array{lat: float|null, lng: float|null}
     */
    private function resolveGeoOrigin(array $query): array
    {
        $geoOrigin = data_get($query, 'geo.origin');

        if ($geoOrigin === 'coordinates') {
            return [
                'lat' => data_get($query, 'reference.coordinates.lat'),
                'lng' => data_get($query, 'reference.coordinates.lng'),
            ];
        }

        if ($geoOrigin === 'customer') {
            $customerId = data_get($query, 'reference.customer.id');
            if ($customerId) {
                $site = Site::where('customer_id', $customerId)
                    ->where('is_main', true)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->first();

                if ($site) {
                    return ['lat' => $site->latitude, 'lng' => $site->longitude];
                }
            }
        }

        if ($geoOrigin === 'site') {
            $siteId = data_get($query, 'reference.site.id');
            if ($siteId) {
                $site = Site::find($siteId);
                if ($site) {
                    return ['lat' => $site->latitude, 'lng' => $site->longitude];
                }
            }
        }

        return ['lat' => null, 'lng' => null];
    }

    // -------------------------------------------------------------------------
    // HAVERSINE EXPRESSION
    // -------------------------------------------------------------------------

    private function haversineExpression(float $lat, float $lng): \Illuminate\Contracts\Database\Query\Expression
    {
        // Returns distance in km between (lat, lng) and site coordinates
        return DB::raw("
            (6371 * ACOS(
                COS(RADIANS({$lat}))
                * COS(RADIANS(sites.latitude))
                * COS(RADIANS(sites.longitude) - RADIANS({$lng}))
                + SIN(RADIANS({$lat}))
                * SIN(RADIANS(sites.latitude))
            ))
        ");
    }

    // -------------------------------------------------------------------------
    // PLANNING_SITES
    // -------------------------------------------------------------------------

    private function buildSiteCandidates(array $query, array $origin): Collection
    {
        $q = Site::query()
            ->with('customer:id,company_name')
            ->whereNull('sites.deleted_at')
            ->whereNotNull('sites.latitude')
            ->whereNotNull('sites.longitude');

        // Risk filters
        $riskMin = data_get($query, 'site_filters.risk_min');
        $riskMax = data_get($query, 'site_filters.risk_max');
        if ($riskMin !== null) {
            $q->where('calculated_risk_factor', '>=', $riskMin);
        }
        if ($riskMax !== null) {
            $q->where('calculated_risk_factor', '<=', $riskMax);
        }

        // Days to next pickup filters
        $daysMin = data_get($query, 'site_filters.days_to_next_pickup_min');
        $daysMax = data_get($query, 'site_filters.days_to_next_pickup_max');
        if ($daysMin !== null) {
            $q->where('days_until_next_withdraw', '>=', $daysMin);
        }
        if ($daysMax !== null) {
            $q->where('days_until_next_withdraw', '<=', $daysMax);
        }

        // Customer whitelist / blacklist
        $customerIds = data_get($query, 'site_filters.customer_ids');
        if (!empty($customerIds)) {
            $q->whereIn('customer_id', $customerIds);
        }
        $excludeIds = data_get($query, 'site_filters.exclude_customer_ids');
        if (!empty($excludeIds)) {
            $q->whereNotIn('customer_id', $excludeIds);
        }

        // Sites with no active orders
        if (data_get($query, 'site_filters.has_no_active_orders') === true) {
            $q->whereNotExists(function ($sub) {
                $sub->selectRaw('1')->from('orders')
                    ->whereColumn('orders.site_id', 'sites.id')
                    ->whereIn('orders.status', ['creato', 'pronto', 'pianificato'])
                    ->whereNull('orders.deleted_at');
            });
        }

        // Last withdrawal filter — sites not visited in N+ days
        $lastWithdrawDaysMin = data_get($query, 'site_filters.last_withdraw_days_min');
        $lastWithdrawSub = DB::table('withdraws')
            ->selectRaw('site_id, MAX(withdrawn_at) AS last_withdrawn_at')
            ->whereNull('deleted_at')
            ->groupBy('site_id');

        $q->leftJoinSub($lastWithdrawSub, 'lw', 'lw.site_id', '=', 'sites.id');

        if ($lastWithdrawDaysMin !== null) {
            $q->where(function ($sub) use ($lastWithdrawDaysMin) {
                $sub->whereNull('lw.last_withdrawn_at')
                    ->orWhereRaw('lw.last_withdrawn_at < DATE_SUB(NOW(), INTERVAL ? DAY)', [$lastWithdrawDaysMin]);
            });
        }

        // Order-based filters — applied as EXISTS subqueries so the site query stays duplicate-free
        $this->applyOrderFiltersToSiteQuery($q, $query);

        // Geo filter + distance column
        $radiusKm = data_get($query, 'geo.radius_km');
        if ($origin['lat'] !== null && $origin['lng'] !== null) {
            $distance = $this->haversineExpression($origin['lat'], $origin['lng']);
            $q->addSelect(DB::raw('sites.*'))
              ->addSelect(DB::raw($distance->getValue(DB::connection()->getQueryGrammar()) . ' AS distance_km'))
              ->addSelect(DB::raw('lw.last_withdrawn_at'));

            if ($radiusKm !== null) {
                $q->havingRaw('distance_km <= ?', [$radiusKm]);
            }
        } else {
            $q->addSelect('sites.*')
              ->addSelect(DB::raw('NULL AS distance_km'))
              ->addSelect(DB::raw('lw.last_withdrawn_at'));
        }

        // Sort
        $sortMode = data_get($query, 'sort.mode', 'distance');
        $q = $this->applySiteSort($q, $sortMode, data_get($query, 'sort.weights'), $origin);

        $limit = (int) data_get($query, 'limit.sites', 200);

        return $q->limit($limit)->get();
    }

    private function applySiteSort($q, string $mode, ?array $weights, array $origin): mixed
    {
        return match ($mode) {
            'risk'    => $q->orderByDesc('calculated_risk_factor'),
            'urgency' => $q->orderBy('days_until_next_withdraw'),
            'mixed'   => $this->applyMixedSiteSort($q, $weights ?? []),
            default   => $origin['lat'] !== null
                ? $q->orderByRaw('distance_km ASC')
                : $q->orderByDesc('calculated_risk_factor'),
        };
    }

    private function applyOrderFiltersToSiteQuery($q, array $query): void
    {
        $statuses = data_get($query, 'order_filters.statuses');
        if (!empty($statuses)) {
            $dbStatuses = collect($statuses)
                ->flatMap(fn ($s) => self::STATUS_MAP[$s] ?? [])
                ->unique()->values()->all();
            if (!empty($dbStatuses)) {
                $q->whereExists(function ($sub) use ($dbStatuses) {
                    $sub->selectRaw('1')->from('orders')
                        ->whereColumn('orders.site_id', 'sites.id')
                        ->whereIn('orders.status', $dbStatuses)
                        ->whereNull('orders.deleted_at');
                });
            }
        }

        $hazardous = data_get($query, 'order_filters.hazardous');
        if ($hazardous === true) {
            $q->where('calculated_risk_factor', '>=', self::HAZARDOUS_RISK_THRESHOLD);
        } elseif ($hazardous === false) {
            $q->where('calculated_risk_factor', '<', self::HAZARDOUS_RISK_THRESHOLD);
        }

        $hasBulk = data_get($query, 'order_filters.has_bulk');
        if ($hasBulk === true) {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('orders')
                    ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                    ->whereColumn('orders.site_id', 'sites.id')
                    ->where('order_items.is_bulk', true)
                    ->whereNull('orders.deleted_at')
                    ->whereNull('order_items.deleted_at');
            });
        }

        $cerCodes = data_get($query, 'order_filters.cer_codes');
        if (!empty($cerCodes)) {
            $q->whereExists(function ($sub) use ($cerCodes) {
                $sub->selectRaw('1')->from('orders')
                    ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                    ->join('cer_codes', 'order_items.cer_code_id', '=', 'cer_codes.id')
                    ->whereColumn('orders.site_id', 'sites.id')
                    ->whereIn('cer_codes.code', $cerCodes)
                    ->whereNull('orders.deleted_at')
                    ->whereNull('order_items.deleted_at');
            });
        }

        $cerKeyword = data_get($query, 'order_filters.cer_keyword');
        if ($cerKeyword) {
            $q->whereExists(function ($sub) use ($cerKeyword) {
                $sub->selectRaw('1')->from('orders')
                    ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                    ->join('cer_codes', 'order_items.cer_code_id', '=', 'cer_codes.id')
                    ->whereColumn('orders.site_id', 'sites.id')
                    ->where('cer_codes.description', 'like', "%{$cerKeyword}%")
                    ->whereNull('orders.deleted_at')
                    ->whereNull('order_items.deleted_at');
            });
        }

        $cerDangerous = data_get($query, 'order_filters.cer_dangerous');
        if ($cerDangerous === true) {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('orders')
                    ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                    ->join('cer_codes', 'order_items.cer_code_id', '=', 'cer_codes.id')
                    ->whereColumn('orders.site_id', 'sites.id')
                    ->where('cer_codes.is_dangerous', true)
                    ->whereNull('orders.deleted_at')
                    ->whereNull('order_items.deleted_at');
            });
        }
    }

    private function applyMixedSiteSort($q, array $weights): mixed
    {
        $wDist    = (float) ($weights['distance'] ?? 0.33);
        $wRisk    = (float) ($weights['risk']     ?? 0.33);
        $wUrgency = (float) ($weights['urgency']  ?? 0.34);

        // Normalize distance to [0,1] using a 200 km reference max
        // Higher score = better candidate
        $scoreExpr = "
            (COALESCE(1 - (distance_km / 200), 0) * {$wDist})
            + (COALESCE(calculated_risk_factor, 0) * {$wRisk})
            + (COALESCE(1 - (days_until_next_withdraw / 365), 0) * {$wUrgency})
        ";

        return $q->orderByRaw("{$scoreExpr} DESC");
    }

    // -------------------------------------------------------------------------
    // ORDER_REQUESTS
    // -------------------------------------------------------------------------

    private function buildOrderCandidates(array $query, array $origin): Collection
    {
        $q = Order::query()
            ->with(['site:id,name,address,latitude,longitude,customer_id', 'customer:id,company_name'])
            ->join('sites', 'orders.site_id', '=', 'sites.id')
            ->whereNull('orders.deleted_at')
            ->whereNull('sites.deleted_at')
            ->whereNotNull('sites.latitude')
            ->whereNotNull('sites.longitude');

        // Status filter — map NLP statuses to DB enum values
        $statuses = data_get($query, 'order_filters.statuses');
        if (!empty($statuses)) {
            $dbStatuses = collect($statuses)
                ->flatMap(fn ($s) => self::STATUS_MAP[$s] ?? [])
                ->unique()
                ->values()
                ->all();

            if (!empty($dbStatuses)) {
                $q->whereIn('orders.status', $dbStatuses);
            }
        }

        // Hazardous — mapped to site risk factor (high risk = hazardous)
        // TODO: replace with order-level ADR material flag when available
        $hazardous = data_get($query, 'order_filters.hazardous');
        if ($hazardous === true) {
            $q->where('sites.calculated_risk_factor', '>=', self::HAZARDOUS_RISK_THRESHOLD);
        } elseif ($hazardous === false) {
            $q->where(function ($sub) {
                $sub->where('sites.calculated_risk_factor', '<', self::HAZARDOUS_RISK_THRESHOLD)
                    ->orWhereNull('sites.calculated_risk_factor');
            });
        }

        // Time filters on requested_at
        $requestedFrom = data_get($query, 'order_filters.requested_from');
        $requestedTo   = data_get($query, 'order_filters.requested_to');
        if ($requestedFrom) {
            $q->where('orders.requested_at', '>=', $requestedFrom);
        }
        if ($requestedTo) {
            $q->where('orders.requested_at', '<=', $requestedTo . ' 23:59:59');
        }

        // Customer whitelist / blacklist (from site_filters, shared context)
        $customerIds = data_get($query, 'site_filters.customer_ids');
        if (!empty($customerIds)) {
            $q->whereIn('orders.customer_id', $customerIds);
        }
        $excludeIds = data_get($query, 'site_filters.exclude_customer_ids');
        if (!empty($excludeIds)) {
            $q->whereNotIn('orders.customer_id', $excludeIds);
        }

        // Last withdrawal filter — orders from sites not visited in N+ days
        $lastWithdrawDaysMin = data_get($query, 'site_filters.last_withdraw_days_min');
        $lastWithdrawSub = DB::table('withdraws')
            ->selectRaw('site_id, MAX(withdrawn_at) AS last_withdrawn_at')
            ->whereNull('deleted_at')
            ->groupBy('site_id');

        $q->leftJoinSub($lastWithdrawSub, 'lw', 'lw.site_id', '=', 'orders.site_id');

        if ($lastWithdrawDaysMin !== null) {
            $q->where(function ($sub) use ($lastWithdrawDaysMin) {
                $sub->whereNull('lw.last_withdrawn_at')
                    ->orWhereRaw('lw.last_withdrawn_at < DATE_SUB(NOW(), INTERVAL ? DAY)', [$lastWithdrawDaysMin]);
            });
        }

        // Bulk material filter
        $hasBulk = data_get($query, 'order_filters.has_bulk');
        if ($hasBulk === true) {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('order_items.is_bulk', true)
                    ->whereNull('order_items.deleted_at');
            });
        } elseif ($hasBulk === false) {
            $q->whereNotExists(function ($sub) {
                $sub->selectRaw('1')->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('order_items.is_bulk', true)
                    ->whereNull('order_items.deleted_at');
            });
        }

        // CER filters — explicit codes, description keyword, dangerous flag
        // Multiple conditions are AND-ed: order must satisfy ALL active CER filters.
        $cerCodes = data_get($query, 'order_filters.cer_codes');
        if (!empty($cerCodes)) {
            $q->whereExists(function ($sub) use ($cerCodes) {
                $sub->selectRaw('1')->from('order_items')
                    ->join('cer_codes', 'order_items.cer_code_id', '=', 'cer_codes.id')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->whereIn('cer_codes.code', $cerCodes)
                    ->whereNull('order_items.deleted_at');
            });
        }

        $cerKeyword = data_get($query, 'order_filters.cer_keyword');
        if ($cerKeyword) {
            $q->whereExists(function ($sub) use ($cerKeyword) {
                $sub->selectRaw('1')->from('order_items')
                    ->join('cer_codes', 'order_items.cer_code_id', '=', 'cer_codes.id')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('cer_codes.description', 'like', "%{$cerKeyword}%")
                    ->whereNull('order_items.deleted_at');
            });
        }

        $cerDangerous = data_get($query, 'order_filters.cer_dangerous');
        if ($cerDangerous === true) {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('order_items')
                    ->join('cer_codes', 'order_items.cer_code_id', '=', 'cer_codes.id')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('cer_codes.is_dangerous', true)
                    ->whereNull('order_items.deleted_at');
            });
        }

        // Geo filter + distance column
        $radiusKm = data_get($query, 'geo.radius_km');
        if ($origin['lat'] !== null && $origin['lng'] !== null) {
            $distance = $this->haversineExpression($origin['lat'], $origin['lng']);
            $q->addSelect(DB::raw('orders.*'))
              ->addSelect(DB::raw($distance->getValue(DB::connection()->getQueryGrammar()) . ' AS distance_km'))
              ->addSelect(DB::raw('lw.last_withdrawn_at'));

            if ($radiusKm !== null) {
                $q->havingRaw('distance_km <= ?', [$radiusKm]);
            }
        } else {
            $q->addSelect('orders.*')
              ->addSelect(DB::raw('NULL AS distance_km'))
              ->addSelect(DB::raw('lw.last_withdrawn_at'));
        }

        // Sort
        $sortMode = data_get($query, 'sort.mode', 'distance');
        $q = $this->applyOrderSort($q, $sortMode, $origin);

        $limit = (int) data_get($query, 'limit.orders', 200);

        return $q->limit($limit)->get();
    }

    private function applyOrderSort($q, string $mode, array $origin): mixed
    {
        return match ($mode) {
            'urgency' => $q->orderByRaw('orders.expected_withdraw_at IS NULL ASC')->orderBy('orders.expected_withdraw_at'),
            'risk'    => $q->orderByDesc('sites.calculated_risk_factor'),
            default   => $origin['lat'] !== null
                ? $q->orderByRaw('distance_km ASC')
                : $q->orderBy('orders.requested_at'),
        };
    }
}
