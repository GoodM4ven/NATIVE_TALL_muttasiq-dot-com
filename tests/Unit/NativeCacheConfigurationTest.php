<?php

declare(strict_types=1);

use App\Support\Native\NativeCachePath;
use Tests\TestCase;

uses(TestCase::class);

function createNativeCacheConfigurationTempDirectory(string $prefix = 'muttasiq-native-cache-config-'): string
{
    $basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefix.bin2hex(random_bytes(6));

    if (! mkdir($basePath, 0777, true) && ! is_dir($basePath)) {
        throw new RuntimeException("Unable to create temporary directory at [{$basePath}].");
    }

    return $basePath;
}

function restoreNativeCacheConfigurationEnv(string $key, string|false|null $previousValue): void
{
    if ($previousValue === false || $previousValue === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return;
    }

    putenv("{$key}={$previousValue}");
    $_ENV[$key] = $previousValue;
    $_SERVER[$key] = $previousValue;
}

function setNativeCacheConfigurationEnv(string $key, ?string $value): void
{
    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return;
    }

    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

it('stores the native file cache in the native temp directory', function () {
    $tempDir = createNativeCacheConfigurationTempDirectory();
    $previousNativeTempDir = getenv('NATIVEPHP_TEMPDIR');

    try {
        putenv("NATIVEPHP_TEMPDIR={$tempDir}");
        $_ENV['NATIVEPHP_TEMPDIR'] = $tempDir;
        $_SERVER['NATIVEPHP_TEMPDIR'] = $tempDir;

        expect(NativeCachePath::resolve())->toBe($tempDir);
    } finally {
        restoreNativeCacheConfigurationEnv('NATIVEPHP_TEMPDIR', $previousNativeTempDir);
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }
});

it('uses file backed cache and sessions in the native runtime', function () {
    $previousNativeRunning = getenv('NATIVEPHP_RUNNING');
    $previousNativePlatform = getenv('NATIVEPHP_PLATFORM');
    $previousCacheStore = getenv('CACHE_STORE');
    $previousSessionDriver = getenv('SESSION_DRIVER');

    try {
        setNativeCacheConfigurationEnv('NATIVEPHP_RUNNING', 'true');
        setNativeCacheConfigurationEnv('NATIVEPHP_PLATFORM', null);
        setNativeCacheConfigurationEnv('CACHE_STORE', 'redis');
        setNativeCacheConfigurationEnv('SESSION_DRIVER', 'redis');

        $cacheConfig = require base_path('config/cache.php');
        $sessionConfig = require base_path('config/session.php');

        expect($cacheConfig['default'])->toBe('file')
            ->and($sessionConfig['driver'])->toBe('file');
    } finally {
        restoreNativeCacheConfigurationEnv('NATIVEPHP_RUNNING', $previousNativeRunning);
        restoreNativeCacheConfigurationEnv('NATIVEPHP_PLATFORM', $previousNativePlatform);
        restoreNativeCacheConfigurationEnv('CACHE_STORE', $previousCacheStore);
        restoreNativeCacheConfigurationEnv('SESSION_DRIVER', $previousSessionDriver);
    }
});

it('preserves configured cache and session drivers outside the native runtime', function () {
    $previousNativeRunning = getenv('NATIVEPHP_RUNNING');
    $previousNativePlatform = getenv('NATIVEPHP_PLATFORM');
    $previousCacheStore = getenv('CACHE_STORE');
    $previousSessionDriver = getenv('SESSION_DRIVER');

    try {
        setNativeCacheConfigurationEnv('NATIVEPHP_RUNNING', null);
        setNativeCacheConfigurationEnv('NATIVEPHP_PLATFORM', null);
        setNativeCacheConfigurationEnv('CACHE_STORE', 'redis');
        setNativeCacheConfigurationEnv('SESSION_DRIVER', 'redis');

        $cacheConfig = require base_path('config/cache.php');
        $sessionConfig = require base_path('config/session.php');

        expect($cacheConfig['default'])->toBe('redis')
            ->and($sessionConfig['driver'])->toBe('redis');
    } finally {
        restoreNativeCacheConfigurationEnv('NATIVEPHP_RUNNING', $previousNativeRunning);
        restoreNativeCacheConfigurationEnv('NATIVEPHP_PLATFORM', $previousNativePlatform);
        restoreNativeCacheConfigurationEnv('CACHE_STORE', $previousCacheStore);
        restoreNativeCacheConfigurationEnv('SESSION_DRIVER', $previousSessionDriver);
    }
});
