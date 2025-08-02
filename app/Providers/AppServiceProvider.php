<?php

namespace App\Providers;

use App\Models\User;
use Inertia\Inertia;
use App\Policies\NotificationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\DatabaseNotification;

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
        Inertia::share([
            'auth' => fn () => [
                'user' => auth()->user(),
            ],
        ]);
    }
}
