<?php

declare(strict_types=1);

use App\Services\Native\NativeQuranDatabaseSnapshotBuilder;
use App\Services\Native\NativeQuranSnapshotApiService;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\getJson;

it('returns quran snapshot metadata from the api endpoint', function () {
    $snapshotApiService = new class extends NativeQuranSnapshotApiService
    {
        public function __construct() {}

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
            return [
                'signature' => 'snapshot-signature',
                'sizeBytes' => 1024,
                'checksumSha256' => str_repeat('a', 64),
                'generatedAt' => now()->toIso8601String(),
                'downloadUrl' => 'https://muttasiq.com/api/quran-snapshot/download',
            ];
        }
    };

    app()->instance(NativeQuranSnapshotApiService::class, $snapshotApiService);

    $response = getJson(route('api.quran-snapshot.meta'));

    $response->assertOk()
        ->assertJsonPath('snapshot.signature', 'snapshot-signature')
        ->assertJsonPath('snapshot.sizeBytes', 1024)
        ->assertJsonPath('snapshot.downloadUrl', 'https://muttasiq.com/api/quran-snapshot/download');
});

it('uses the configured snapshot download endpoint when generating metadata', function () {
    $snapshotDatabasePath = storage_path('framework/testing/native-quran-reader.sqlite');
    $compressedSnapshotPath = storage_path('framework/testing/native-quran-reader.sqlite.gz');
    $configuredDownloadUrl = 'http://127.0.0.1:8787/api/quran-snapshot/download';

    File::ensureDirectoryExists(dirname($snapshotDatabasePath));
    File::put($snapshotDatabasePath, 'sqlite-payload');
    File::delete($compressedSnapshotPath);

    $snapshotBuilder = new class($snapshotDatabasePath) extends NativeQuranDatabaseSnapshotBuilder
    {
        public function __construct(private string $snapshotDatabasePath) {}

        /**
         * @return array{built: bool, path: string, signature: string}
         */
        public function build(bool $force = false): array
        {
            return [
                'built' => false,
                'path' => $this->snapshotDatabasePath,
                'signature' => 'snapshot-signature',
            ];
        }

        public function snapshotPath(): string
        {
            return $this->snapshotDatabasePath;
        }
    };

    $snapshotApiService = new class($snapshotBuilder, $compressedSnapshotPath) extends NativeQuranSnapshotApiService
    {
        public function __construct(
            NativeQuranDatabaseSnapshotBuilder $snapshotBuilder,
            private string $compressedSnapshotPath,
        ) {
            parent::__construct($snapshotBuilder);
        }

        public function compressedSnapshotPath(): string
        {
            return $this->compressedSnapshotPath;
        }
    };

    config()->set('app.custom.native_end_points.quran_snapshot_download', $configuredDownloadUrl);

    $metadata = $snapshotApiService->metadata();

    expect($metadata['downloadUrl'])->toBe($configuredDownloadUrl);
    expect($metadata['sizeBytes'])->toBeGreaterThan(0);

    File::delete($snapshotDatabasePath);
    File::delete($compressedSnapshotPath);
});
