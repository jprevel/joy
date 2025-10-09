<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Statusfaction access - admin and agency roles only
        Gate::define('access statusfaction', function ($user) {
            return $user->hasRole(['admin', 'agency']);
        });
    }
}
