<?php

declare(strict_types=1);

it('keeps native packaging defaults compatible with bundled web assets and native runtime fallbacks', function () {
    expect(config('nativephp.cleanup_exclude_files'))
        ->not->toContain('build');

    expect(config('nativephp.runtime'))->toMatchArray([
        'mode' => 'persistent',
        'reset_instances' => true,
        'gc_between_dispatches' => false,
    ]);

    expect(config('nativephp.server'))->toMatchArray([
        'http_port' => 3000,
        'ws_port' => 8081,
        'service_name' => 'NativePHP Server',
        'service_type' => '_http._tcp',
        'public_path' => 'public',
        'build_path' => 'storage/app/native-build',
        'open_browser' => true,
    ]);

    expect(config('nativephp.server.watch_paths'))->toBe([
        'app',
        'resources',
        'routes',
        'public/build',
    ]);

    expect(config('nativephp.server.watch_extensions'))->toBe([
        'php',
        'blade.php',
        'js',
        'css',
        'ts',
        'vue',
        'json',
    ]);

    expect(config('nativephp.android'))->toMatchArray([
        'compile_sdk' => 36,
        'min_sdk' => 33,
        'target_sdk' => 36,
        'status_bar_style' => 'auto',
    ]);

    $previousNativeRunning = getenv('NATIVEPHP_RUNNING');
    $previousDbConnection = getenv('DB_CONNECTION');

    putenv('NATIVEPHP_RUNNING=true');
    putenv('DB_CONNECTION=mysql');
    $previousCacheStore = getenv('CACHE_STORE');
    putenv('CACHE_STORE=redis');

    try {
        /** @var array{default: string} $databaseConfig */
        $databaseConfig = require config_path('database.php');

        expect($databaseConfig['default'])->toBe('sqlite');
        /** @var array{default: string} $cacheConfig */
        $cacheConfig = require config_path('cache.php');

        expect($cacheConfig['default'])->toBe('file');
    } finally {
        putenv(
            $previousNativeRunning === false
                ? 'NATIVEPHP_RUNNING'
                : "NATIVEPHP_RUNNING={$previousNativeRunning}",
        );
        putenv(
            $previousCacheStore === false
                ? 'CACHE_STORE'
                : "CACHE_STORE={$previousCacheStore}",
        );
        putenv(
            $previousDbConnection === false
                ? 'DB_CONNECTION'
                : "DB_CONNECTION={$previousDbConnection}",
        );
    }
});
