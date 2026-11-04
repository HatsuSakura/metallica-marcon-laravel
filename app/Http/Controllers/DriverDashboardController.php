<?php

namespace App\Http\Controllers;

use App\Enums\JourneyStatus;
use App\Enums\JourneyStopStatus;
use App\Models\Journey;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DriverDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $today = CarbonImmutable::now('Europe/Rome')->startOfDay();
        $day = isset($validated['date'])
            ? CarbonImmutable::parse($validated['date'], 'Europe/Rome')->startOfDay()
            : $today;
        $start = $day->utc();
        $end = $day->addDay()->utc();

        $base = Journey::query()
            ->where('driver_id', $request->user()->id)
            ->with(['vehicle', 'trailer'])
            ->withCount([
                'stops',
                'stops as completed_stops_count' => fn ($q) => $q->where('status', JourneyStopStatus::Done->value),
                'stops as skipped_stops_count' => fn ($q) => $q->where('status', JourneyStopStatus::Skipped->value),
                'stops as cancelled_stops_count' => fn ($q) => $q->where('status', JourneyStopStatus::Cancelled->value),
            ]);

        $journeys = (clone $base)
            ->where('planned_start_at', '>=', $start)
            ->where('planned_start_at', '<', $end)
            ->orderBy('planned_start_at')->orderBy('id')->get();

        // Un viaggio attivo rimane raggiungibile anche dopo mezzanotte o senza data.
        $otherActive = (clone $base)
            ->where('status', JourneyStatus::STATUS_ACTIVE->value)
            ->where(fn ($q) => $q->whereNull('planned_start_at')
                ->orWhere('planned_start_at', '<', $start)
                ->orWhere('planned_start_at', '>=', $end))
            ->orderBy('planned_start_at')->orderBy('id')->get();

        $present = fn (Journey $journey) => [
            'id' => $journey->id,
            'status' => $journey->status?->value,
            'planned_start_at' => $journey->planned_start_at?->toIso8601String(),
            'actual_start_at' => $journey->actual_start_at?->toIso8601String(),
            'stops_count' => $journey->stops_count,
            'completed_stops_count' => $journey->completed_stops_count,
            'skipped_stops_count' => $journey->skipped_stops_count,
            'cancelled_stops_count' => $journey->cancelled_stops_count,
            'vehicle' => $journey->vehicle?->only(['name', 'plate']),
            'trailer' => $journey->trailer?->only(['name', 'plate']),
        ];

        return inertia('Dashboard/Driver', [
            'selectedDate' => $day->toDateString(),
            'today' => $today->toDateString(),
            'previousDate' => $day->subDay()->toDateString(),
            'nextDate' => $day->addDay()->toDateString(),
            'journeys' => $journeys->map($present),
            'otherActiveJourneys' => $otherActive->map($present),
        ]);
    }
}
