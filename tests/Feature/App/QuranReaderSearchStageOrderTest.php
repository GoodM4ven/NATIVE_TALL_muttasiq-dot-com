<?php

declare(strict_types=1);

use App\Services\Quran\QuranReaderDataService;

test('quran search keeps divine-name tokens out of sarf and jathr stages', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('تالله', 20);

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and(collect($results)->contains(
            static fn (array $item): bool => (string) ($item['match_strategy'] ?? '') === 'ayah_exact',
        ))->toBeTrue()
        ->and(collect($results)->contains(
            static fn (array $item): bool => in_array(
                (string) ($item['match_strategy'] ?? ''),
                ['ayah_sarf', 'ayah_jathr'],
                true,
            ),
        ))->toBeFalse();
});

test('quran search does not return unrelated surah close or sarf matches for verse-style phrases', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('محمد إلا رسول', 24);

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and(collect($results)->contains(
            static fn (array $item): bool => in_array(
                (string) ($item['match_strategy'] ?? ''),
                ['surah_close', 'surah_sarf'],
                true,
            ),
        ))->toBeFalse();
});
