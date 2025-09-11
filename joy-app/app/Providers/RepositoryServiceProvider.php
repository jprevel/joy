<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\VariantRepositoryInterface;
use App\Repositories\Contracts\ConceptRepositoryInterface;
use App\Repositories\VariantRepository;
use App\Repositories\ConceptRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(VariantRepositoryInterface::class, VariantRepository::class);
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
