<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

function createAndroidSdkFallbackTempDirectory(string $prefix = 'muttasiq-android-sdk-fallback-'): string
{
    $basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefix.bin2hex(random_bytes(6));

    if (! mkdir($basePath, 0777, true) && ! is_dir($basePath)) {
        throw new RuntimeException("Unable to create temporary directory at [{$basePath}].");
    }

    return $basePath;
}

function removeAndroidSdkFallbackTempDirectory(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        @unlink($path);

        return;
    }

    $items = scandir($path);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        removeAndroidSdkFallbackTempDirectory($path.DIRECTORY_SEPARATOR.$item);
    }

    @rmdir($path);
}

function restoreAndroidSdkFallbackEnv(string $key, string|false|null $previousValue): void
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
