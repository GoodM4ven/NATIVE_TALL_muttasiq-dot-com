<?php

declare(strict_types=1);
use App\Services\Quran\QuranReaderDataService;
use GoodMaven\Arabicable\Support\Quran\QuranWordCopyText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('keeps sacred divine name tokens out of stem and root search stages', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $sacredTokenResults = $service->searchProgressively('تالله', 20);

    expect($sacredTokenResults)->toBeArray()->not->toBeEmpty()
        ->and(collect($sacredTokenResults)->contains(
            static fn (array $item): bool => (string) ($item['match_strategy'] ?? '') === 'ayah_exact',
        ))->toBeTrue()
        ->and(collect($sacredTokenResults)->contains(
            static fn (array $item): bool => in_array(
                (string) ($item['match_strategy'] ?? ''),
                ['ayah_sarf', 'ayah_jathr'],
                true,
            ),
        ))->toBeFalse();
});

it('matches conjunction-attached aal tokens before falling back to root search', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('آل عمران', 20);
    $firstResult = $results[0] ?? null;

    $aliImranVerseMatch = collect($results)->first(
        static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 3
            && (int) ($item['ayah_number'] ?? 0) === 33
            && ! str_starts_with((string) ($item['match_strategy'] ?? ''), 'surah_'),
    );

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and($firstResult)->toBeArray()
        ->and((string) ($firstResult['match_strategy'] ?? ''))->toBe('surah_exact')
        ->and($aliImranVerseMatch)->toBeArray()
        ->and((string) ($aliImranVerseMatch['match_strategy'] ?? ''))->toBe('ayah_close');
});

it('returns matches for legacy orthography phrases in quran search endpoint', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $query = 'يا بني أقم الصلاة';
    $response = $this->getJson(route('quran-reader-search-index', ['q' => $query], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and(collect($items)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 31
                && (int) ($item['ayah_number'] ?? 0) === 17,
        ))->toBeTrue();

    $legacySpellingResponse = $this->getJson(route('quran-reader-search-index', [
        'q' => 'والله يدعو إلى دار السلام',
    ], false));

    $legacySpellingResponse->assertSuccessful();

    $legacyItems = $legacySpellingResponse->json('items', []);

    expect($legacyItems)->toBeArray()->not->toBeEmpty()
        ->and(collect($legacyItems)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 10
                && (int) ($item['ayah_number'] ?? 0) === 25,
        ))->toBeTrue()
        ->and(collect($legacyItems)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 10
                && (int) ($item['ayah_number'] ?? 0) === 25
                && (string) ($item['match_strategy'] ?? '') === 'ayah_exact',
        ))->toBeTrue();
});

