<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Plugins\Parallel;
use Tests\TestCase;

require_once __DIR__.'/Support/Browser/Core.php';
require_once __DIR__.'/Support/Browser/Helpers.php';

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature/App');

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature/Browser')
    ->group('browser')
    ->beforeAll(function () {
        assertBrowserAssetsReady();
    });

if (isBrowserPluginEnabled()) {
    pest()
        ->browser()
        ->timeout((int) env('PEST_BROWSER_TIMEOUT_MS', 1500));
}

if (! Parallel::isWorker() && isBrowserPluginEnabled()) {
    runPlaywrightBrowserPreflight();

    register_shutdown_function(static function (): void {
        runPlaywrightBrowserPreflight();
    });
}
