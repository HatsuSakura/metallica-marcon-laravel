<?php

namespace App\Services\Nlp\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LogisticsQueryValidator
{
    /**
     * @param  array<string, mixed>  $parsed
     * @return array{parsed: array<string, mixed>, warnings: array<int, string>}
     *
     * @throws ValidationException
     */
    public function validate(array $parsed): array
    {
        $validator = Validator::make($parsed, [
            'scenario' => 'required|string|in:planning_sites,order_requests,hybrid',

            'reference' => 'nullable|array',
            'reference.customer' => 'nullable|array',
            'reference.customer.id' => 'nullable|integer',
            'reference.customer.name' => 'nullable|string',
            'reference.site' => 'nullable|array',
            'reference.site.id' => 'nullable|integer',
            'reference.site.name' => 'nullable|string',
            'reference.coordinates' => 'nullable|array',
            'reference.coordinates.lat' => 'required_with:reference.coordinates|numeric',
            'reference.coordinates.lng' => 'required_with:reference.coordinates|numeric',

            'geo' => 'required|array',
            'geo.origin' => 'required|string|in:customer,site,coordinates',
            'geo.radius_km' => 'nullable|numeric|min:0|max:500',

            'time' => 'nullable|array',
            'time.target_date' => 'nullable|date_format:Y-m-d',
            'time.from' => 'nullable|date_format:Y-m-d',
            'time.to' => 'nullable|date_format:Y-m-d',

            'site_filters' => 'nullable|array',
            'site_filters.risk_min' => 'nullable|numeric|min:0|max:1',
            'site_filters.risk_max' => 'nullable|numeric|min:0|max:1',
            'site_filters.days_to_next_pickup_min' => 'nullable|integer|min:0|max:3650',
            'site_filters.days_to_next_pickup_max' => 'nullable|integer|min:0|max:3650',
            'site_filters.last_withdraw_days_min' => 'nullable|integer|min:1|max:3650',
            'site_filters.has_no_active_orders' => 'nullable|boolean',
            'site_filters.customer_ids' => 'nullable|array',
            'site_filters.customer_ids.*' => 'integer',
            'site_filters.exclude_customer_ids' => 'nullable|array',
            'site_filters.exclude_customer_ids.*' => 'integer',

            'order_filters' => 'nullable|array',
            'order_filters.statuses' => 'nullable|array',
            'order_filters.statuses.*' => 'string|in:requested,planned,executed,closed',
            'order_filters.hazardous' => 'nullable|boolean',
            'order_filters.has_bulk' => 'nullable|boolean',
            'order_filters.cer_codes' => 'nullable|array',
            'order_filters.cer_codes.*' => 'string|max:10',
            'order_filters.cer_keyword' => 'nullable|string|max:100',
            'order_filters.cer_dangerous' => 'nullable|boolean',
            'order_filters.min_weight_kg' => 'nullable|numeric|min:0',
            'order_filters.max_weight_kg' => 'nullable|numeric|min:0',
            'order_filters.requested_from' => 'nullable|date_format:Y-m-d',
            'order_filters.requested_to' => 'nullable|date_format:Y-m-d',

            'sort' => 'required|array',
            'sort.mode' => 'required|string|in:distance,risk,urgency,mixed',
            'sort.weights' => 'nullable|array',
            'sort.weights.distance' => 'nullable|numeric|min:0|max:1',
            'sort.weights.risk' => 'nullable|numeric|min:0|max:1',
            'sort.weights.urgency' => 'nullable|numeric|min:0|max:1',

            'limit' => 'required|array',
            'limit.sites' => 'required|integer|min:1|max:500',
            'limit.orders' => 'required|integer|min:1|max:500',
        ]);

        $validator->after(function ($v) use ($parsed) {
            $scenario = $parsed['scenario'] ?? null;

            $riskMin = data_get($parsed, 'site_filters.risk_min');
            $riskMax = data_get($parsed, 'site_filters.risk_max');
            if ($riskMin !== null && $riskMax !== null && (float) $riskMin > (float) $riskMax) {
                $v->errors()->add('site_filters.risk_min', 'risk_min must be <= risk_max.');
            }

            $daysMin = data_get($parsed, 'site_filters.days_to_next_pickup_min');
            $daysMax = data_get($parsed, 'site_filters.days_to_next_pickup_max');
            if ($daysMin !== null && $daysMax !== null && (int) $daysMin > (int) $daysMax) {
                $v->errors()->add('site_filters.days_to_next_pickup_min', 'days_to_next_pickup_min must be <= max.');
            }

            $minWeight = data_get($parsed, 'order_filters.min_weight_kg');
            $maxWeight = data_get($parsed, 'order_filters.max_weight_kg');
            if ($minWeight !== null && $maxWeight !== null && (float) $minWeight > (float) $maxWeight) {
                $v->errors()->add('order_filters.min_weight_kg', 'min_weight_kg must be <= max_weight_kg.');
            }

            $timeFrom = data_get($parsed, 'time.from');
            $timeTo = data_get($parsed, 'time.to');
            if ($timeFrom && $timeTo && $timeFrom > $timeTo) {
                $v->errors()->add('time.from', 'time.from must be <= time.to.');
            }

            $reqFrom = data_get($parsed, 'order_filters.requested_from');
            $reqTo = data_get($parsed, 'order_filters.requested_to');
            if ($reqFrom && $reqTo && $reqFrom > $reqTo) {
                $v->errors()->add('order_filters.requested_from', 'requested_from must be <= requested_to.');
            }

            if (($parsed['sort']['mode'] ?? null) === 'mixed') {
                $weights = data_get($parsed, 'sort.weights');
                if (!is_array($weights)) {
                    $v->errors()->add('sort.weights', 'sort.weights is required when sort.mode is mixed.');
                } else {
                    $sum = (float) ($weights['distance'] ?? 0) + (float) ($weights['risk'] ?? 0) + (float) ($weights['urgency'] ?? 0);
                    if (abs($sum - 1.0) > 0.001) {
                        $v->errors()->add('sort.weights', 'sort.weights must sum to 1.');
                    }
                }
            }

            if ($scenario === 'planning_sites') {
                $hasSiteSignal =
                    data_get($parsed, 'site_filters.risk_min') !== null ||
                    data_get($parsed, 'site_filters.risk_max') !== null ||
                    data_get($parsed, 'site_filters.days_to_next_pickup_min') !== null ||
                    data_get($parsed, 'site_filters.days_to_next_pickup_max') !== null ||
                    data_get($parsed, 'site_filters.last_withdraw_days_min') !== null ||
                    data_get($parsed, 'site_filters.has_no_active_orders') !== null ||
                    data_get($parsed, 'geo.radius_km') !== null ||
                    data_get($parsed, 'reference.customer.id') !== null ||
                    data_get($parsed, 'reference.site.id') !== null;
                if (!$hasSiteSignal) {
                    $v->errors()->add('site_filters', 'site_filters should contain at least one planning filter in planning_sites scenario.');
                }
            }

            if ($scenario === 'order_requests') {
                $hasOrderSignal =
                    !empty(data_get($parsed, 'order_filters.statuses')) ||
                    data_get($parsed, 'order_filters.hazardous') !== null ||
                    data_get($parsed, 'order_filters.has_bulk') !== null ||
                    !empty(data_get($parsed, 'order_filters.cer_codes')) ||
                    data_get($parsed, 'order_filters.cer_keyword') !== null ||
                    data_get($parsed, 'order_filters.cer_dangerous') !== null ||
                    data_get($parsed, 'site_filters.last_withdraw_days_min') !== null ||
                    data_get($parsed, 'site_filters.has_no_active_orders') !== null ||
                    data_get($parsed, 'order_filters.min_weight_kg') !== null ||
                    data_get($parsed, 'order_filters.max_weight_kg') !== null ||
                    data_get($parsed, 'order_filters.requested_from') !== null ||
                    data_get($parsed, 'order_filters.requested_to') !== null;
                if (!$hasOrderSignal) {
                    $v->errors()->add('order_filters', 'order_filters should contain at least one order filter in order_requests scenario.');
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $warnings = [];

        $hasGeoAnchor = data_get($parsed, 'reference.customer') !== null
            || data_get($parsed, 'reference.site') !== null
            || data_get($parsed, 'reference.coordinates') !== null;

        if (!$hasGeoAnchor && data_get($parsed, 'geo.radius_km') !== null) {
            $warnings[] = 'radius_km provided without a geo anchor (customer, site or coordinates); geo filter will be ignored.';
        }

        return [
            'parsed' => $validator->validated(),
            'warnings' => $warnings,
        ];
    }
}