it('matches quran orthography variants when the query drops the ra from al-quran', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $response = $this->getJson(route('quran-reader-search-index', [
        'q' => 'والقآن المجيد',
    ], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and((int) ($items[0]['surah_number'] ?? 0))->toBe(50)
        ->and((int) ($items[0]['ayah_number'] ?? 0))->toBe(1)
        ->and((string) ($items[0]['match_strategy'] ?? ''))->toBe('ayah_exact');
});

it('treats hamza-on-line alif phrase variants as exact quran matches', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $response = $this->getJson(route('quran-reader-search-index', [
        'q' => 'آمن الرسول بما',
    ], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);
    $targetMatch = collect($items)->first(
        static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 2
            && (int) ($item['ayah_number'] ?? 0) === 285,
    );

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and($targetMatch)->toBeArray()
        ->and((string) ($targetMatch['match_strategy'] ?? ''))->toBe('ayah_exact');
});

it('exposes local quran search index payload for client-side instant preview', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $response = $this->getJson(route('quran-reader-search-index', [
        'local' => 1,
    ], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and((int) ($items[0]['id'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($items[0]['surah_number'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($items[0]['ayah_number'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($items[0]['page_number'] ?? 0))->toBeGreaterThan(0)
        ->and((string) ($items[0]['text_searchable_typed'] ?? ''))->not->toBe('');
});

it('normalizes invisible directional chars in quran search queries while preserving exact phrase ranking', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $vocativeQueryWithZwnj = "ي\u{200C}بني أقم الصلاة";
    $vocativeResponse = $this->getJson(route('quran-reader-search-index', [
        'q' => $vocativeQueryWithZwnj,
    ], false));

    $vocativeResponse->assertSuccessful();

    $vocativeItems = $vocativeResponse->json('items', []);

    expect($vocativeItems)->toBeArray()->not->toBeEmpty()
        ->and(count($vocativeItems))->toBeGreaterThan(1)
        ->and(collect($vocativeItems)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 31
                && (int) ($item['ayah_number'] ?? 0) === 17
                && (string) ($item['match_strategy'] ?? '') === 'ayah_exact',
        ))->toBeTrue();

    $invocationQueryWithRlm = "وقال ربكم\u{200F} ادعوني أستجب لكم";
    $invocationResponse = $this->getJson(route('quran-reader-search-index', [
        'q' => $invocationQueryWithRlm,
    ], false));

    $invocationResponse->assertSuccessful();

    $invocationItems = $invocationResponse->json('items', []);

    expect($invocationItems)->toBeArray()->not->toBeEmpty()
        ->and((int) ($invocationItems[0]['surah_number'] ?? 0))->toBe(40)
        ->and((int) ($invocationItems[0]['ayah_number'] ?? 0))->toBe(60)
        ->and((string) ($invocationItems[0]['match_strategy'] ?? ''))->toBe('ayah_exact');
});

it('injects visible basmallah lines under late-page surah headers', function () {
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();
    config()->set('arabicable.quran_fonts.basmalah.preferred', 'quran-common-ligature');

    $page = $service->resolvePage(604);
    $mushafLines = collect($page['mushafLines'] ?? []);
    $basmallahLines = $mushafLines
        ->filter(static fn (array $line): bool => ($line['line_type'] ?? '') === 'basmallah')
        ->values();
    $firstAyahLine = $mushafLines
        ->first(static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah' && ($line['words'] ?? []) !== []);

    expect($basmallahLines)->toHaveCount(3)
        ->and($page['basmallahFontFamily'] ?? null)->toBe('QuranCommon')
        ->and($page['basmallahFontFormat'] ?? null)->toBe('woff2')
        ->and($page['basmallahText'] ?? null)->toBe("\u{FDFD}")
        ->and($page['basmallahFontUrl'] ?? null)->toBe(url('/vendor/arabicable/quran-common.woff2'))
        ->and($firstAyahLine)->toBeArray();

    expect(public_path('vendor/arabicable/quran-common.woff2'))->toBeFile();

    config()->set('arabicable.quran_fonts.basmalah.preferred', 'madina-default');

    $madinaPage = $service->resolvePage(604);

    expect($madinaPage['basmallahFontFamily'] ?? null)->toBe('MadinaQuran')
        ->and($madinaPage['basmallahFontUrl'] ?? null)->toBeNull()
        ->and($madinaPage['basmallahText'] ?? null)->toContain('بِسْمِ');
});

it('prefers published static quran helper font assets over dynamic binary routes', function () {
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();
    config()->set('arabicable.quran_fonts.basmalah.preferred', 'quran-common-ligature');

    $page = $service->resolvePage(604);

    expect($page['surahHeaderFontUrl'] ?? null)->toBe(url('/vendor/arabicable/surah-name-v4.ttf'))
        ->and($page['basmallahFontUrl'] ?? null)->toBe(url('/vendor/arabicable/quran-common.woff2'));
});

it('keeps search destination cue caption-only on immersive mobile and touch contexts', function () {
    $scaleControlsSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/reader-navigation-fit-idle-warmup-and-scale-controls.js'),
    );

    expect($scaleControlsSource)->not->toBeFalse()
        ->and($scaleControlsSource)->toContain('shouldApplySearchDestinationScaleBoost()')
        ->and($scaleControlsSource)->toContain('this.$store.bp.isTouch()')
        ->and($scaleControlsSource)->toContain('return this.$store.bp.isTouch();')
        ->and($scaleControlsSource)->toContain('searchDestinationScaleBoostAmount()')
        ->and($scaleControlsSource)->toContain('searchDestinationTypeScaleBoostAmount()');
});

it('does not repeat surah preludes on continuation pages', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    foreach ([99, 100] as $pageNumber) {
        $page = $service->resolvePage($pageNumber);
        $lines = collect($page['mushafLines'] ?? []);
        $firstAyahLine = $lines->first(static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah');
        $firstWord = collect($firstAyahLine['words'] ?? [])->first();

        expect($firstAyahLine)->toBeArray()
            ->and((int) ($firstWord['ayah_number'] ?? 0))->toBeGreaterThan(1)
            ->and(($lines->first()['line_type'] ?? null))->toBe('ayah')
            ->and($lines->take(2)->pluck('line_type')->all())->not->toContain('surah_name')
            ->and($lines->take(2)->pluck('line_type')->all())->not->toContain('basmallah');
    }
});

it('uses canonical verse text for ayah copy payload and excludes neighboring ayah tokens', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    /** @var object|null $firstAyah */
    $firstAyah = DB::table('quran_verses')
        ->select(['ayah_index', 'text_searchable_typed', 'text_uthmani'])
        ->where('surah_number', 1)
        ->where('ayah_number', 1)
        ->first();

    /** @var object|null $secondAyah */
    $secondAyah = DB::table('quran_verses')
        ->select(['text_searchable_typed', 'text_uthmani'])
        ->where('surah_number', 1)
        ->where('ayah_number', 2)
        ->first();

    if (! is_object($firstAyah) || ! is_object($secondAyah)) {
        $this->markTestSkipped('Required Al-Fatiha verses are unavailable.');
    }

    $normalize = static fn (?string $text): string => (string) preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $text),
    );
    $normalizeForClipboard = static fn (?string $uthmani, ?string $typed): string => $normalize(
        QuranWordCopyText::normalizeToken($uthmani, $typed) ?? '',
    );

    $page = $service->resolvePage(1, (int) $firstAyah->ayah_index);
    $lines = collect($page['mushafLines'] ?? []);
    $firstAyahIndex = (int) $firstAyah->ayah_index;
    $expectedFirstAyahText = $normalizeForClipboard(
        (string) ($firstAyah->text_uthmani ?? ''),
        (string) ($firstAyah->text_searchable_typed ?? ''),
    );
    $secondAyahText = $normalizeForClipboard(
        (string) ($secondAyah->text_uthmani ?? ''),
        (string) ($secondAyah->text_searchable_typed ?? ''),
    );
    $secondAyahFirstToken = collect(preg_split('/\s+/u', $secondAyahText) ?: [])
        ->filter(static fn ($token): bool => is_string($token) && trim($token) !== '')
        ->map(static fn (string $token): string => trim($token))
        ->first();

    $ayahCopyTexts = $lines
        ->flatMap(function (array $line): array {
            $segmentTexts = collect($line['segments'] ?? [])
                ->map(static fn (array $segment): array => [
                    'ayah_index' => (int) ($segment['ayah_index'] ?? 0),
                    'ayah_copy_text' => (string) ($segment['ayah_copy_text'] ?? ''),
                ])
                ->all();

            $wordTexts = collect($line['words'] ?? [])
                ->map(static fn (array $word): array => [
                    'ayah_index' => (int) ($word['ayah_index'] ?? 0),
                    'ayah_copy_text' => (string) ($word['ayah_copy_text'] ?? ''),
                ])
                ->all();

            return [...$segmentTexts, ...$wordTexts];
        })
        ->filter(static fn (array $entry): bool => (int) ($entry['ayah_index'] ?? 0) === $firstAyahIndex)
        ->map(static fn (array $entry): string => $normalize((string) ($entry['ayah_copy_text'] ?? '')))
        ->filter(static fn (string $text): bool => $text !== '')
        ->unique()
        ->values();

    expect($expectedFirstAyahText)->not->toBe('')
        ->and($secondAyahFirstToken)->toBeString()->not->toBe('')
        ->and($ayahCopyTexts)->not->toBeEmpty()
        ->and($ayahCopyTexts)->toContain($expectedFirstAyahText)
        ->and($ayahCopyTexts->join(' '))->not->toContain($secondAyahFirstToken);
});

it('builds meaningful late-page copy payloads for ayahs and words', function () {
    if (! Schema::hasTable('quran_verses') || ! Schema::hasTable('quran_words')) {
        $this->markTestSkipped('Quran verses or words table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    $page = $service->resolvePage(604);
    $lines = collect($page['mushafLines'] ?? []);

    /** @var object|null $targetAyah */
    $targetAyah = DB::table('quran_verses')
        ->select(['ayah_index', 'text_uthmani', 'text_searchable_typed'])
        ->where('surah_number', 112)
        ->where('ayah_number', 1)
        ->first();

    if (! is_object($targetAyah)) {
        $this->markTestSkipped('Required late-page ayah is unavailable.');
    }

    $normalize = static fn (?string $text): string => (string) preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $text),
    );

    $expectedAyahText = $normalize(
        QuranWordCopyText::normalizeToken(
            (string) ($targetAyah->text_uthmani ?? ''),
            (string) ($targetAyah->text_searchable_typed ?? ''),
        ) ?? '',
    );
    $targetAyahIndex = (int) ($targetAyah->ayah_index ?? 0);

    $payloadEntries = $lines->flatMap(function (array $line): array {
        $wordEntries = collect($line['words'] ?? [])
            ->map(static fn (array $word): array => [
                'ayah_index' => (int) ($word['ayah_index'] ?? 0),
                'copy_text' => (string) ($word['copy_text'] ?? ''),
                'ayah_copy_text' => (string) ($word['ayah_copy_text'] ?? ''),
            ])
            ->all();

        $segmentEntries = collect($line['segments'] ?? [])
            ->map(static fn (array $segment): array => [
                'ayah_index' => (int) ($segment['ayah_index'] ?? 0),
                'copy_text' => (string) ($segment['copy_text'] ?? ''),
                'ayah_copy_text' => (string) ($segment['ayah_copy_text'] ?? ''),
            ])
            ->all();

        return [...$wordEntries, ...$segmentEntries];
    });

    $ayahCopyTexts = $payloadEntries
        ->filter(static fn (array $entry): bool => (int) ($entry['ayah_index'] ?? 0) === $targetAyahIndex)
        ->map(static fn (array $entry): string => $normalize((string) ($entry['ayah_copy_text'] ?? '')))
        ->filter(static fn (string $text): bool => $text !== '')
        ->unique()
        ->values();

    $expectedWordTokens = collect(DB::table('quran_words')
        ->select(['token_uthmani', 'token_searchable_typed'])
        ->where('surah_number', 112)
        ->where('ayah_number', 1)
        ->orderBy('word_position')
        ->get())
        ->map(static fn (object $word): string => $normalize(
            QuranWordCopyText::normalizeToken(
                (string) ($word->token_uthmani ?? ''),
                (string) ($word->token_searchable_typed ?? ''),
            ) ?? '',
        ))
        ->filter(static fn (string $token): bool => $token !== '')
        ->values();

    $actualWordTokens = $payloadEntries
        ->filter(static fn (array $entry): bool => (int) ($entry['ayah_index'] ?? 0) === $targetAyahIndex)
        ->map(static fn (array $entry): string => $normalize((string) ($entry['copy_text'] ?? '')))
        ->filter(static fn (string $token): bool => $token !== '')
        ->filter(static fn (string $token): bool => ! preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $token))
        ->unique()
        ->values();

    expect($expectedAyahText)->not->toBe('')
        ->and($ayahCopyTexts)->not->toBeEmpty()
        ->and($ayahCopyTexts)->toContain($expectedAyahText)
        ->and($expectedWordTokens)->not->toBeEmpty()
        ->and($actualWordTokens->join(' '))->not->toMatch('/[\x{06D6}-\x{06ED}\x{0640}]/u');

    foreach ($expectedWordTokens as $expectedToken) {
        expect($actualWordTokens)->toContain((string) $expectedToken);
    }
});

it('does not render basmallah below surah nine header', function () {
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    $pageNumbers = DB::table('quran_mushaf_lines')
        ->distinct()
        ->orderBy('page_number')
        ->pluck('page_number')
        ->map(static fn ($value): int => max(0, (int) $value))
        ->filter(static fn (int $pageNumber): bool => $pageNumber > 0)
        ->values()
        ->all();

    $surahNineBasmallahEntries = [];

    foreach ($pageNumbers as $pageNumber) {
        $page = $service->resolvePage((int) $pageNumber);
        $lines = array_values(array_filter($page['mushafLines'] ?? [], 'is_array'));

        foreach ($lines as $lineIndex => $line) {
            if (
                ($line['line_type'] ?? null) === 'basmallah' &&
                (int) ($line['surah_number'] ?? 0) === 9
            ) {
                $surahNineBasmallahEntries[] = [
                    'page' => (int) $pageNumber,
                    'line' => (int) ($line['line_number'] ?? $lineIndex + 1),
                ];
            }

            if (
                ($line['line_type'] ?? null) !== 'surah_name' ||
                (int) ($line['surah_number'] ?? 0) !== 9
            ) {
                continue;
            }

            $nextLine = $lines[$lineIndex + 1] ?? null;

            if (is_array($nextLine) && ($nextLine['line_type'] ?? null) === 'basmallah') {
                $surahNineBasmallahEntries[] = [
                    'page' => (int) $pageNumber,
                    'line' => (int) ($nextLine['line_number'] ?? $lineIndex + 2),
                ];
            }
        }
    }

    expect(array_slice($surahNineBasmallahEntries, 0, 10))->toBeEmpty();
});

it('keeps qpc late-page surah metadata aligned for headers and copy payload', function () {
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    $page499 = $service->resolvePage(499);
    $firstAyahLineOn499 = collect($page499['mushafLines'] ?? [])->first(
        static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah',
    );
    $firstWordOn499 = collect(is_array($firstAyahLineOn499) ? ($firstAyahLineOn499['words'] ?? []) : [])->first();

    expect($firstWordOn499)->toBeArray()
        ->and((int) ($firstWordOn499['surah_number'] ?? 0))->toBe(45)
        ->and((int) ($firstWordOn499['ayah_number'] ?? 0))->toBe(1);

    $page187 = $service->resolvePage(187);
    $linesOn187 = array_values(array_filter($page187['mushafLines'] ?? [], 'is_array'));

    $surahNineHeaderIndex = null;

    foreach ($linesOn187 as $lineIndex => $line) {
        if (
            ($line['line_type'] ?? null) === 'surah_name' &&
            (int) ($line['surah_number'] ?? 0) === 9
        ) {
            $surahNineHeaderIndex = $lineIndex;

            break;
        }
    }

    expect($surahNineHeaderIndex)->not->toBeNull();

    $lineAfterSurahNineHeader = $linesOn187[(int) $surahNineHeaderIndex + 1] ?? null;

    expect($lineAfterSurahNineHeader)->toBeArray()
        ->and((string) ($lineAfterSurahNineHeader['line_type'] ?? ''))->not->toBe('basmallah');

    $firstAyahAfterSurahNineHeader = collect(array_slice($linesOn187, (int) $surahNineHeaderIndex + 1))->first(
        static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah',
    );
    $firstWordAfterSurahNineHeader = collect(
        is_array($firstAyahAfterSurahNineHeader) ? ($firstAyahAfterSurahNineHeader['words'] ?? []) : [],
    )->first();

    expect($firstWordAfterSurahNineHeader)->toBeArray()
        ->and((int) ($firstWordAfterSurahNineHeader['surah_number'] ?? 0))->toBe(9)
        ->and((int) ($firstWordAfterSurahNineHeader['ayah_number'] ?? 0))->toBe(1);
});

it('builds canonical copy payloads for every ayah in the quran dataset', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    $normalize = static fn (?string $text): string => (string) preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $text),
    );

    $expectedAyahTextByIndex = [];

    foreach (DB::table('quran_verses')
        ->select(['ayah_index', 'text_uthmani', 'text_searchable_typed'])
        ->orderBy('ayah_index')
        ->get() as $verseRow) {
        $ayahIndex = (int) ($verseRow->ayah_index ?? 0);

        if ($ayahIndex < 1) {
            continue;
        }

        $normalizedAyahText = $normalize(
            QuranWordCopyText::normalizeToken(
                (string) ($verseRow->text_uthmani ?? ''),
                (string) ($verseRow->text_searchable_typed ?? ''),
            ) ?? '',
        );

        if ($normalizedAyahText === '') {
            continue;
        }

        $expectedAyahTextByIndex[$ayahIndex] = $normalizedAyahText;
    }

    $pageNumbers = DB::table('quran_mushaf_lines')
        ->distinct()
        ->orderBy('page_number')
        ->pluck('page_number')
        ->map(static fn ($value): int => max(0, (int) $value))
        ->filter(static fn (int $pageNumber): bool => $pageNumber > 0)
        ->values()
        ->all();

    if ($expectedAyahTextByIndex === [] || $pageNumbers === []) {
        $this->markTestSkipped('Canonical Quran ayah rows are unavailable.');
    }

    $presentationFormPattern = '/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
    $actualAyahTextsByIndex = [];
    $invalidWordCopyEntries = [];
    $invalidAyahCopyEntries = [];
    $invalidAyahMetadataEntries = [];

    foreach ($pageNumbers as $pageNumber) {
        $page = $service->resolvePage((int) $pageNumber);
        $lines = collect($page['mushafLines'] ?? []);
        $payloadEntries = $lines->flatMap(static function (array $line): array {
            $segmentEntries = collect($line['segments'] ?? [])
                ->map(static fn (array $segment): array => [
                    'ayah_index' => (int) ($segment['ayah_index'] ?? 0),
                    'surah_number' => (int) ($segment['surah_number'] ?? 0),
                    'ayah_number' => (int) ($segment['ayah_number'] ?? 0),
                    'copy_text' => (string) ($segment['copy_text'] ?? ''),
                    'ayah_copy_text' => (string) ($segment['ayah_copy_text'] ?? ''),
                ])
                ->all();

            $wordEntries = collect($line['words'] ?? [])
                ->map(static fn (array $word): array => [
                    'ayah_index' => (int) ($word['ayah_index'] ?? 0),
                    'surah_number' => (int) ($word['surah_number'] ?? 0),
                    'ayah_number' => (int) ($word['ayah_number'] ?? 0),
                    'copy_text' => (string) ($word['copy_text'] ?? ''),
                    'ayah_copy_text' => (string) ($word['ayah_copy_text'] ?? ''),
                ])
                ->all();

            return [...$segmentEntries, ...$wordEntries];
        });

        foreach ($payloadEntries as $entry) {
            $ayahIndex = (int) ($entry['ayah_index'] ?? 0);

            if ($ayahIndex < 1) {
                continue;
            }

            $copyText = $normalize((string) ($entry['copy_text'] ?? ''));
            $ayahCopyText = $normalize((string) ($entry['ayah_copy_text'] ?? ''));
            $surahNumber = (int) ($entry['surah_number'] ?? 0);
            $ayahNumber = (int) ($entry['ayah_number'] ?? 0);

            if ($surahNumber < 1 || $ayahNumber < 1) {
                $invalidAyahMetadataEntries[] = [
                    'page' => (int) $pageNumber,
                    'ayah_index' => $ayahIndex,
                    'surah_number' => $surahNumber,
                    'ayah_number' => $ayahNumber,
                ];
            }

            if ($copyText !== '' && preg_match($presentationFormPattern, $copyText)) {
                $invalidWordCopyEntries[] = [
                    'page' => (int) $pageNumber,
                    'ayah_index' => $ayahIndex,
                    'copy_text' => $copyText,
                ];
            }

            if ($ayahCopyText !== '' && preg_match($presentationFormPattern, $ayahCopyText)) {
                $invalidAyahCopyEntries[] = [
                    'page' => (int) $pageNumber,
                    'ayah_index' => $ayahIndex,
                    'ayah_copy_text' => $ayahCopyText,
                ];
            }

            if ($ayahCopyText !== '') {
                $actualAyahTextsByIndex[$ayahIndex] ??= [];
                $actualAyahTextsByIndex[$ayahIndex][$ayahCopyText] = true;
            }
        }
    }

    $expectedAyahIndexes = array_keys($expectedAyahTextByIndex);
    $actualAyahIndexes = array_keys($actualAyahTextsByIndex);
    sort($expectedAyahIndexes);
    sort($actualAyahIndexes);

    $missingAyahIndexes = array_values(array_diff($expectedAyahIndexes, $actualAyahIndexes));
    $unexpectedAyahIndexes = array_values(array_diff($actualAyahIndexes, $expectedAyahIndexes));
    $mismatchedAyahs = [];

    foreach ($expectedAyahTextByIndex as $ayahIndex => $expectedAyahText) {
        $actualAyahTexts = array_keys($actualAyahTextsByIndex[$ayahIndex] ?? []);

        if ($actualAyahTexts === []) {
            continue;
        }

        foreach ($actualAyahTexts as $actualAyahText) {
            if ($actualAyahText === $expectedAyahText) {
                continue;
            }

            $mismatchedAyahs[] = [
                'ayah_index' => (int) $ayahIndex,
                'expected' => $expectedAyahText,
                'actual' => $actualAyahText,
            ];

            break;
        }
    }

    expect(array_slice($invalidWordCopyEntries, 0, 10))->toBeEmpty()
        ->and(array_slice($invalidAyahCopyEntries, 0, 10))->toBeEmpty()
        ->and(array_slice($invalidAyahMetadataEntries, 0, 10))->toBeEmpty()
        ->and(array_slice($missingAyahIndexes, 0, 20))->toBeEmpty()
        ->and(array_slice($unexpectedAyahIndexes, 0, 20))->toBeEmpty()
        ->and(array_slice($mismatchedAyahs, 0, 10))->toBeEmpty();
});
