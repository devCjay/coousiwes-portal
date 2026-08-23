<?php

namespace App\Providers;

use App\Support\PortalPermission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(static function (object $user, string $ability): ?bool {
            return PortalPermission::hasFallbackPermission($user, $ability) ?: null;
        });
    }
}
