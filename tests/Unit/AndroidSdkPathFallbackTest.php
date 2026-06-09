<?php

declare(strict_types=1);

use Native\Mobile\Traits\RunsAndroid;
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

it('falls back to android home when config sdk path is empty', function () {
    $workspace = createAndroidSdkFallbackTempDirectory();
    $originalBasePath = base_path();
    $previousAndroidHome = getenv('ANDROID_HOME');
    $previousAndroidSdkRoot = getenv('ANDROID_SDK_ROOT');
    $previousConfigSdkPath = config('nativephp.android.android_sdk_path');

    try {
        app()->setBasePath($workspace);
        mkdir($workspace.'/nativephp/android', 0777, true);

        putenv('ANDROID_HOME=/tmp/android-home-sdk');
        putenv('ANDROID_SDK_ROOT');
        $_ENV['ANDROID_HOME'] = '/tmp/android-home-sdk';
        $_SERVER['ANDROID_HOME'] = '/tmp/android-home-sdk';
        unset($_ENV['ANDROID_SDK_ROOT'], $_SERVER['ANDROID_SDK_ROOT']);
        config(['nativephp.android.android_sdk_path' => null]);

        $runner = new class
        {
            use RunsAndroid {
                updateLocalProperties as public;
            }

            protected function detectCurrentAppId(): ?string
            {
                return null;
            }

            protected function updateAppId(string $oldAppId, string $newAppId): void {}

            protected function updateVersionConfiguration(): void {}

            protected function updateAppDisplayName(): void {}

            protected function updateDeepLinkConfiguration(): void {}

            protected function updatePermissions(): void {}

            protected function updateIcuConfiguration(): void {}

            protected function updateFirebaseConfiguration(): void {}

            protected function removeDirectory(string $path): void {}

            protected function platformOptimizedCopy(string $source, string $destination, array $excludedDirs): void {}
        };

        $runner->updateLocalProperties();

        $localPropertiesPath = $workspace.'/nativephp/android/local.properties';
        expect(file_get_contents($localPropertiesPath))->toBe("sdk.dir=/tmp/android-home-sdk\n");
    } finally {
        restoreAndroidSdkFallbackEnv('ANDROID_HOME', $previousAndroidHome);
        restoreAndroidSdkFallbackEnv('ANDROID_SDK_ROOT', $previousAndroidSdkRoot);
        config(['nativephp.android.android_sdk_path' => $previousConfigSdkPath]);
        app()->setBasePath($originalBasePath);
        removeAndroidSdkFallbackTempDirectory($workspace);
    }
});
