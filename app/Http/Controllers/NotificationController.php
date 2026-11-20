<?php

namespace App\Http\Controllers;

use App\Notifications\JourneyExecutionUpdated;
use App\Services\JourneyExecution\ExecutionNotificationView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('from') ? ['after_or_equal:from'] : []),
            'read' => ['nullable', Rule::in(['all', 'unread', 'read'])], 'type' => ['nullable', Rule::in(['all', JourneyExecutionUpdated::TYPE])],
        ]);
        $query = $request->user()->notifications();
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'], 'Europe/Rome')->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'));
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<', Carbon::parse($filters['to'], 'Europe/Rome')->addDay()->startOfDay()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'));
        }
        if (($filters['read'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }
        if (($filters['read'] ?? 'all') === 'read') {
            $query->whereNotNull('read_at');
        }
        if (($filters['type'] ?? 'all') !== 'all') {
            $query->where('type', $filters['type']);
        }

        return inertia(
            'Notification/Index',
            [
                'notifications' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($notification) => app(ExecutionNotificationView::class)->present($notification, $request->user())),
                'filters' => $filters,
            ]
        );
    }

    public function count(Request $request)
    {
        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()])->header('Cache-Control', 'private, no-store');
    }
}
