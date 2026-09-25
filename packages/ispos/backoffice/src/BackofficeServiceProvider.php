<?php

namespace Ispos\Backoffice;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BackofficeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/backoffice.php', 'backoffice');

        $packagePages = realpath(__DIR__.'/../resources/js/Pages') ?: __DIR__.'/../resources/js/Pages';

        $pagePaths = config('inertia.page_paths') ?? [resource_path('js/Pages')];
        if (! in_array($packagePages, $pagePaths, true)) {
            config(['inertia.page_paths' => [...$pagePaths, $packagePages]]);
        }

        $testingPaths = config('inertia.testing.page_paths') ?? [resource_path('js/Pages')];
        if (! in_array($packagePages, $testingPaths, true)) {
            config(['inertia.testing.page_paths' => [...$testingPaths, $packagePages]]);
        }
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->group(__DIR__.'/../routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'backoffice');
    }
}
