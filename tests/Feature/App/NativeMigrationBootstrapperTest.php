<?php

declare(strict_types=1);

use App\Services\Native\NativeMigrationBootstrapper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

it('tracks deferred quran migration files and clears quran reader readiness caches', function () {
    $bootstrapper = app(NativeMigrationBootstrapper::class);
    $paths = $bootstrapper->deferredQuranMigrationPaths();
    $readerPreparationPaths = $bootstrapper->readerPreparationMigrationPaths();

    expect($paths)->toHaveCount(4);
    expect($readerPreparationPaths)->toHaveCount(3);

    foreach ($paths as $path) {
        expect($path)->toBeFile();
    }

    foreach ($readerPreparationPaths as $path) {
        expect($path)->toBeFile();
    }

    expect($readerPreparationPaths)->not->toContain(database_path('migrations/2026_03_20_180146_create_quran_explanations_tables.php'));
    expect($paths)->toContain(database_path('migrations/2026_03_20_180146_create_quran_explanations_tables.php'));

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

it('can prepare quran reader tables on a dedicated sqlite connection', function () {
    $bootstrapper = app(NativeMigrationBootstrapper::class);
    $connectionName = 'native_quran_snapshot_test';
    $databasePath = storage_path('framework/testing/native-quran-snapshot-test.sqlite');

    File::ensureDirectoryExists(dirname($databasePath));
    File::delete($databasePath);
    File::put($databasePath, '');

    config()->set('database.connections.'.$connectionName, [
        ...config('database.connections.sqlite'),
        'database' => $databasePath,
        'foreign_key_constraints' => true,
    ]);

    try {
        $status = $bootstrapper->runDeferredQuranMigrations($connectionName);

        expect($status)->toBe(0);
        expect(Schema::connection($connectionName)->hasTable('quran_verses'))->toBeTrue()
            ->and(Schema::connection($connectionName)->hasTable('quran_words'))->toBeTrue()
            ->and(Schema::connection($connectionName)->hasTable('quran_mushaf_lines'))->toBeTrue()
            ->and(Schema::connection($connectionName)->hasColumn('quran_verses', 'text_searchable_typed'))->toBeTrue()
            ->and(DB::connection($connectionName)->table('quran_verses')->count())->toBeGreaterThan(6200)
            ->and(DB::connection($connectionName)->table('quran_words')->count())->toBeGreaterThan(77000)
            ->and(DB::connection($connectionName)->table('quran_mushaf_lines')->count())->toBeGreaterThan(9000);
    } finally {
        DB::purge($connectionName);
        File::delete($databasePath);
    }
});
