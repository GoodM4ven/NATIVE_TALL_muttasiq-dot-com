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

        $preparationService->markRunning(progressPercent: 1, message: arabic_text('يجري تنزيل بيانات القرآن...'));

        $temporaryGzipPath = null;
        $temporarySnapshotPath = null;

        try {
            $metadata = $this->fetchRemoteSnapshotMetadata();
            $snapshotInfo = $metadata['snapshot'] ?? null;

            if (! is_array($snapshotInfo)) {
                throw new RuntimeException('Remote Quran snapshot metadata is malformed.');
            }

            $downloadUrl = trim((string) ($snapshotInfo['downloadUrl'] ?? ''));
            $expectedChecksum = trim((string) ($snapshotInfo['checksumSha256'] ?? ''));
            $expectedSizeBytes = (int) ($snapshotInfo['sizeBytes'] ?? 0);

            if ($downloadUrl === '' || ! preg_match('/^https?:\/\//i', $downloadUrl)) {
                throw new RuntimeException('Remote Quran snapshot download URL is invalid.');
            }

            $temporaryDirectory = storage_path('app/private/native/quran-snapshot');
            File::ensureDirectoryExists($temporaryDirectory);

            $temporaryGzipPath = $temporaryDirectory.'/quran-reader-snapshot.sqlite.gz.tmp';
            $temporarySnapshotPath = $temporaryDirectory.'/quran-reader-snapshot.sqlite.tmp';

            File::delete($temporaryGzipPath);
            File::delete($temporarySnapshotPath);

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
        } catch (Throwable $throwable) {
            $preparationService->markFailed($throwable);

            throw $throwable;
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
        app(NativeQuranPreparationService::class)->markFailed($exception);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRemoteSnapshotMetadata(): array
    {
        $metadataUrl = trim((string) config('app.custom.native_end_points.quran_snapshot_meta', ''));

        if ($metadataUrl === '' || ! preg_match('/^https?:\/\//i', $metadataUrl)) {
            throw new RuntimeException('Native Quran snapshot metadata endpoint is missing or invalid.');
        }

        /** @var Response $response */
        $response = Http::acceptJson()
            ->connectTimeout(6)
            ->timeout(20)
            ->retry(2, 800)
            ->get($metadataUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Native Quran snapshot metadata request failed with status '.$response->status().'.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        return $payload;
    }

    private function downloadSnapshotArchive(
        string $downloadUrl,
        string $destinationPath,
        int $expectedSizeBytes,
        NativeQuranPreparationService $preparationService,
    ): void {
        /** @var Response $response */
        $response = Http::withOptions([
            'sink' => $destinationPath,
            'progress' => function (
                int|float $downloadTotal,
                int|float $downloadedBytes,
            ) use ($expectedSizeBytes, $preparationService): void {
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
            ->connectTimeout(8)
            ->timeout(900)
            ->retry(2, 1000)
            ->get($downloadUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Native Quran snapshot download failed with status '.$response->status().'.');
        }

        if (! is_file($destinationPath) || filesize($destinationPath) === 0) {
            throw new RuntimeException('Downloaded Quran snapshot archive is empty.');
        }
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
        $runtimeDatabasePath = (string) config('database.connections.sqlite.database', '');

        if ($runtimeDatabasePath === '') {
            throw new RuntimeException('Native sqlite database path is missing.');
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
        }
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

            $runtimePdo->exec('DROP TABLE IF EXISTS "'.$tableName.'"');
            $runtimePdo->exec($createSql);
            $runtimePdo->exec('INSERT INTO "'.$tableName.'" SELECT * FROM quran_source."'.$tableName.'"');
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

        foreach ($indexRows as $indexRow) {
            $indexName = trim((string) ($indexRow['name'] ?? ''));
            $createSql = trim((string) ($indexRow['sql'] ?? ''));

            if ($indexName === '' || $createSql === '') {
                continue;
            }

            $runtimePdo->exec('DROP INDEX IF EXISTS "'.$indexName.'"');
            $runtimePdo->exec($createSql);
        }
    }
}
