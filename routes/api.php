<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AthkarController;
use App\Http\Controllers\Api\JsErrorReportController;
use App\Http\Controllers\Api\NativeAuthClaimController;
use App\Http\Controllers\Api\NativeAuthExchangeController;
use App\Http\Controllers\Api\NativeSyncController;
use App\Http\Controllers\Api\QuranSnapshotDownloadController;
use App\Http\Controllers\Api\QuranSnapshotMetaController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\VisitMetricController;
use App\Http\Middleware\RequireSanctumAccessToken;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::match(['get', 'post'], '/broadcasting/auth', [BroadcastController::class, 'authenticate'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:60,1'])
        ->name('broadcasting.auth');

    Route::get('/athkar', AthkarController::class)
        ->middleware('throttle:athkar')
        ->name('athkar.index');

    Route::get('/settings', SettingsController::class)
        ->middleware('throttle:settings')
        ->name('settings.index');

    Route::post('/js-error-reports', JsErrorReportController::class)
        ->middleware('throttle:js-error-reports')
        ->name('js-error-reports.store');

    Route::post('/visit-metrics', VisitMetricController::class)
        ->middleware('throttle:visit-metrics')
        ->name('visit-metrics.store');

    Route::post('/native-auth/exchange', NativeAuthExchangeController::class)
        ->middleware('throttle:6,1')
        ->name('native-auth.exchange');

    Route::post('/native-auth/claim', NativeAuthClaimController::class)
        ->middleware('throttle:20,1')
        ->name('native-auth.claim');

    Route::post('/native-sync/password', [NativeSyncController::class, 'password'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:6,1'])
        ->name('native-sync.password');

    Route::post('/native-sync/settings', [NativeSyncController::class, 'settings'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:30,1'])
        ->name('native-sync.settings');

    Route::get('/native-sync/snapshot', [NativeSyncController::class, 'snapshot'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:30,1'])
        ->name('native-sync.snapshot');

    Route::get('/native-sync/devices', [NativeSyncController::class, 'devices'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:30,1'])
        ->name('native-sync.devices');

    Route::post('/native-sync/devices/revoke', [NativeSyncController::class, 'revokeDevice'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:30,1'])
        ->name('native-sync.devices.revoke');

    Route::post('/native-auth/two-factor', [NativeSyncController::class, 'twoFactor'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:6,1'])
        ->name('native-auth.two-factor');

    Route::post('/native-sync/delete', [NativeSyncController::class, 'destroy'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:6,1'])
        ->name('native-sync.delete');

    Route::post('/native-sync/logout', [NativeSyncController::class, 'revoke'])
        ->middleware(['auth:sanctum', RequireSanctumAccessToken::class, 'throttle:6,1'])
        ->name('native-sync.logout');

    Route::get('/quran-snapshot/meta', QuranSnapshotMetaController::class)
        ->middleware('throttle:quran-snapshot')
        ->name('quran-snapshot.meta');

    Route::get('/quran-snapshot/download', QuranSnapshotDownloadController::class)
        ->middleware('throttle:quran-snapshot')
        ->name('quran-snapshot.download');
});
