<?php

namespace App\Providers;

use App\Contracts\SlackServiceContract;
use App\Contracts\SlackNotificationServiceContract;
use App\Contracts\SlackBlockFormatterContract;
use App\Contracts\UserManagementContract;
use App\Contracts\ClientManagementContract;
use App\Contracts\AuditEventFormatterContract;
use App\Services\SlackService;
use App\Services\SlackNotificationService;
use App\Services\SlackBlockFormatter;
use App\Services\UserManagementService;
use App\Services\ClientManagementService;
use App\Services\AuditEventFormatterService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Slack Integration Service Bindings
        $this->app->bind(SlackServiceContract::class, SlackService::class);
        $this->app->bind(SlackNotificationServiceContract::class, SlackNotificationService::class);
        $this->app->bind(SlackBlockFormatterContract::class, SlackBlockFormatter::class);

        // Admin Section Service Bindings
        $this->app->bind(UserManagementContract::class, UserManagementService::class);
        $this->app->bind(ClientManagementContract::class, ClientManagementService::class);
        $this->app->bind(AuditEventFormatterContract::class, AuditEventFormatterService::class);
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

        // Register Slack Integration Observers
        \App\Models\Comment::observe(\App\Observers\CommentObserver::class);
        \App\Models\ContentItem::observe(\App\Observers\ContentItemObserver::class);
        \App\Models\ClientStatusfactionUpdate::observe(\App\Observers\ClientStatusUpdateObserver::class);

        // Register Admin Section Observers
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Client::observe(\App\Observers\ClientObserver::class);
    }
}
