<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Native\NativeQuranSnapshotApiService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuranSnapshotDownloadController extends Controller
{
    public function __invoke(NativeQuranSnapshotApiService $snapshotApiService): BinaryFileResponse
    {
        $metadata = $snapshotApiService->metadata();
        $filePath = $snapshotApiService->compressedSnapshotPath();

        return response()->download(
            $filePath,
            'quran-reader-snapshot.sqlite.gz',
            [
                'Content-Type' => 'application/gzip',
                'Content-Length' => (string) $metadata['sizeBytes'],
                'X-Quran-Snapshot-Signature' => (string) $metadata['signature'],
                'X-Quran-Snapshot-Checksum' => (string) $metadata['checksumSha256'],
                'Cache-Control' => 'public, max-age=300',
            ],
        );
    }
}
