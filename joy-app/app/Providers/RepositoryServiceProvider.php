<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\ContentItemRepositoryInterface;
use App\Repositories\Contracts\ConceptRepositoryInterface;
use App\Repositories\ContentItemRepository;
use App\Repositories\ConceptRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ContentItemRepositoryInterface::class, ContentItemRepository::class);
        $this->app->bind(ConceptRepositoryInterface::class, ConceptRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
