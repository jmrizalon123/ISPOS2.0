<?php

namespace App\Providers;

use App\Support\PublicStorage;
use Illuminate\Support\Facades\Vite;
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
        PublicStorage::ensureStorageLink();

        Vite::prefetch(concurrency: 3);
    }
}
