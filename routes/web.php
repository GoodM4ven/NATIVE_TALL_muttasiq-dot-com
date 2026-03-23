<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Quran\ReaderPageDataController;
use App\Http\Controllers\Quran\ReaderSearchIndexController;
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

Route::get('/quran-surah-header-font', function () {
    $fontConfig = config('arabicable.quran_fonts.surah_headers', [
        'family' => 'SurahNameV4',
        'filename' => 'surah-name-v4.ttf',
        'format' => 'ttf',
    ]);

    if (! is_array($fontConfig)) {
        abort(404);
    }

    $filename = trim((string) ($fontConfig['filename'] ?? ''));
    $format = trim((string) ($fontConfig['format'] ?? 'woff2'));
    $configuredSurahHeadersDir = trim((string) config(
        'arabicable.data_sources.quran_surah_headers_fonts_dir',
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/surah-headers'),
    ));
    $configuredFontsDir = trim((string) config(
        'arabicable.data_sources.quran_fonts_dir',
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts'),
    ));

    if ($filename === '') {
        abort(404);
    }

    $paths = [
        $configuredSurahHeadersDir !== '' ? $configuredSurahHeadersDir.'/'.$filename : null,
        $configuredFontsDir !== '' ? $configuredFontsDir.'/'.$filename : null,
        base_path('resources/raw-data/quran/fonts/surah-headers/'.$filename),
        dirname(base_path()).'/resources/raw-data/quran/fonts/surah-headers/'.$filename,
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/surah-headers/'.$filename),
        base_path('resources/raw-data/quran/fonts/'.$filename),
        dirname(base_path()).'/resources/raw-data/quran/fonts/'.$filename,
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/'.$filename),
        base_path('vendor/goodm4ven/arabicable/resources/dist/'.$filename),
    ];

    $fontPath = null;

    foreach ($paths as $path) {
        if (! is_string($path) || $path === '') {
            continue;
        }

        if (is_file($path)) {
            $fontPath = $path;

            break;
        }
    }

    if ($fontPath === null) {
        abort(404);
    }

    $isTrueType = in_array($format, ['ttf', 'truetype'], true) || str_ends_with($filename, '.ttf');

    return response()->file($fontPath, [
        'Content-Type' => $isTrueType ? 'font/ttf' : 'font/woff2',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->name('quran-surah-header-font');

Route::get('/quran-basmallah-font/{fontKey}', function (string $fontKey) {
    $fontConfig = config('arabicable.quran_fonts.basmalah', []);

    if (! is_array($fontConfig)) {
        abort(404);
    }

    $availableFonts = $fontConfig['available'] ?? [];

    if (! is_array($availableFonts) || ! isset($availableFonts[$fontKey])) {
        abort(404);
    }

    $preferredFont = $availableFonts[$fontKey];

    if (! is_array($preferredFont)) {
        abort(404);
    }

    $filename = trim((string) ($preferredFont['filename'] ?? ''));
    $format = trim((string) ($preferredFont['format'] ?? 'woff2'));
    $configuredSurahHeadersDir = trim((string) config(
        'arabicable.data_sources.quran_surah_headers_fonts_dir',
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/surah-headers'),
    ));
    $configuredFontsDir = trim((string) config(
        'arabicable.data_sources.quran_fonts_dir',
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts'),
    ));

    if ($filename === '') {
        abort(404);
    }

    $paths = [
        $configuredSurahHeadersDir !== '' ? $configuredSurahHeadersDir.'/'.$filename : null,
        $configuredFontsDir !== '' ? $configuredFontsDir.'/'.$filename : null,
        base_path('resources/raw-data/quran/fonts/surah-headers/'.$filename),
        dirname(base_path()).'/resources/raw-data/quran/fonts/surah-headers/'.$filename,
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/surah-headers/'.$filename),
        base_path('resources/raw-data/quran/fonts/'.$filename),
        dirname(base_path()).'/resources/raw-data/quran/fonts/'.$filename,
        base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/'.$filename),
        base_path('vendor/goodm4ven/arabicable/resources/dist/'.$filename),
    ];

    $fontPath = null;

    foreach ($paths as $path) {
        if (! is_string($path) || $path === '') {
            continue;
        }

        if (is_file($path)) {
            $fontPath = $path;

            break;
        }
    }

    if ($fontPath === null) {
        abort(404);
    }

    $isTrueType = in_array($format, ['ttf', 'truetype'], true) || str_ends_with($filename, '.ttf');

    return response()->file($fontPath, [
        'Content-Type' => $isTrueType ? 'font/ttf' : 'font/woff2',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->name('quran-basmallah-font');

Route::get('/quran-reader/pages/{page}.json', ReaderPageDataController::class)
    ->whereNumber('page')
    ->name('quran-reader-page-data');

Route::get('/quran-reader/search-index.json', ReaderSearchIndexController::class)
    ->name('quran-reader-search-index');

Route::fallback(fn () => abort(404))
    ->middleware(LogRepeatedUnmatchedRouteHits::class);
