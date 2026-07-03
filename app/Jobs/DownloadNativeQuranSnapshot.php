<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Native\NativeQuranPreparationService;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PDO;
use RuntimeException;
use Throwable;

class DownloadNativeQuranSnapshot implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public bool $failOnTimeout = true;

    /**
     * Canonical production snapshot endpoint. Used as a fallback when the
     * configured (e.g. local dev-broadcast) endpoint is unreachable.
     */
    private const PRODUCTION_METADATA_ENDPOINT = 'https://muttasiq.com/api/quran-snapshot/meta';

    public function handle(
        NativeQuranPreparationService $preparationService,
        QuranReaderDataService $readerDataService,
    ): void {
        if (! is_platform('mobile')) {
            return;
        }

        if ($readerDataService->isReady()) {
            $preparationService->markReady();

            return;
        }

        $this->assertInternetConnection($preparationService);

        $preparationService->markRunning(progressPercent: 1, message: arabic_text('يجري تنزيل بيانات القرآن...'));

        $lastError = null;

        // Try the configured endpoint first (which watch scripts point at a local
        // source), then fall back to production muttasiq.com so a flaky/missing
        // local snapshot still resolves online instead of failing outright.
        foreach ($this->metadataEndpointCandidates() as $metadataUrl) {
            try {
                $this->syncSnapshotFromEndpoint($metadataUrl, $preparationService, $readerDataService);

                return;
            } catch (Throwable $throwable) {
                $lastError = $throwable;

                // Offline: no other endpoint will help, so stop retrying.
                if ($preparationService->isInternetConnected() === false) {
                    break;
                }
            }
        }

        if ($preparationService->isInternetConnected() === false) {
            $preparationService->markNoInternetConnection();
        } else {
            $preparationService->markFailed($lastError);
        }

        throw $lastError ?? new RuntimeException('Native Quran snapshot sync failed for every endpoint.');
    }

    /**
     * @return list<string>
     */
    private function metadataEndpointCandidates(): array
    {
        $configured = $this->metadataEndpointUrl();

        return array_values(array_unique([$configured, self::PRODUCTION_METADATA_ENDPOINT]));
    }

    private function syncSnapshotFromEndpoint(
        string $metadataUrl,
        NativeQuranPreparationService $preparationService,
        QuranReaderDataService $readerDataService,
    ): void {
        $temporaryGzipPath = null;
        $temporarySnapshotPath = null;

        try {
            $metadata = $this->fetchRemoteSnapshotMetadata($metadataUrl, $preparationService);
            $snapshotInfo = $metadata['snapshot'] ?? null;

            if (! is_array($snapshotInfo)) {
                throw new RuntimeException('Remote Quran snapshot metadata is malformed.');
            }

            $downloadUrl = $this->resolveDownloadUrl($snapshotInfo, $metadataUrl);
            $expectedChecksum = trim((string) ($snapshotInfo['checksumSha256'] ?? ''));
            $expectedSizeBytes = (int) ($snapshotInfo['sizeBytes'] ?? 0);

            $temporaryDirectory = storage_path('app/private/native/quran-snapshot');
            File::ensureDirectoryExists($temporaryDirectory);

            $temporaryGzipPath = $temporaryDirectory.'/quran-reader-snapshot.sqlite.gz.tmp';
            $temporarySnapshotPath = $temporaryDirectory.'/quran-reader-snapshot.sqlite.tmp';

            File::delete($temporaryGzipPath);
            File::delete($temporarySnapshotPath);

            $this->assertInternetConnection($preparationService);

            $this->downloadSnapshotArchive(
                $downloadUrl,
                $temporaryGzipPath,
                $expectedSizeBytes,
                $preparationService,
            );

            if ($expectedChecksum !== '') {
                $this->assertSnapshotChecksum($temporaryGzipPath, $expectedChecksum);
            }

            $preparationService->markRunning(
                progressPercent: 94,
                downloadedBytes: $expectedSizeBytes > 0 ? $expectedSizeBytes : null,
                totalBytes: $expectedSizeBytes > 0 ? $expectedSizeBytes : null,
                message: arabic_text('يجري تجهيز المصحف على جهازك...'),
            );

            $this->decompressSnapshotArchive($temporaryGzipPath, $temporarySnapshotPath);
            $this->replaceQuranTablesFromSnapshot($temporarySnapshotPath);

            $readerDataService->forgetReadinessCaches();

            if (! $readerDataService->isReady()) {
                throw new RuntimeException('Quran data is still not ready after importing the downloaded snapshot.');
            }

            $preparationService->markReady();
        } finally {
            if (is_string($temporaryGzipPath)) {
                File::delete($temporaryGzipPath);
            }

            if (is_string($temporarySnapshotPath)) {
                File::delete($temporarySnapshotPath);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        $preparationService = app(NativeQuranPreparationService::class);

        if ($preparationService->isInternetConnected() === false) {
            $preparationService->markNoInternetConnection();

            return;
        }

        $preparationService->markFailed($exception);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRemoteSnapshotMetadata(
        string $metadataUrl,
        NativeQuranPreparationService $preparationService,
    ): array {
        try {
            $this->assertInternetConnection($preparationService);

            /** @var Response $response */
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(120)
                ->retry(2, 1000)
                ->get($metadataUrl);
        } catch (Throwable $throwable) {
            throw new RuntimeException(
                "Native Quran snapshot metadata request failed for [{$metadataUrl}]: ".$throwable->getMessage(),
                previous: $throwable,
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException("Native Quran snapshot metadata request failed with status {$response->status()} for [{$metadataUrl}].");
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return $payload;
    }

    private function metadataEndpointUrl(): string
    {
        $metadataUrl = trim((string) config('app.custom.native_end_points.quran_snapshot_meta', ''));

        if ($metadataUrl === '' || ! preg_match('/^https?:\/\//i', $metadataUrl)) {
            throw new RuntimeException("Native Quran snapshot metadata endpoint is missing or invalid [{$metadataUrl}].");
        }

        return $metadataUrl;
    }

    private function downloadSnapshotArchive(
        string $downloadUrl,
        string $destinationPath,
        int $expectedSizeBytes,
        NativeQuranPreparationService $preparationService,
    ): void {
        try {
            $this->assertInternetConnection($preparationService);

            /** @var Response $response */
            $response = Http::withOptions([
                'sink' => $destinationPath,
                'progress' => function (
                    int|float $downloadTotal,
                    int|float $downloadedBytes,
                ) use ($expectedSizeBytes, $preparationService): void {
                    if ($preparationService->isInternetConnected() === false) {
                        throw new RuntimeException($preparationService->offlineFailureMessage());
                    }

                    $knownTotalBytes = $downloadTotal > 0
                        ? (int) round($downloadTotal)
                        : ($expectedSizeBytes > 0 ? $expectedSizeBytes : null);

                    $progressPercent = null;

                    if (is_int($knownTotalBytes) && $knownTotalBytes > 0) {
                        $progressPercent = (int) floor(((int) round($downloadedBytes) / $knownTotalBytes) * 92);
                    }

                    $preparationService->markDownloadProgress(
                        downloadedBytes: max(0, (int) round($downloadedBytes)),
                        totalBytes: $knownTotalBytes,
                        progressPercent: $progressPercent,
                    );
                },
            ])
                ->connectTimeout(6)
                ->timeout(180)
                ->retry(2, 1000)
                ->get($downloadUrl);
        } catch (Throwable $throwable) {
            throw new RuntimeException(
                "Native Quran snapshot download request failed for [{$downloadUrl}]: ".$throwable->getMessage(),
                previous: $throwable,
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException("Native Quran snapshot download failed with status {$response->status()} for [{$downloadUrl}].");
        }

        if (! is_file($destinationPath) || filesize($destinationPath) === 0) {
            throw new RuntimeException('Downloaded Quran snapshot archive is empty.');
        }
    }

    /**
     * @param  array<string, mixed>  $snapshotInfo
     */
    private function resolveDownloadUrl(array $snapshotInfo, string $metadataUrl): string
    {
        $localMetadataDownloadUrl = $this->downloadUrlFromLocalMetadataEndpoint($metadataUrl);

        if ($localMetadataDownloadUrl !== null) {
            return $localMetadataDownloadUrl;
        }

        $downloadUrl = trim((string) ($snapshotInfo['downloadUrl'] ?? ''));

        if ($this->isValidHttpUrl($downloadUrl)) {
            return $downloadUrl;
        }

        $fallbackDownloadUrl = trim((string) config('app.custom.native_end_points.quran_snapshot_download', ''));

        if ($this->isValidHttpUrl($fallbackDownloadUrl)) {
            return $fallbackDownloadUrl;
        }

        throw new RuntimeException('Remote Quran snapshot download URL is invalid.');
    }

    private function isValidHttpUrl(string $url): bool
    {
        return $url !== '' && preg_match('/^https?:\/\//i', $url) === 1;
    }

    private function assertInternetConnection(NativeQuranPreparationService $preparationService): void
    {
        if ($preparationService->isInternetConnected() === false) {
            throw new RuntimeException($preparationService->offlineFailureMessage());
        }
    }

    private function downloadUrlFromLocalMetadataEndpoint(string $metadataUrl): ?string
    {
        $parts = parse_url($metadataUrl);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || ! $this->isLocalNetworkHost($host)) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';

        return "{$scheme}://{$host}{$port}/api/quran-snapshot/download";
    }

    private function isLocalNetworkHost(string $host): bool
    {
        if (in_array($host, ['localhost', '10.0.2.2'], true) || str_starts_with($host, '127.')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        if (str_starts_with($host, '10.') || str_starts_with($host, '192.168.') || str_starts_with($host, '169.254.')) {
            return true;
        }

        if (! preg_match('/^172\.(\d{1,2})\./', $host, $matches)) {
            return false;
        }

        $secondOctet = (int) $matches[1];

        return $secondOctet >= 16 && $secondOctet <= 31;
    }

    private function assertSnapshotChecksum(string $path, string $expectedChecksum): void
    {
        $checksum = hash_file('sha256', $path);

        if (! is_string($checksum) || ! hash_equals(strtolower($expectedChecksum), strtolower($checksum))) {
            throw new RuntimeException('Downloaded Quran snapshot checksum verification failed.');
        }
    }

    private function decompressSnapshotArchive(string $sourcePath, string $destinationPath): void
    {
        $gzipHandle = gzopen($sourcePath, 'rb');

        if ($gzipHandle === false) {
            throw new RuntimeException('Unable to open downloaded Quran snapshot archive.');
        }

        $outputHandle = fopen($destinationPath, 'wb');

        if ($outputHandle === false) {
            gzclose($gzipHandle);

            throw new RuntimeException('Unable to open temporary Quran snapshot database file for writing.');
        }

        try {
            while (! gzeof($gzipHandle)) {
                $chunk = gzread($gzipHandle, 1048576);

                if ($chunk === false) {
                    throw new RuntimeException('Failed while decompressing the Quran snapshot archive.');
                }

                if ($chunk === '') {
                    continue;
                }

                if (fwrite($outputHandle, $chunk) === false) {
                    throw new RuntimeException('Failed while writing the decompressed Quran snapshot database.');
                }
            }
        } finally {
            gzclose($gzipHandle);
            fclose($outputHandle);
        }

        if (! is_file($destinationPath) || filesize($destinationPath) === 0) {
            throw new RuntimeException('The decompressed Quran snapshot database is empty.');
        }
    }

    private function replaceQuranTablesFromSnapshot(string $snapshotPath): void
    {
        $runtimeConnectionName = $this->resolveRuntimeSqliteConnectionName();
        $runtimeDatabasePath = $this->resolveRuntimeDatabasePath($runtimeConnectionName);

        if ($runtimeConnectionName !== null) {
            // Release the framework-managed sqlite handle before raw PDO schema replacement.
            DB::disconnect($runtimeConnectionName);
            DB::purge($runtimeConnectionName);
        }

        File::ensureDirectoryExists(dirname($runtimeDatabasePath));

        if (! is_file($runtimeDatabasePath)) {
            File::put($runtimeDatabasePath, '');
        }

        $runtimePdo = new PDO('sqlite:'.$runtimeDatabasePath);
        $runtimePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $runtimePdo->exec('PRAGMA busy_timeout = 10000');
        $runtimePdo->exec('PRAGMA foreign_keys = OFF');
        $runtimePdo->exec('ATTACH DATABASE '.$runtimePdo->quote($snapshotPath).' AS quran_source');

        try {
            $runtimePdo->beginTransaction();
            $this->replaceQuranTables($runtimePdo);
            $this->replaceQuranIndexes($runtimePdo);
            $runtimePdo->commit();
        } catch (Throwable $throwable) {
            if ($runtimePdo->inTransaction()) {
                $runtimePdo->rollBack();
            }

            throw $throwable;
        } finally {
            try {
                $runtimePdo->exec('DETACH DATABASE quran_source');
            } catch (Throwable) {
                //
            }

            $runtimePdo->exec('PRAGMA foreign_keys = ON');

            if ($runtimeConnectionName !== null) {
                DB::purge($runtimeConnectionName);
                DB::reconnect($runtimeConnectionName);
            }
        }
    }

    private function resolveRuntimeSqliteConnectionName(): ?string
    {
        $defaultConnection = (string) config('database.default', '');

        if ($this->isSqliteConnection($defaultConnection)) {
            return $defaultConnection;
        }

        if ($this->isSqliteConnection('sqlite')) {
            return 'sqlite';
        }

        return null;
    }

    private function isSqliteConnection(string $connectionName): bool
    {
        if ($connectionName === '') {
            return false;
        }

        return (string) config("database.connections.{$connectionName}.driver", '') === 'sqlite';
    }

    private function resolveRuntimeDatabasePath(?string $connectionName): string
    {
        $runtimeDatabasePath = '';

        if ($connectionName !== null) {
            $runtimeDatabasePath = (string) config("database.connections.{$connectionName}.database", '');
        }

        if ($runtimeDatabasePath === '') {
            $runtimeDatabasePath = (string) config('database.connections.sqlite.database', '');
        }

        if ($runtimeDatabasePath === '' || $runtimeDatabasePath === ':memory:') {
            throw new RuntimeException('Native sqlite database path is missing or invalid.');
        }

        return $runtimeDatabasePath;
    }

    private function replaceQuranTables(PDO $runtimePdo): void
    {
        $tableNames = [
            'common_arabic_texts',
            'arabic_stop_words',
            'quran_verses',
            'quran_words',
            'quran_mushaf_lines',
        ];

        foreach ($tableNames as $tableName) {
            $statement = $runtimePdo->query(
                "SELECT sql FROM quran_source.sqlite_master WHERE type = 'table' AND name = ".$runtimePdo->quote($tableName).' LIMIT 1',
            );
            if ($statement === false) {
                throw new RuntimeException("Unable to read downloaded Quran snapshot schema for table [{$tableName}].");
            }

            $createSql = $statement->fetchColumn();

            if (! is_string($createSql) || trim($createSql) === '') {
                throw new RuntimeException("Downloaded Quran snapshot is missing table definition for [{$tableName}].");
            }

            $statement->closeCursor();
            $statement = null;

            $runtimePdo->exec('DROP TABLE IF EXISTS main."'.$tableName.'"');
            $runtimePdo->exec($createSql);
            $runtimePdo->exec('INSERT INTO main."'.$tableName.'" SELECT * FROM quran_source."'.$tableName.'"');
        }
    }

    private function replaceQuranIndexes(PDO $runtimePdo): void
    {
        $statement = $runtimePdo->query(<<<'SQL'
SELECT name, sql
FROM quran_source.sqlite_master
WHERE type = 'index'
  AND sql IS NOT NULL
  AND tbl_name IN (
      'common_arabic_texts',
      'arabic_stop_words',
      'quran_verses',
      'quran_words',
      'quran_mushaf_lines'
  )
SQL);

        $indexRows = $statement !== false ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

        if ($statement !== false) {
            $statement->closeCursor();
        }

        foreach ($indexRows as $indexRow) {
            $indexName = trim((string) ($indexRow['name'] ?? ''));
            $createSql = trim((string) ($indexRow['sql'] ?? ''));

            if ($indexName === '' || $createSql === '') {
                continue;
            }

            $runtimePdo->exec('DROP INDEX IF EXISTS main."'.$indexName.'"');
            $runtimePdo->exec($createSql);
        }
    }
}
