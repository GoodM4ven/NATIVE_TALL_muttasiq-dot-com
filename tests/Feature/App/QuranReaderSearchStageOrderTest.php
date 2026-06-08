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
    'raoof phrase' => ['رؤوف بالعباد', 2, 207],
    'musa opening' => ['وَهَلْ أَتَاكَ حَدِيثُ مُوسَىٰ', 20, 9],
    'legacy typo correction' => ['لألا يعلم أهل الكتاب', 57, 29],
    'clear exact verse' => ['ذَٰلِكَ الْكِتَابُ', 2, 2],
    'maryam verse' => ['ذَٰلِكَ عِيسَى ابْنُ مَرْيَمَ', 19, 34],
    'dua verse' => ['رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً', 2, 201],
]);

test('quran search treats رحمة and رحمت as interchangeable spellings', function (string $query) {
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
        ->and(collect($results)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 44
                && (int) ($item['ayah_number'] ?? 0) === 6,
        ))->toBeTrue()
        ->and(collect($results)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 2
                && (int) ($item['ayah_number'] ?? 0) === 218,
        ))->toBeTrue();
})->with([
    'rahma spelling' => ['رحمة'],
    'rahmat spelling' => ['رحمت'],
]);

test('quran search continues into jathr after exact matches when room remains', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('أَنبِئُونِي بِأَسْمَاءِ هَٰؤُلَاءِ', 24);

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and(collect($results)->contains(
            static fn (array $item): bool => (string) ($item['match_strategy'] ?? '') === 'ayah_jathr',
        ))->toBeTrue();
});

test('quran search blocks only lone exact قال and كان queries while keeping variants searchable', function (string $query, bool $shouldBeBlocked) {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively($query, 24);

    if ($shouldBeBlocked) {
        expect($results)->toBeArray()->toBeEmpty();

        return;
    }

    expect($results)->toBeArray()->not->toBeEmpty();
})->with([
    'قال' => ['قال', true],
    'كان' => ['كان', true],
    'وقال' => ['وقال', false],
    'وكان' => ['وكان', false],
]);

test('quran search caps and randomizes semantic stage batches', function (): void {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    try {
        mt_srand(1111);
        $firstResults = $service->searchByStages('علم', ['ayah_sarf'], 100);
        $firstOrder = collect($firstResults)->pluck('ayah_index')->all();

        mt_srand(2222);
        $secondResults = $service->searchByStages('علم', ['ayah_sarf'], 100);
        $secondOrder = collect($secondResults)->pluck('ayah_index')->all();

        expect($firstResults)->toHaveCount(50)
            ->and($secondResults)->toHaveCount(50)
            ->and($firstOrder)->not->toBe($secondOrder);
    } finally {
        mt_srand();
    }
});

test('quran search keeps exact close sarf and jathr stage buckets disjoint', function (string $query): void {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively($query, 60);
    $idsByStrategy = collect($results)
        ->groupBy(static fn (array $item): string => (string) ($item['match_strategy'] ?? ''))
        ->map(static fn ($group): array => $group
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all());

    $exactIds = $idsByStrategy->get('ayah_exact', []);
    $closeIds = $idsByStrategy->get('ayah_close', []);
    $sarfIds = $idsByStrategy->get('ayah_sarf', []);
    $jathrIds = $idsByStrategy->get('ayah_jathr', []);

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and($exactIds)->toHaveCount(1)
        ->and(count($closeIds))->toBeLessThanOrEqual(50)
        ->and(count($sarfIds))->toBeLessThanOrEqual(50)
        ->and(count($jathrIds))->toBeLessThanOrEqual(50)
        ->and(array_intersect($exactIds, $closeIds))->toBe([])
        ->and(array_intersect($exactIds, $sarfIds))->toBe([])
        ->and(array_intersect($exactIds, $jathrIds))->toBe([])
        ->and(array_intersect($closeIds, $sarfIds))->toBe([])
        ->and(array_intersect($closeIds, $jathrIds))->toBe([])
        ->and(array_intersect($sarfIds, $jathrIds))->toBe([]);
})->with([
    'short phrase' => ['وقال ربكم ادعوني'],
    'long phrase' => ['وقال ربكم ادعوني أستجب لكم'],
]);

test('quran search keeps vocative phrases out of sarf and jathr when the vocative token is explicit', function (): void {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('قيل يا أرض ابلعي ماءك', 24);
    $targetMatch = collect($results)->first(
        static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 11
            && (int) ($item['ayah_number'] ?? 0) === 44,
    );

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and($targetMatch)->toBeArray()
        ->and(in_array((string) ($targetMatch['match_strategy'] ?? ''), ['ayah_exact', 'ayah_close'], true))->toBeTrue()
        ->and(collect($results)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 11
                && (int) ($item['ayah_number'] ?? 0) === 44
                && in_array((string) ($item['match_strategy'] ?? ''), ['ayah_sarf', 'ayah_jathr'], true),
        ))->toBeFalse();
});

test('quran search distributes semantic matches across the meaningful words in a multi-word query', function (): void {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    try {
        mt_srand(1111);
        $sarfResults = $service->searchByStages('وقال ربكم ادعوني أستجب لكم', ['ayah_sarf'], 100);

        mt_srand(1111);
        $jathrResults = $service->searchByStages('وقال ربكم ادعوني أستجب لكم', ['ayah_jathr'], 100);

        $sarfSurahCount = collect($sarfResults)->pluck('surah_number')->unique()->count();
        $sarfPageCount = collect($sarfResults)->pluck('page_number')->unique()->count();
        $jathrSurahCount = collect($jathrResults)->pluck('surah_number')->unique()->count();
        $jathrPageCount = collect($jathrResults)->pluck('page_number')->unique()->count();

        expect($sarfResults)->toHaveCount(50)
            ->and($jathrResults)->toHaveCount(50)
            ->and($sarfSurahCount)->toBeGreaterThan(15)
            ->and($sarfPageCount)->toBeGreaterThan(30)
            ->and($jathrSurahCount)->toBeGreaterThan(15)
            ->and($jathrPageCount)->toBeGreaterThan(30)
            ->and(collect($sarfResults)->pluck('surah_number')->take(10)->all())->not->toBe(
                collect($jathrResults)->pluck('surah_number')->take(10)->all(),
            );
    } finally {
        mt_srand();
    }
});

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

test('quran search returns opening ayah matches for حم', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('حم', 24);

    expect($results)->toHaveCount(7)
        ->and(collect($results)->pluck('match_strategy')->all())->toBe([
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
        ])
        ->and(collect($results)->pluck('surah_number')->all())->toBe([
            40,
            41,
            42,
            43,
            44,
            45,
            46,
        ])
        ->and(collect($results)->pluck('ayah_number')->all())->toBe([
            1,
            1,
            1,
            1,
            1,
            1,
            1,
        ]);
});

test('quran search returns opening ayah matches for حم when split with other opening queries', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('يس ۝ طه ۝ حم', 24);

    expect($results)->toHaveCount(9)
        ->and(collect($results)->pluck('match_strategy')->all())->toBe([
            'surah_exact',
            'surah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
            'ayah_exact',
        ])
        ->and(collect($results)->pluck('surah_number')->all())->toBe([
            36,
            20,
            40,
            41,
            42,
            43,
            44,
            45,
            46,
        ]);
});

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
