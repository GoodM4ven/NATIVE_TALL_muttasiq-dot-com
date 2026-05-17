<?php

declare(strict_types=1);

use App\Services\Quran\QuranReaderDataService;

test('quran reader search sanitizes mixed payloads and keeps arabic matches', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    $arabicPhrase = 'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ';
    $mixedPayload = <<<'TEXT'
TypeError: Cannot read properties of null (reading 'id')
app-CRGV6Tb4.js:102
search payload: الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ
Mozilla/5.0 Linux Android SM-G950U Build/NRD90M
TEXT;

    $baselineResults = $service->searchProgressively($arabicPhrase, 24);
    $mixedResults = $service->searchProgressively($mixedPayload, 24);

    $baselineIds = array_values(array_filter(array_map(
        static fn (array $match): int => (int) ($match['id'] ?? 0),
        $baselineResults,
    )));
    $mixedIds = array_values(array_filter(array_map(
        static fn (array $match): int => (int) ($match['id'] ?? 0),
        $mixedResults,
    )));

    expect($baselineIds)->not->toBe([])
        ->and($mixedIds)->not->toBe([])
        ->and(array_intersect(array_slice($baselineIds, 0, 5), $mixedIds))->not->toBe([]);
});

test('quran reader search ignores non arabic payloads without throwing', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    $nonArabicPayload = <<<'TEXT'
TypeError: Cannot read properties of null (reading 'id')
(index):285 browser logger active
stack trace line 1455
SM-G950U Build/NRD90M
TEXT;

    $results = $service->searchProgressively($nonArabicPayload, 24);

    expect($results)->toBeArray()->toBe([]);
});
