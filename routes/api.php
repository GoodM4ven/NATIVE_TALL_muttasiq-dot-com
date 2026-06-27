<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AthkarController;
use App\Http\Controllers\Api\JsErrorReportController;
use App\Http\Controllers\Api\NativeAuthExchangeController;
use App\Http\Controllers\Api\QuranSnapshotDownloadController;
use App\Http\Controllers\Api\QuranSnapshotMetaController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\VisitMetricController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
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

    Route::get('/quran-snapshot/meta', QuranSnapshotMetaController::class)
        ->middleware('throttle:quran-snapshot')
        ->name('quran-snapshot.meta');

    Route::get('/quran-snapshot/download', QuranSnapshotDownloadController::class)
        ->middleware('throttle:quran-snapshot')
        ->name('quran-snapshot.download');
});
