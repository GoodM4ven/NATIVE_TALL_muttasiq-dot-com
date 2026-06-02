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
        $filePath = $snapshotApiService->compressedSnapshotPath();
        $fileSize = is_file($filePath) ? filesize($filePath) : false;

        if (! is_int($fileSize) || $fileSize < 1) {
            $metadata = $snapshotApiService->metadata();
            $fileSize = (int) $metadata['sizeBytes'];
        }

        return response()->download(
            $filePath,
            'quran-reader-snapshot.sqlite.gz',
            [
                'Content-Type' => 'application/gzip',
                'Content-Length' => (string) max(0, $fileSize),
                'Cache-Control' => 'public, max-age=300',
            ],
        );
    }
}
