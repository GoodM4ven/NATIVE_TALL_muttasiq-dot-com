<?php

declare(strict_types=1);

use App\Services\Quran\QuranReaderDataService;

test('quran search stages run in surah then ayah exact close sarf and jathr order', function () {
    $serviceSource = file_get_contents(app_path('Services/Quran/QuranReaderDataService.php'));

    expect($serviceSource)->not->toBeFalse();

    $surahExactPos = strpos($serviceSource, "'surah_exact'");
    $surahClosePos = strpos($serviceSource, "'surah_close'");
    $ayahExactPos = strpos($serviceSource, "'ayah_exact'");
    $ayahClosePos = strpos($serviceSource, "'ayah_close'");
    $ayahSarfPos = strpos($serviceSource, "'ayah_sarf'");
    $ayahJathrPos = strpos($serviceSource, "'ayah_jathr'");

    expect($surahExactPos)->not->toBeFalse()
        ->and($surahClosePos)->not->toBeFalse()
        ->and($ayahExactPos)->not->toBeFalse()
        ->and($ayahClosePos)->not->toBeFalse()
        ->and($ayahSarfPos)->not->toBeFalse()
        ->and($ayahJathrPos)->not->toBeFalse()
        ->and($surahExactPos)->toBeLessThan($surahClosePos)
        ->and($surahClosePos)->toBeLessThan($ayahExactPos)
        ->and($ayahExactPos)->toBeLessThan($ayahClosePos)
        ->and($ayahClosePos)->toBeLessThan($ayahSarfPos)
        ->and($ayahSarfPos)->toBeLessThan($ayahJathrPos);
});

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
