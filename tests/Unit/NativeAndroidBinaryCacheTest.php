<?php

declare(strict_types=1);

use Native\Mobile\Traits\InstallsAndroid;

function createNativeAndroidBinaryCacheTempDirectory(string $prefix = 'muttasiq-native-android-cache-'): string
{
    $basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefix.bin2hex(random_bytes(6));

    if (! mkdir($basePath, 0777, true) && ! is_dir($basePath)) {
        throw new RuntimeException("Unable to create temporary directory at [{$basePath}].");
    }

    return $basePath;
}

function removeNativeAndroidBinaryCacheTempDirectory(string $path): void
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

        removeNativeAndroidBinaryCacheTempDirectory($path.DIRECTORY_SEPARATOR.$item);
    }

    @rmdir($path);
}

it('detects valid and invalid android binary cache archives', function () {
    $workspace = createNativeAndroidBinaryCacheTempDirectory();

    try {
        $invalidZip = $workspace.'/invalid.zip';
        file_put_contents($invalidZip, str_repeat('not-a-zip', 128));

        $validZip = $workspace.'/valid.zip';
        $archive = new ZipArchive;
        expect($archive->open($validZip, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();
        $archive->addFromString('payload.txt', 'hello');
        $archive->close();

        $checker = new class
        {
            use InstallsAndroid {
                isValidZipArchive as public;
            }
        };

        expect($checker->isValidZipArchive($invalidZip))->toBeFalse();
        expect($checker->isValidZipArchive($validZip))->toBeTrue();
    } finally {
        removeNativeAndroidBinaryCacheTempDirectory($workspace);
    }
});
