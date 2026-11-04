<?php

namespace App\Services\JourneyExecution;

use App\Models\Journey;
use App\Models\Order;
use App\Models\OrderHolder;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

/** Protect model writes made outside the planning HTTP boundary as well. */
class FrozenPlanningGuard
{
    public static function register(): void
    {
        foreach ([Journey::class, Order::class, OrderItem::class, OrderHolder::class] as $class) {
            $class::saving(fn (Model $model) => self::check($model));
            $class::deleting(fn (Model $model) => self::check($model, true));
            $class::restoring(fn (Model $model) => self::check($model, true));
        }
    }

    private static function check(Model $model, bool $deleting = false): void
    {
        $changed = array_keys($model->getDirty());
        $businessChanges = array_filter($changed, fn ($key) => ! str_starts_with($key, 'documents_') && ! in_array($key, ['created_at', 'updated_at']));
        if (! $deleting && ! $businessChanges) {
            return;
        }
        if ($model instanceof Journey && $model->exists && $model->getRawOriginal('status') === 'creato' && $model->status !== \App\Enums\JourneyStatus::STATUS_CREATED) {
            abort(409, 'Usa il comando di partenza per congelare il piano.');
        }
        $ids = match (true) {
            $model instanceof Journey => [$model->id],
            $model instanceof Order => [$model->getRawOriginal('journey_id'), $model->journey_id],
            default => Order::withTrashed()->whereIn('id', array_filter([$model->getRawOriginal('order_id'), $model->order_id]))->pluck('journey_id')->all(),
        };
        abort_if(Journey::withTrashed()->whereIn('id', array_filter($ids))->where(fn ($q) => $q->whereHas('baseline')->orWhere('status', '!=', 'creato'))->exists(), 409,
            'Viaggio partito: modifica la sua esecuzione, non il pianificato.');
    }
}
