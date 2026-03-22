<?php

declare(strict_types=1);

namespace App\Http\Controllers\Quran;

use App\Http\Controllers\Controller;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReaderSearchIndexController extends Controller
{
    public function __invoke(Request $request, QuranReaderDataService $readerDataService): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query !== '') {
            return response()
                ->json([
                    'ready' => $readerDataService->isReady(),
                    'query' => $query,
                    'items' => $readerDataService->search($query, 24),
                ])
                ->header('Cache-Control', 'no-store, max-age=0');
        }

        $payload = [
            'ready' => $readerDataService->isReady(),
            'surah_names' => $readerDataService->surahNames(),
            'surah_directory' => $readerDataService->surahDirectory(),
        ];

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=604800, stale-while-revalidate=2592000');
    }
}
