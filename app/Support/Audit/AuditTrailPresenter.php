<?php

namespace App\Support\Audit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderHolder;
use App\Models\OrderItem;
use App\Models\Site;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Models\Audit;

class AuditTrailPresenter
{
    public static function forModel(Model $model, int $limit = 50): array
    {
        if (!method_exists($model, 'audits')) {
            return [];
        }

        return $model->audits()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($audit) {
                $user = $audit->user;

                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'created_at' => optional($audit->created_at)?->toISOString(),
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => trim(collect([$user->name ?? null, $user->surname ?? null])->filter()->implode(' ')),
                        'email' => $user->email ?? null,
                    ] : null,
                    'old_values' => $audit->old_values ?? [],
                    'new_values' => $audit->new_values ?? [],
                    'tags' => $audit->tags,
                    'url' => $audit->url,
                ];
            })
            ->values()
            ->all();
    }

    public static function forOrder(Order $order, int $limit = 100): array
    {
        $itemIds = $order->items()->pluck('id')->all();
        $holderIds = $order->holders()->pluck('id')->all();

        return Audit::query()
            ->with('user')
            ->where(function ($query) use ($order, $itemIds, $holderIds) {
                $query->where(function ($inner) use ($order) {
                    $inner->where('auditable_type', Order::class)
                        ->where('auditable_id', $order->id);
                });

                if (!empty($itemIds)) {
                    $query->orWhere(function ($inner) use ($itemIds) {
                        $inner->where('auditable_type', OrderItem::class)
                            ->whereIn('auditable_id', $itemIds);
                    });
                }

                if (!empty($holderIds)) {
                    $query->orWhere(function ($inner) use ($holderIds) {
                        $inner->where('auditable_type', OrderHolder::class)
                            ->whereIn('auditable_id', $holderIds);
                    });
                }
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($audit) {
                $base = self::presentAudit($audit);
                $base['subject_type'] = $audit->auditable_type;
                $base['subject_id'] = $audit->auditable_id;
                $base['subject_label'] = self::subjectLabel($audit->auditable_type, (int) $audit->auditable_id);

                return $base;
            })
            ->values()
            ->all();
    }

    public static function forCustomer(Customer $customer, int $limit = 100): array
    {
        $sites = $customer->sites()->withTrashed()->get(['id', 'name']);
        $siteIds = $sites->pluck('id')->all();
        $siteNames = $sites->mapWithKeys(fn ($site) => [$site->id => $site->name])->all();

        $withdraws = Withdraw::query()
            ->withTrashed()
            ->whereIn('site_id', $siteIds)
            ->get(['id', 'site_id', 'withdrawn_at']);

        $withdrawIds = $withdraws->pluck('id')->all();
        $withdrawLabels = $withdraws->mapWithKeys(function ($withdraw) use ($siteNames) {
            $siteName = $siteNames[$withdraw->site_id] ?? null;
            $label = 'Ritiro';

            if ($siteName) {
                $label .= " {$siteName}";
            }

            if ($withdraw->withdrawn_at) {
                $label .= ' del ' . $withdraw->withdrawn_at->format('d/m/Y H:i');
            }

            return [$withdraw->id => $label];
        })->all();

        return Audit::query()
            ->with('user')
            ->where(function ($query) use ($customer, $siteIds, $withdrawIds) {
                $query->where(function ($inner) use ($customer) {
                    $inner->where('auditable_type', Customer::class)
                        ->where('auditable_id', $customer->id);
                });

                if (!empty($siteIds)) {
                    $query->orWhere(function ($inner) use ($siteIds) {
                        $inner->where('auditable_type', Site::class)
                            ->whereIn('auditable_id', $siteIds);
                    });
                }

                if (!empty($withdrawIds)) {
                    $query->orWhere(function ($inner) use ($withdrawIds) {
                        $inner->where('auditable_type', Withdraw::class)
                            ->whereIn('auditable_id', $withdrawIds);
                    });
                }
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($audit) use ($siteNames, $withdrawLabels) {
                $base = self::presentAudit($audit);
                $base['subject_type'] = $audit->auditable_type;
                $base['subject_id'] = $audit->auditable_id;
                $base['subject_label'] = self::customerSubjectLabel(
                    $audit->auditable_type,
                    (int) $audit->auditable_id,
                    $siteNames,
                    $withdrawLabels
                );

                return $base;
            })
            ->values()
            ->all();
    }

    private static function presentAudit($audit): array
    {
        $user = $audit->user;

        return [
            'id' => $audit->id,
            'event' => $audit->event,
            'created_at' => optional($audit->created_at)?->toISOString(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => trim(collect([$user->name ?? null, $user->surname ?? null])->filter()->implode(' ')),
                'email' => $user->email ?? null,
            ] : null,
            'old_values' => $audit->old_values ?? [],
            'new_values' => $audit->new_values ?? [],
            'tags' => $audit->tags,
            'url' => $audit->url,
        ];
    }

    private static function subjectLabel(?string $type, int $id): ?string
    {
        return match ($type) {
            Order::class => 'Ordine',
            OrderItem::class => "Item ordine #{$id}",
            OrderHolder::class => "Contenitore ordine #{$id}",
            default => null,
        };
    }

    private static function customerSubjectLabel(
        ?string $type,
        int $id,
        array $siteNames,
        array $withdrawLabels
    ): ?string {
        return match ($type) {
            Customer::class => 'Cliente',
            Site::class => isset($siteNames[$id]) ? "Sede {$siteNames[$id]}" : "Sede #{$id}",
            Withdraw::class => $withdrawLabels[$id] ?? "Ritiro #{$id}",
            default => null,
        };
    }
}
