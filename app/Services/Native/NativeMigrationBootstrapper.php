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
    private const DEFERRED_QURAN_READER_MIGRATIONS = [
        '2026_03_20_180143_create_common_arabic_texts_table.php',
        '2026_03_20_180144_create_arabic_stop_words_table.php',
        '2026_03_20_180145_create_quran_index_tables.php',
    ];

    /**
     * @var list<string>
     */
    private const DEFERRED_QURAN_EXPLANATION_MIGRATIONS = [
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

    public function runBootstrapMigrations(?string $database = null): int
    {
        return $this->runWithoutDeferredQuranMigrations(
            fn (): int => Artisan::call('migrate', $this->migrationCommandArguments($database)),
        );
    }

    public function runDeferredQuranMigrations(?string $database = null): int
    {
        $bootstrapStatus = $this->runBootstrapMigrations($database);

        if ($bootstrapStatus !== 0) {
            return $bootstrapStatus;
        }

        $status = Artisan::call('migrate', [
            ...$this->migrationCommandArguments($database),
            '--path' => $this->readerPreparationMigrationPaths(),
            '--realpath' => true,
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
            [
                ...self::DEFERRED_QURAN_READER_MIGRATIONS,
                ...self::DEFERRED_QURAN_EXPLANATION_MIGRATIONS,
            ],
        );
    }

    /**
     * @return list<string>
     */
    public function readerPreparationMigrationPaths(): array
    {
        return array_map(
            static fn (string $migration): string => database_path('migrations/'.$migration),
            self::DEFERRED_QURAN_READER_MIGRATIONS,
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

    /**
     * @return array<string, string|true>
     */
    private function migrationCommandArguments(?string $database = null): array
    {
        $arguments = [
            '--force' => true,
            '--no-interaction' => true,
        ];

        if ($database !== null && $database !== '') {
            $arguments['--database'] = $database;
        }

        return $arguments;
    }
}
