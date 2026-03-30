<?php

declare(strict_types=1);

use App\Services\Native\NativeMigrationBootstrapper;
use Illuminate\Support\Facades\Cache;

it('tracks deferred quran migration files and clears quran reader readiness caches', function () {
    $bootstrapper = app(NativeMigrationBootstrapper::class);
    $paths = $bootstrapper->deferredQuranMigrationPaths();

    expect($paths)->toHaveCount(4);

    foreach ($paths as $path) {
        expect($path)->toBeFile();
    }

    Cache::put('quran-reader-ready-v2', false, now()->addMinutes(5));
    Cache::put('quran-reader-max-page-v2', 0, now()->addMinutes(5));
    Cache::memo()->put('quran-reader-ready-v2', false, now()->addMinutes(5));
    Cache::memo()->put('quran-reader-max-page-v2', 0, now()->addMinutes(5));

    $bootstrapper->flushQuranReaderCaches();

    expect(Cache::get('quran-reader-ready-v2'))->toBeNull()
        ->and(Cache::get('quran-reader-max-page-v2'))->toBeNull()
        ->and(Cache::memo()->get('quran-reader-ready-v2'))->toBeNull()
        ->and(Cache::memo()->get('quran-reader-max-page-v2'))->toBeNull();
});
