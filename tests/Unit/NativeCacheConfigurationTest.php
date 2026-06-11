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
