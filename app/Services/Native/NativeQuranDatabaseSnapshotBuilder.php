<?php

declare(strict_types=1);

namespace App\Services\Native;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class NativeQuranDatabaseSnapshotBuilder
{
    private const SNAPSHOT_CONNECTION = 'native_quran_snapshot';

    private const SNAPSHOT_DATABASE_PATH = 'database/native-quran-reader.sqlite';

    private const SNAPSHOT_MANIFEST_PATH = 'database/native-quran-reader.json';

    public function __construct(
        private NativeMigrationBootstrapper $bootstrapper,
    ) {}

    /**
     * @return array{built: bool, path: string, signature: string}
     */
    public function build(bool $force = false): array
    {
        $signature = $this->snapshotSignature();
        $snapshotPath = $this->snapshotPath();
        $manifestPath = $this->manifestPath();

        if (! $force && $this->hasCurrentSnapshot($signature)) {
            return [
                'built' => false,
                'path' => $snapshotPath,
                'signature' => $signature,
            ];
        }

        $temporarySnapshotPath = $snapshotPath.'.tmp';

        File::ensureDirectoryExists(dirname($snapshotPath));
        File::delete($temporarySnapshotPath);
        File::put($temporarySnapshotPath, '');

        $this->configureSnapshotConnection($temporarySnapshotPath);

        try {
            $status = $this->bootstrapper->runDeferredQuranMigrations(self::SNAPSHOT_CONNECTION);

            if ($status !== 0) {
                throw new RuntimeException(
                    'Failed to build the bundled native Quran snapshot: '.trim(Artisan::output()),
                );
            }

            $this->assertSnapshotReady();
            $this->compactSnapshot();
            DB::disconnect(self::SNAPSHOT_CONNECTION);

            File::delete($snapshotPath);
            File::move($temporarySnapshotPath, $snapshotPath);
            File::put($manifestPath, $this->manifestJson($signature));
        } catch (\Throwable $exception) {
            File::delete($temporarySnapshotPath);

            throw $exception;
        } finally {
            DB::purge(self::SNAPSHOT_CONNECTION);
        }

        return [
            'built' => true,
            'path' => $snapshotPath,
            'signature' => $signature,
        ];
    }

    public function snapshotPath(): string
    {
        return base_path(self::SNAPSHOT_DATABASE_PATH);
    }

    public function manifestPath(): string
    {
        return base_path(self::SNAPSHOT_MANIFEST_PATH);
    }

    public function snapshotSignature(): string
    {
        return hash('sha256', json_encode($this->signaturePayload(), JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{
     *     generated_at: string,
     *     signature: string
     * }
     */
    private function manifestData(string $signature): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'signature' => $signature,
        ];
    }

    private function manifestJson(string $signature): string
    {
        return json_encode(
            $this->manifestData($signature),
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        ).PHP_EOL;
    }

    /**
     * @return array{
     *     arabicable_version: string,
     *     bootstrapper_hash: string,
     *     migration_hashes: array<string, string>
     * }
     */
    private function signaturePayload(): array
    {
        return [
            'arabicable_version' => $this->lockedPackageVersion('goodm4ven/arabicable'),
            'bootstrapper_hash' => $this->requiredFileHash(app_path('Services/Native/NativeMigrationBootstrapper.php')),
            'migration_hashes' => $this->migrationHashes(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function migrationHashes(): array
    {
        $migrationFiles = File::files(database_path('migrations'));
        $paths = [];

        foreach ($migrationFiles as $migrationFile) {
            $realPath = $migrationFile->getRealPath();
            $paths[] = is_string($realPath) && $realPath !== '' ? $realPath : $migrationFile->getPathname();
        }

        sort($paths);

        $hashes = [];

        foreach ($paths as $path) {
            $hashes[basename($path)] = $this->requiredFileHash($path);
        }

        return $hashes;
    }

    private function hasCurrentSnapshot(string $signature): bool
    {
        $snapshotPath = $this->snapshotPath();
        $manifestPath = $this->manifestPath();

        if (! is_file($snapshotPath) || filesize($snapshotPath) === 0 || ! is_file($manifestPath)) {
            return false;
        }

        try {
            /** @var array{signature?: string} $manifest */
            $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }

        return ($manifest['signature'] ?? null) === $signature;
    }

    private function configureSnapshotConnection(string $databasePath): void
    {
        /** @var array<string, mixed> $sqliteConnection */
        $sqliteConnection = config('database.connections.sqlite', []);
        $snapshotConnection = [
            ...$sqliteConnection,
            'database' => $databasePath,
            'foreign_key_constraints' => true,
        ];

        config()->set('database.connections.'.self::SNAPSHOT_CONNECTION, $snapshotConnection);
        DB::purge(self::SNAPSHOT_CONNECTION);
    }

    private function assertSnapshotReady(): void
    {
        $schema = Schema::connection(self::SNAPSHOT_CONNECTION);

        if (! $schema->hasTable('quran_verses') || ! $schema->hasTable('quran_words') || ! $schema->hasTable('quran_mushaf_lines')) {
            throw new RuntimeException('Bundled native Quran snapshot is missing the reader tables.');
        }

        if (! $schema->hasColumn('quran_verses', 'text_searchable_typed')) {
            throw new RuntimeException('Bundled native Quran snapshot is missing quran_verses.text_searchable_typed.');
        }

        $connection = DB::connection(self::SNAPSHOT_CONNECTION);
        $verseCount = (int) $connection->table('quran_verses')->count();
        $wordCount = (int) $connection->table('quran_words')->count();
        $lineCount = (int) $connection->table('quran_mushaf_lines')->count();

        if ($verseCount < 6200 || $wordCount < 77000 || $lineCount < 9000) {
            throw new RuntimeException('Bundled native Quran snapshot did not populate the expected reader rows.');
        }
    }

    private function compactSnapshot(): void
    {
        DB::connection(self::SNAPSHOT_CONNECTION)->unprepared('VACUUM');
    }

    private function lockedPackageVersion(string $packageName): string
    {
        /** @var array{packages?: array<int, array{name?: string, version?: string}>} $lock */
        $lock = json_decode(File::get(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($lock['packages'] ?? [] as $package) {
            if (($package['name'] ?? null) === $packageName) {
                return (string) ($package['version'] ?? '');
            }
        }

        throw new RuntimeException("Package [{$packageName}] was not found in composer.lock.");
    }

    private function requiredFileHash(string $path): string
    {
        $hash = hash_file('sha256', $path);

        if (! is_string($hash)) {
            throw new RuntimeException("Unable to hash required file [{$path}].");
        }

        return $hash;
    }
}
