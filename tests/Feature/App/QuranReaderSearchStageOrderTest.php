<?php

declare(strict_types=1);

use App\Services\Quran\QuranReaderDataService;
use Illuminate\Support\Facades\Schema;

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

test('quran search returns corrected exact matches for hamza and orthography variants', function (
    string $query,
    int $surahNumber,
    int $ayahNumber,
) {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively($query, 24);
    $targetMatch = collect($results)->first(
        static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === $surahNumber
            && (int) ($item['ayah_number'] ?? 0) === $ayahNumber,
    );

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and((string) ($results[0]['match_strategy'] ?? ''))->toBe('ayah_exact')
        ->and($targetMatch)->toBeArray()
        ->and((string) ($targetMatch['match_strategy'] ?? ''))->toBe('ayah_exact');
})->with([
    'double hamza' => ['أأمنتم من في السماء', 67, 16],
    'quranic oath' => ['وَالْقُرْآنِ الْحَكِيمِ', 36, 2],
    'double hamza question' => ['سَوَاءٌ عَلَيْهِمْ أَأَنذَرْتَهُمْ أَمْ لَمْ تُنذِرْهُمْ', 2, 6],
    'musa opening' => ['وَهَلْ أَتَاكَ حَدِيثُ مُوسَىٰ', 20, 9],
    'legacy typo correction' => ['لألا يعلم أهل الكتاب', 57, 29],
    'clear exact verse' => ['ذَٰلِكَ الْكِتَابُ', 2, 2],
    'maryam verse' => ['ذَٰلِكَ عِيسَى ابْنُ مَرْيَمَ', 19, 34],
    'dua verse' => ['رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً', 2, 201],
]);

test('quran search treats يس and ياسين as the same surah-name match', function (string $query) {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively($query, 24);

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and((int) ($results[0]['surah_number'] ?? 0))->toBe(36)
        ->and((string) ($results[0]['match_strategy'] ?? ''))->toBe('surah_exact');
})->with([
    'yaseen alias' => ['ياسين'],
    'ya sin alias' => ['يس'],
]);

test('quran search splits multi-ayah queries into separate exact results', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively(
        'قُلْ هُوَ اللَّهُ أَحَدٌ ۝ اللَّهُ الصَّمَدُ ۝ لَمْ يَلِدْ وَلَمْ يُولَدْ ۝ وَلَمْ يَكُن لَّهُ كُفُوًا أَحَدٌ',
        24,
    );

    expect($results)->toHaveCount(4)
        ->and(collect($results)->pluck('match_strategy')->all())->toBe([
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
        ])
        ->and(collect($results)->map(
            static fn (array $item): array => [
                (int) ($item['surah_number'] ?? 0),
                (int) ($item['ayah_number'] ?? 0),
            ],
        )->all())->toBe([
            [112, 1],
            [112, 2],
            [112, 3],
            [112, 4],
        ]);
});
