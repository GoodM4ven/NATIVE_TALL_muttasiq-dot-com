<?php

declare(strict_types=1);

namespace App\Services\Native;

use Illuminate\Support\Facades\File;
use RuntimeException;

class NativeQuranSnapshotApiService
{
    private const COMPRESSED_SNAPSHOT_PATH = 'database/native-quran-reader.sqlite.gz';

    public function __construct(
        private NativeQuranDatabaseSnapshotBuilder $snapshotBuilder,
    ) {}

    /**
     * @return array{
     *     signature: string,
     *     sizeBytes: int,
     *     checksumSha256: string,
     *     generatedAt: string,
     *     downloadUrl: string
     * }
     */
    public function metadata(): array
    {
        $snapshotResult = $this->snapshotBuilder->build();
        $snapshotPath = $this->snapshotBuilder->snapshotPath();
        $compressedPath = $this->ensureCompressedSnapshot($snapshotPath);

        $checksum = hash_file('sha256', $compressedPath);

        if (! is_string($checksum)) {
            throw new RuntimeException('Unable to compute checksum for the compressed Quran snapshot.');
        }

        $sizeBytes = filesize($compressedPath);

        if (! is_int($sizeBytes) || $sizeBytes < 1) {
            throw new RuntimeException('Compressed Quran snapshot is empty.');
        }

        $generatedAt = now()->toIso8601String();
        $compressedFileModifiedAt = filemtime($compressedPath);

        if (is_int($compressedFileModifiedAt) && $compressedFileModifiedAt > 0) {
            $generatedAt = now()->setTimestamp($compressedFileModifiedAt)->toIso8601String();
        }

        return [
            'signature' => $snapshotResult['signature'],
            'sizeBytes' => $sizeBytes,
            'checksumSha256' => $checksum,
            'generatedAt' => $generatedAt,
            'downloadUrl' => $this->resolveDownloadUrl(),
        ];
    }

    public function compressedSnapshotPath(): string
    {
        return base_path(self::COMPRESSED_SNAPSHOT_PATH);
    }

    private function ensureCompressedSnapshot(string $snapshotPath): string
    {
        if (! is_file($snapshotPath) || filesize($snapshotPath) === 0) {
            throw new RuntimeException('Native Quran snapshot SQLite file is missing.');
        }

        $compressedPath = $this->compressedSnapshotPath();
        File::ensureDirectoryExists(dirname($compressedPath));

        $snapshotModifiedAt = filemtime($snapshotPath) ?: 0;
        $compressedModifiedAt = is_file($compressedPath) ? (filemtime($compressedPath) ?: 0) : 0;

        if (! is_file($compressedPath) || filesize($compressedPath) === 0 || $compressedModifiedAt < $snapshotModifiedAt) {
            $this->compressSnapshot($snapshotPath, $compressedPath);
        }

        return $compressedPath;
    }

    private function compressSnapshot(string $snapshotPath, string $compressedPath): void
    {
        $temporaryCompressedPath = $compressedPath.'.tmp';

        File::delete($temporaryCompressedPath);

        $inputHandle = fopen($snapshotPath, 'rb');

        if ($inputHandle === false) {
            throw new RuntimeException("Unable to open Quran snapshot file for reading [{$snapshotPath}].");
        }

        $gzipHandle = gzopen($temporaryCompressedPath, 'wb9');

        if ($gzipHandle === false) {
            fclose($inputHandle);

            throw new RuntimeException("Unable to open compressed Quran snapshot file for writing [{$temporaryCompressedPath}].");
        }

        try {
            while (! feof($inputHandle)) {
                $chunk = fread($inputHandle, 1048576);

                if ($chunk === false) {
                    throw new RuntimeException("Failed while reading Quran snapshot file [{$snapshotPath}].");
                }

                if ($chunk === '') {
                    continue;
                }

                if (gzwrite($gzipHandle, $chunk) === false) {
                    throw new RuntimeException("Failed while writing compressed Quran snapshot file [{$temporaryCompressedPath}].");
                }
            }
        } finally {
            gzclose($gzipHandle);
            fclose($inputHandle);
        }

        File::delete($compressedPath);
        File::move($temporaryCompressedPath, $compressedPath);
    }

    private function resolveDownloadUrl(): string
    {
        $configuredDownloadUrl = trim((string) config('app.custom.native_end_points.quran_snapshot_download', ''));

        if ($this->isValidHttpUrl($configuredDownloadUrl)) {
            return $configuredDownloadUrl;
        }

        return route('api.quran-snapshot.download');
    }

    private function isValidHttpUrl(string $url): bool
    {
        return $url !== '' && preg_match('/^https?:\/\//i', $url) === 1;
    }
}
