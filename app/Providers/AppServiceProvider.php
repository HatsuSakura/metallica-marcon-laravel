<?php

namespace App\Providers;

use App\Models\Cargo;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Holder;
use App\Models\Journey;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\Site;
use App\Models\Trailer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Withdraw;
use App\Policies\CargoPolicy;
use App\Policies\CatalogItemPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\HolderPolicy;
use App\Policies\JourneyPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\RecipePolicy;
use App\Policies\SitePolicy;
use App\Policies\TrailerPolicy;
use App\Policies\UserPolicy;
use App\Policies\VehiclePolicy;
use App\Policies\WithdrawPolicy;
use App\Enums\UserRole;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(DatabaseNotification::class, NotificationPolicy::class);
        Gate::policy(Cargo::class, CargoPolicy::class);
        Gate::policy(CatalogItem::class, CatalogItemPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Holder::class, HolderPolicy::class);
        Gate::policy(Journey::class, JourneyPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Recipe::class, RecipePolicy::class);
        Gate::policy(Site::class, SitePolicy::class);
        Gate::policy(Trailer::class, TrailerPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(Withdraw::class, WithdrawPolicy::class);

        Gate::define('useLogisticsNlp', function (User $user): bool {
            return $user->is_admin || in_array($user->role, [
                UserRole::DEVELOPER,
                UserRole::MANAGER,
                UserRole::LOGISTIC,
            ], true);
        });

        Gate::define('accessBackofficeArea', function (User $user): bool {
            return $user->is_admin || in_array($user->role, [
                UserRole::DEVELOPER,
                UserRole::MANAGER,
                UserRole::LOGISTIC,
            ], true);
        });

        Gate::define('accessWarehouseArea', function (User $user): bool {
            return $user->is_admin || in_array($user->role, [
                UserRole::DEVELOPER,
                UserRole::MANAGER,
                UserRole::LOGISTIC,
                UserRole::WAREHOUSE_CHIEF,
                UserRole::WAREHOUSE_MANAGER,
                UserRole::WAREHOUSE_WORKER,
            ], true);
        });

        Gate::define('accessDriverArea', function (User $user): bool {
            return $user->is_admin || in_array($user->role, [
                UserRole::DEVELOPER,
                UserRole::MANAGER,
                UserRole::LOGISTIC,
                UserRole::DRIVER,
            ], true);
        });

        Inertia::share([
            'auth' => fn () => [
                'user' => auth()->user(),
            ],
        ]);
    }
}
