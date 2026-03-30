<?php

declare(strict_types=1);

namespace App\Services\Native;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class NativeMigrationBootstrapper
{
    /**
     * @var list<string>
     */
    private const DEFERRED_QURAN_MIGRATIONS = [
        '2026_03_20_180143_create_common_arabic_texts_table.php',
        '2026_03_20_180144_create_arabic_stop_words_table.php',
        '2026_03_20_180145_create_quran_index_tables.php',
        '2026_03_20_180146_create_quran_explanations_tables.php',
    ];

    /**
     * @var list<string>
     */
    private const QURAN_READER_CACHE_KEYS = [
        'quran-reader-ready-v2',
        'quran-reader-max-page-v2',
        'quran-reader-search-index-v1',
        'quran-reader-surah-directory-v2',
    ];

    public function runBootstrapMigrations(): int
    {
        return $this->runWithoutDeferredQuranMigrations(
            fn (): int => Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]),
        );
    }

    public function runDeferredQuranMigrations(): int
    {
        $bootstrapStatus = $this->runBootstrapMigrations();

        if ($bootstrapStatus !== 0) {
            return $bootstrapStatus;
        }

        $status = Artisan::call('migrate', [
            '--path' => $this->deferredQuranMigrationPaths(),
            '--realpath' => true,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $this->flushQuranReaderCaches();

        return $status;
    }

    /**
     * @return list<string>
     */
    public function deferredQuranMigrationPaths(): array
    {
        return array_map(
            static fn (string $migration): string => database_path('migrations/'.$migration),
            self::DEFERRED_QURAN_MIGRATIONS,
        );
    }

    public function flushQuranReaderCaches(): void
    {
        foreach (self::QURAN_READER_CACHE_KEYS as $cacheKey) {
            Cache::forget($cacheKey);
            Cache::memo()->forget($cacheKey);
        }
    }

    /**
     * @param  callable(): int  $callback
     */
    private function runWithoutDeferredQuranMigrations(callable $callback): int
    {
        Migrator::withoutMigrations($this->deferredQuranMigrationPaths());

        try {
            return $callback();
        } finally {
            Migrator::withoutMigrations([]);
            $this->flushQuranReaderCaches();
        }
    }
}
