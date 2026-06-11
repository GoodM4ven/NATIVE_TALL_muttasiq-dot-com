<?php

declare(strict_types=1);

namespace App\Support\Native;

class NativeCachePath
{
    public static function resolve(): string
    {
        $nativeCachePath = trim((string) getenv('NATIVEPHP_TEMPDIR'));

        if ($nativeCachePath !== '') {
            return $nativeCachePath;
        }

        return storage_path('framework/cache/data');
    }
}
