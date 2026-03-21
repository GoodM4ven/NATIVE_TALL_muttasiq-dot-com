<?php

declare(strict_types=1);

namespace App\Http\Controllers\Quran;

use App\Http\Controllers\Controller;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReaderPageDataController extends Controller
{
    public function __invoke(Request $request, int $page, QuranReaderDataService $readerDataService): JsonResponse
    {
        $activeAyahIndex = (int) $request->integer('active_ayah_index', 0);
        $payload = $readerDataService->resolvePage($page, $activeAyahIndex);

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=604800, stale-while-revalidate=2592000');
    }
}
