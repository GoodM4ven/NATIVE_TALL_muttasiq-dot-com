<?php

declare(strict_types=1);

use App\Services\Native\NativeQuranSnapshotApiService;

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
