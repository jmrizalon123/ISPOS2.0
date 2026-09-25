<?php

use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PosProvisionBindController;
use App\Http\Controllers\Api\V1\PosProvisionLookupController;
use App\Http\Controllers\Api\V1\PosProvisionResetController;
use App\Http\Controllers\Api\V1\PosDeviceRegisterController;
use App\Http\Controllers\Api\V1\SyncBootstrapController;
use App\Http\Controllers\Api\V1\SyncCatalogPullController;
use App\Http\Controllers\Api\V1\SyncPushController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class)->name('api.v1.health');

    Route::get('/pos/provision/lookup', PosProvisionLookupController::class)
        ->middleware('throttle:30,1')
        ->name('api.v1.pos.provision.lookup');

    Route::post('/pos/provision/bind', PosProvisionBindController::class)
        ->middleware('throttle:30,1')
        ->name('api.v1.pos.provision.bind');

    Route::post('/pos/provision/reset', PosProvisionResetController::class)
        ->middleware('throttle:30,1')
        ->name('api.v1.pos.provision.reset');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', MeController::class)->name('api.v1.me');
        Route::post('/devices/register', PosDeviceRegisterController::class)->name('api.v1.devices.register');

        Route::middleware('pos.device')->group(function () {
            Route::get('/sync/bootstrap', SyncBootstrapController::class)->name('api.v1.sync.bootstrap');
            Route::get('/sync/catalog', SyncCatalogPullController::class)->name('api.v1.sync.catalog');
            Route::post('/sync/push', SyncPushController::class)->name('api.v1.sync.push');
        });
    });
});
