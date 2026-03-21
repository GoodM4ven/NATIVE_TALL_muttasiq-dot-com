<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Middleware\LogRepeatedUnmatchedRouteHits;
use App\Http\Middleware\TrackWebHomeMetrics;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->middleware(TrackWebHomeMetrics::class)
    ->name('home');

Route::get('/qpc-v2-fonts/{page}.ttf', function (int $page) {
    if ($page < 1 || $page > 604) {
        abort(404);
    }

    $candidates = [
        [
            'path' => base_path('resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.woff2'),
            'content_type' => 'font/woff2',
        ],
        [
            'path' => dirname(base_path()).'/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.woff2',
            'content_type' => 'font/woff2',
        ],
        [
            'path' => base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.woff2'),
            'content_type' => 'font/woff2',
        ],
        [
            'path' => base_path('resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.ttf'),
            'content_type' => 'font/ttf',
        ],
        [
            'path' => dirname(base_path()).'/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.ttf',
            'content_type' => 'font/ttf',
        ],
        [
            'path' => base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/qpc-v2/p'.$page.'.ttf'),
            'content_type' => 'font/ttf',
        ],
    ];

    $fontPath = null;
    $contentType = null;

    foreach ($candidates as $candidate) {
        $candidatePath = (string) ($candidate['path'] ?? '');
        $candidateContentType = (string) ($candidate['content_type'] ?? '');

        if ($candidatePath !== '' && $candidateContentType !== '' && is_file($candidatePath)) {
            $fontPath = $candidatePath;
            $contentType = $candidateContentType;

            break;
        }
    }

    if (! is_string($fontPath) || $fontPath === '' || ! is_string($contentType) || $contentType === '') {
        abort(404);
    }

    return response()->file($fontPath, [
        'Content-Type' => $contentType,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->whereNumber('page')->name('qpc-v2-font');

Route::fallback(fn () => abort(404))
    ->middleware(LogRepeatedUnmatchedRouteHits::class);
