<?php

declare(strict_types=1);

namespace App\Http\Controllers\Quran;

use App\Http\Controllers\Controller;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Http\JsonResponse;

class ReaderSearchIndexController extends Controller
{
    public function __invoke(QuranReaderDataService $readerDataService): JsonResponse
    {
        $payload = [
            'ready' => $readerDataService->isReady(),
            'items' => $readerDataService->searchIndex(),
            'surah_names' => $readerDataService->surahNames(),
        ];

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=604800, stale-while-revalidate=2592000');
    }
}
