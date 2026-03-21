<?php

declare(strict_types=1);

namespace App\Services\Quran;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuranReaderDataService
{
    public function isReady(): bool
    {
        $hasVersesTable = Schema::hasTable('quran_verses');
        $hasWordsTable = Schema::hasTable('quran_words');
        $hasMushafLinesTable = Schema::hasTable('quran_mushaf_lines');
        $hasTypedSearchColumn = $hasVersesTable && Schema::hasColumn('quran_verses', 'text_searchable_typed');

        return $hasVersesTable && $hasWordsTable && $hasMushafLinesTable && $hasTypedSearchColumn;
    }

    public function maxPage(): int
    {
        if (! $this->isReady()) {
            return 0;
        }

        return (int) DB::table('quran_mushaf_lines')->max('page_number');
    }

    /**
     * @return array{
     *     ready: bool,
     *     pageNumber: int,
     *     maxPage: int,
     *     activeAyahIndex: int,
     *     mushafLines: array<int, array{
     *         line_number: int,
     *         line_type: string,
     *         is_centered: bool,
     *         surah_number: int|null,
     *         segments: array<int, array{
     *             verse_id: int,
     *             ayah_index: int,
     *             surah_number: int,
     *             ayah_number: int,
     *             text: string,
     *             ends_ayah: bool
     *         }>,
     *         words: array<int, array{
     *             verse_id: int,
     *             word_index: int,
     *             ayah_index: int,
     *             surah_number: int,
     *             ayah_number: int,
     *             text: string,
     *             is_glyph: bool,
     *             ends_ayah: bool
     *         }>,
     *         text: string
     *     }>,
     *     qpcPageFontFamily: string|null,
     *     qpcPageFontUrl: string|null,
     *     qpcPageFontFormat: string|null,
     *     useCenteredAyahLayout: bool
     * }
     */
    public function resolvePage(int $pageNumber, int $activeAyahIndex = 0): array
    {
        if (! $this->isReady()) {
            return [
                'ready' => false,
                'pageNumber' => 1,
                'maxPage' => 0,
                'activeAyahIndex' => 0,
                'mushafLines' => [],
                'qpcPageFontFamily' => null,
                'qpcPageFontUrl' => null,
                'qpcPageFontFormat' => null,
                'useCenteredAyahLayout' => true,
            ];
        }

        $maxPage = $this->maxPage();
        $normalizedPage = $maxPage > 0 ? max(1, min($pageNumber, $maxPage)) : 1;
        $cacheKey = 'quran-reader-page-v2:'.$normalizedPage;

        /**
         * @var array{
         *     ready: bool,
         *     pageNumber: int,
         *     maxPage: int,
         *     mushafLines: array<int, array{
         *         line_number: int,
         *         line_type: string,
         *         is_centered: bool,
         *         surah_number: int|null,
         *         segments: array<int, array{
         *             verse_id: int,
         *             ayah_index: int,
         *             surah_number: int,
         *             ayah_number: int,
         *             text: string,
         *             ends_ayah: bool
         *         }>,
         *         words: array<int, array{
         *             verse_id: int,
         *             word_index: int,
         *             ayah_index: int,
         *             surah_number: int,
         *             ayah_number: int,
         *             text: string,
         *             is_glyph: bool,
         *             ends_ayah: bool
         *         }>,
         *         text: string
         *     }>,
         *     qpcPageFontFamily: string|null,
         *     qpcPageFontUrl: string|null,
         *     qpcPageFontFormat: string|null,
         *     useCenteredAyahLayout: bool
         * } $staticPayload
         */
        $staticPayload = Cache::remember($cacheKey, now()->addDays(30), function () use ($maxPage, $normalizedPage): array {
            $qpcPageFont = $this->resolveQpcPageFont($normalizedPage);
            $mushafLines = $this->buildPageLines($normalizedPage);
            $useCenteredAyahLayout = $this->shouldUseCenteredAyahLayout($normalizedPage, $mushafLines);

            return [
                'ready' => true,
                'pageNumber' => $normalizedPage,
                'maxPage' => $maxPage,
                'mushafLines' => $mushafLines,
                'qpcPageFontFamily' => $qpcPageFont['family'] ?? null,
                'qpcPageFontUrl' => $qpcPageFont['url'] ?? null,
                'qpcPageFontFormat' => $qpcPageFont['format'] ?? null,
                'useCenteredAyahLayout' => $useCenteredAyahLayout,
            ];
        });

        $effectiveAyahIndex = $activeAyahIndex;

        if ($effectiveAyahIndex < 1) {
            $effectiveAyahIndex = $this->firstAyahIndexInPage($staticPayload['mushafLines']) ?? 0;
        }

        return [
            ...$staticPayload,
            'activeAyahIndex' => $effectiveAyahIndex,
        ];
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string
     * }>
     */
    public function searchIndex(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        /** @var array<int, array{
         *     id: int,
         *     ayah_index: int,
         *     surah_number: int,
         *     ayah_number: int,
         *     page_number: int,
         *     text_uthmani: string,
         *     text_searchable_typed: string
         * }> $index
         */
        $index = Cache::remember('quran-reader-search-index-v1', now()->addDays(30), static function (): array {
            return DB::table('quran_verses')
                ->select([
                    'id',
                    'ayah_index',
                    'surah_number',
                    'ayah_number',
                    'mushaf_page',
                    'text_uthmani',
                    'text_searchable_typed',
                ])
                ->whereNotNull('text_searchable_typed')
                ->orderBy('ayah_index')
                ->get()
                ->map(static function (object $row): array {
                    return [
                        'id' => (int) $row->id,
                        'ayah_index' => (int) $row->ayah_index,
                        'surah_number' => (int) $row->surah_number,
                        'ayah_number' => (int) $row->ayah_number,
                        'page_number' => (int) ($row->mushaf_page ?? 0),
                        'text_uthmani' => trim((string) $row->text_uthmani),
                        'text_searchable_typed' => trim((string) $row->text_searchable_typed),
                    ];
                })
                ->values()
                ->all();
        });

        return $index;
    }

    /**
     * @return array<int, string>
     */
    public function surahNames(): array
    {
        $names = [];

        for ($surahNumber = 1; $surahNumber <= 114; $surahNumber++) {
            $arabicName = $this->resolveSurahArabicName($surahNumber);

            if ($arabicName === null || $arabicName === '') {
                $names[$surahNumber] = (string) $surahNumber;

                continue;
            }

            $names[$surahNumber] = $arabicName;
        }

        return $names;
    }

    /**
     * @return array<int, array{
     *     line_number: int,
     *     line_type: string,
     *     is_centered: bool,
     *     surah_number: int|null,
     *     segments: array<int, array{
     *         verse_id: int,
     *         ayah_index: int,
     *         surah_number: int,
     *         ayah_number: int,
     *         text: string,
     *         ends_ayah: bool
     *     }>,
     *     words: array<int, array{
     *         verse_id: int,
     *         word_index: int,
     *         ayah_index: int,
     *         surah_number: int,
     *         ayah_number: int,
     *         text: string,
     *         is_glyph: bool,
     *         ends_ayah: bool
     *     }>,
     *     text: string
     * }>
     */
    private function buildPageLines(int $pageNumber): array
    {
        $lineRows = DB::table('quran_mushaf_lines')
            ->select([
                'line_number',
                'line_type',
                'is_centered',
                'first_word_index',
                'last_word_index',
                'surah_number',
            ])
            ->where('page_number', $pageNumber)
            ->orderBy('line_number')
            ->get()
            ->all();

        if ($lineRows === []) {
            return [];
        }

        $wordRangeStart = null;
        $wordRangeEnd = null;

        foreach ($lineRows as $lineRow) {
            if ($lineRow->first_word_index === null || $lineRow->last_word_index === null) {
                continue;
            }

            $lineStart = (int) $lineRow->first_word_index;
            $lineEnd = (int) $lineRow->last_word_index;

            $wordRangeStart = $wordRangeStart === null ? $lineStart : min($wordRangeStart, $lineStart);
            $wordRangeEnd = $wordRangeEnd === null ? $lineEnd : max($wordRangeEnd, $lineEnd);
        }

        $displayWordsByIndex = [];

        if ($wordRangeStart !== null && $wordRangeEnd !== null) {
            $displayWordsByIndex = $this->loadQpcDisplayWordsByIndex($wordRangeStart, $wordRangeEnd + 1);
        }

        if ($displayWordsByIndex === [] && $wordRangeStart !== null && $wordRangeEnd !== null) {
            $fallbackWords = DB::table('quran_words')
                ->select([
                    'global_word_index',
                    'surah_number',
                    'ayah_number',
                    'token_uthmani',
                ])
                ->whereBetween('global_word_index', [$wordRangeStart, $wordRangeEnd + 1])
                ->orderBy('global_word_index')
                ->get();

            foreach ($fallbackWords as $word) {
                $displayWordsByIndex[(int) $word->global_word_index] = [
                    'global_word_index' => (int) $word->global_word_index,
                    'surah_number' => (int) $word->surah_number,
                    'ayah_number' => (int) $word->ayah_number,
                    'text' => trim((string) $word->token_uthmani),
                    'is_glyph' => false,
                ];
            }
        }

        $verseMetaByPair = [];
        $surahNumbers = [];
        $ayahNumbers = [];

        foreach ($displayWordsByIndex as $word) {
            $surahNumbers[(int) $word['surah_number']] = true;
            $ayahNumbers[(int) $word['ayah_number']] = true;
        }

        unset($surahNumbers[0], $ayahNumbers[0]);

        if ($surahNumbers !== [] && $ayahNumbers !== []) {
            $verseRows = DB::table('quran_verses')
                ->select(['id', 'ayah_index', 'surah_number', 'ayah_number'])
                ->whereIn('surah_number', array_keys($surahNumbers))
                ->whereIn('ayah_number', array_keys($ayahNumbers))
                ->get();

            foreach ($verseRows as $verseRow) {
                $pairKey = (int) $verseRow->surah_number.':'.(int) $verseRow->ayah_number;
                $verseMetaByPair[$pairKey] = [
                    'id' => (int) $verseRow->id,
                    'ayah_index' => (int) $verseRow->ayah_index,
                    'surah_number' => (int) $verseRow->surah_number,
                    'ayah_number' => (int) $verseRow->ayah_number,
                ];
            }
        }

        $lines = [];

        foreach ($lineRows as $lineRow) {
            $lineType = (string) $lineRow->line_type;
            $lineNumber = (int) $lineRow->line_number;
            $segments = [];
            $words = [];
            $lineText = '';
            $firstWordIndex = $lineRow->first_word_index !== null ? (int) $lineRow->first_word_index : null;
            $lastWordIndex = $lineRow->last_word_index !== null ? (int) $lineRow->last_word_index : null;

            if ($firstWordIndex !== null && $lastWordIndex !== null) {
                $currentPairKey = null;
                $currentSegmentTokens = [];
                $currentSegmentMeta = null;
                $currentSegmentJoiner = '';
                $currentSegmentEndsAyah = false;

                for ($wordIndex = $firstWordIndex; $wordIndex <= $lastWordIndex; $wordIndex++) {
                    $word = $displayWordsByIndex[$wordIndex] ?? null;

                    if (! is_array($word)) {
                        continue;
                    }

                    $wordSurahNumber = (int) $word['surah_number'];
                    $wordAyahNumber = (int) $word['ayah_number'];
                    $wordText = (string) $word['text'];
                    $pairKey = $wordSurahNumber.':'.$wordAyahNumber;
                    $verseMeta = $verseMetaByPair[$pairKey] ?? null;
                    $nextWord = $displayWordsByIndex[$wordIndex + 1] ?? null;
                    $wordEndsAyah = ! is_array($nextWord)
                        || (int) $nextWord['surah_number'] !== $wordSurahNumber
                        || (int) $nextWord['ayah_number'] !== $wordAyahNumber;

                    $words[] = [
                        'verse_id' => (int) ($verseMeta['id'] ?? 0),
                        'word_index' => (int) $word['global_word_index'],
                        'ayah_index' => (int) ($verseMeta['ayah_index'] ?? 0),
                        'surah_number' => $wordSurahNumber,
                        'ayah_number' => $wordAyahNumber,
                        'text' => $wordText,
                        'is_glyph' => (bool) $word['is_glyph'],
                        'ends_ayah' => $wordEndsAyah,
                    ];

                    if ($currentSegmentMeta !== null && $currentPairKey !== null && $currentPairKey !== $pairKey) {
                        $segments[] = [
                            'verse_id' => (int) $currentSegmentMeta['verse_id'],
                            'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                            'surah_number' => (int) $currentSegmentMeta['surah_number'],
                            'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                            'text' => trim(implode($currentSegmentJoiner, $currentSegmentTokens)),
                            'ends_ayah' => $currentSegmentEndsAyah,
                        ];

                        $currentSegmentTokens = [];
                        $currentSegmentMeta = null;
                        $currentSegmentEndsAyah = false;
                    }

                    if ($currentSegmentMeta === null) {
                        $currentSegmentMeta = [
                            'verse_id' => (int) ($verseMeta['id'] ?? 0),
                            'ayah_index' => (int) ($verseMeta['ayah_index'] ?? 0),
                            'surah_number' => $wordSurahNumber,
                            'ayah_number' => $wordAyahNumber,
                        ];
                        $currentSegmentJoiner = ((bool) $word['is_glyph']) ? '' : ' ';
                    }

                    $currentPairKey = $pairKey;
                    $currentSegmentTokens[] = $wordText;
                    $currentSegmentEndsAyah = $wordEndsAyah;
                }

                if ($currentSegmentMeta !== null && $currentSegmentTokens !== []) {
                    $segments[] = [
                        'verse_id' => (int) $currentSegmentMeta['verse_id'],
                        'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                        'surah_number' => (int) $currentSegmentMeta['surah_number'],
                        'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                        'text' => trim(implode($currentSegmentJoiner, $currentSegmentTokens)),
                        'ends_ayah' => $currentSegmentEndsAyah,
                    ];
                }

                $lineText = trim(implode(' ', array_map(static fn (array $segment): string => $segment['text'], $segments)));
            }

            if ($lineText === '' && $lineType === 'basmallah') {
                $lineText = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';
            }

            if ($lineText === '' && $lineType === 'surah_name') {
                $surahNumber = $lineRow->surah_number !== null ? (int) $lineRow->surah_number : null;
                $lineText = $this->formatSurahTitle($surahNumber ?? 0);
            }

            $lines[] = [
                'line_number' => $lineNumber,
                'line_type' => $lineType,
                'is_centered' => ((int) $lineRow->is_centered) === 1,
                'surah_number' => $lineRow->surah_number !== null ? (int) $lineRow->surah_number : null,
                'segments' => $segments,
                'words' => $words,
                'text' => $lineText,
            ];
        }

        return $this->applyOpeningSpreadCorrections($lines);
    }

    /**
     * @param  array<int, array{
     *     line_number: int,
     *     line_type: string,
     *     is_centered: bool,
     *     surah_number: int|null,
     *     segments: array<int, array{
     *         verse_id: int,
     *         ayah_index: int,
     *         surah_number: int,
     *         ayah_number: int,
     *         text: string,
     *         ends_ayah: bool
     *     }>,
     *     words: array<int, array{
     *         verse_id: int,
     *         word_index: int,
     *         ayah_index: int,
     *         surah_number: int,
     *         ayah_number: int,
     *         text: string,
     *         is_glyph: bool,
     *         ends_ayah: bool
     *     }>,
     *     text: string
     * }>  $lines
     * @return array<int, array{
     *     line_number: int,
     *     line_type: string,
     *     is_centered: bool,
     *     surah_number: int|null,
     *     segments: array<int, array{
     *         verse_id: int,
     *         ayah_index: int,
     *         surah_number: int,
     *         ayah_number: int,
     *         text: string,
     *         ends_ayah: bool
     *     }>,
     *     words: array<int, array{
     *         verse_id: int,
     *         word_index: int,
     *         ayah_index: int,
     *         surah_number: int,
     *         ayah_number: int,
     *         text: string,
     *         is_glyph: bool,
     *         ends_ayah: bool
     *     }>,
     *     text: string
     * }>
     */
    private function applyOpeningSpreadCorrections(array $lines): array
    {
        return array_values(array_filter(
            $lines,
            static fn (array $line): bool => $line['line_type'] !== 'ayah' || $line['segments'] !== [],
        ));
    }

    /**
     * @param  array<int, array{
     *     segments: array<int, array{ayah_index: int}>
     * }>  $mushafLines
     */
    private function firstAyahIndexInPage(array $mushafLines): ?int
    {
        foreach ($mushafLines as $line) {
            $segments = $line['segments'];

            if ($segments === []) {
                continue;
            }

            return (int) ($segments[0]['ayah_index'] ?? 0);
        }

        return null;
    }

    /**
     * @param  array<int, array{line_type: string}>  $mushafLines
     */
    private function shouldUseCenteredAyahLayout(int $pageNumber, array $mushafLines): bool
    {
        if ($pageNumber <= 2) {
            return true;
        }

        $surahHeaderCount = count(array_filter(
            $mushafLines,
            static fn (array $line): bool => $line['line_type'] === 'surah_name',
        ));

        return $surahHeaderCount >= 2 && $pageNumber >= 587;
    }

    private function formatSurahTitle(int $surahNumber): string
    {
        if ($surahNumber < 1) {
            return 'سورة';
        }

        $arabicName = $this->resolveSurahArabicName($surahNumber);

        if ($arabicName === null || $arabicName === '') {
            return 'سورة ('.$surahNumber.')';
        }

        return 'سورة '.$arabicName.' ('.$surahNumber.')';
    }

    private function resolveSurahArabicName(int $surahNumber): ?string
    {
        $surahNames = $this->loadSurahArabicNames();

        return $surahNames[$surahNumber] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function loadSurahArabicNames(): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        $candidates = [
            base_path('resources/raw-data/quran/layouts/quran-metadata-surah-name.json'),
            dirname(base_path()).'/resources/raw-data/quran/layouts/quran-metadata-surah-name.json',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/layouts/quran-metadata-surah-name.json'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_file($candidate)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($candidate), true);

            if (! is_array($decoded)) {
                continue;
            }

            $surahNames = [];

            foreach ($decoded as $key => $value) {
                $surahNumber = is_numeric($key) ? (int) $key : 0;
                $nameArabic = is_array($value) ? trim((string) ($value['name_arabic'] ?? '')) : '';

                if ($surahNumber < 1 || $nameArabic === '') {
                    continue;
                }

                $surahNames[$surahNumber] = $nameArabic;
            }

            if ($surahNames !== []) {
                $cached = $surahNames;

                return $cached;
            }
        }

        $cached = [];

        return $cached;
    }

    /**
     * @return array{family: string, url: string, format: string}|null
     */
    private function resolveQpcPageFont(int $pageNumber): ?array
    {
        if ($pageNumber < 1 || $pageNumber > 604) {
            return null;
        }

        $candidates = [
            [
                'path' => base_path('resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.woff2'),
                'format' => 'woff2',
            ],
            [
                'path' => dirname(base_path()).'/resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.woff2',
                'format' => 'woff2',
            ],
            [
                'path' => base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.woff2'),
                'format' => 'woff2',
            ],
            [
                'path' => base_path('resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.ttf'),
                'format' => 'truetype',
            ],
            [
                'path' => dirname(base_path()).'/resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.ttf',
                'format' => 'truetype',
            ],
            [
                'path' => base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/qpc-v2/p'.$pageNumber.'.ttf'),
                'format' => 'truetype',
            ],
        ];

        foreach ($candidates as $candidate) {
            $fontPath = $candidate['path'];
            $fontFormat = $candidate['format'];

            if (! is_file($fontPath)) {
                continue;
            }

            return [
                'family' => 'QpcPage'.$pageNumber,
                'url' => route('qpc-v2-font', ['page' => $pageNumber]),
                'format' => $fontFormat,
            ];
        }

        return null;
    }

    /**
     * @return array<int, array{
     *     global_word_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     text: string,
     *     is_glyph: bool
     * }>
     */
    private function loadQpcDisplayWordsByIndex(int $startIndex, int $endIndex): array
    {
        $databasePath = $this->resolveQpcDisplayWordsDatabasePath();

        if ($databasePath === null) {
            return [];
        }

        if ($startIndex < 1 || $endIndex < $startIndex) {
            return [];
        }

        $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $statement = $database->prepare('SELECT id, surah, ayah, text FROM words WHERE id BETWEEN :start AND :end ORDER BY id');

        if (! $statement instanceof \SQLite3Stmt) {
            $database->close();

            return [];
        }

        $statement->bindValue(':start', $startIndex, SQLITE3_INTEGER);
        $statement->bindValue(':end', $endIndex, SQLITE3_INTEGER);

        $result = $statement->execute();

        if (! $result instanceof \SQLite3Result) {
            $statement->close();
            $database->close();

            return [];
        }

        $wordsByIndex = [];

        while (true) {
            $row = $result->fetchArray(SQLITE3_ASSOC);

            if (! is_array($row)) {
                break;
            }

            $wordIndex = (int) ($row['id'] ?? 0);
            $wordText = (string) ($row['text'] ?? '');

            if ($wordIndex < 1 || $wordText === '') {
                continue;
            }

            $wordsByIndex[$wordIndex] = [
                'global_word_index' => $wordIndex,
                'surah_number' => (int) ($row['surah'] ?? 0),
                'ayah_number' => (int) ($row['ayah'] ?? 0),
                'text' => $wordText,
                'is_glyph' => true,
            ];
        }

        $result->finalize();
        $statement->close();
        $database->close();

        return $wordsByIndex;
    }

    private function resolveQpcDisplayWordsDatabasePath(): ?string
    {
        $candidates = [
            base_path('resources/raw-data/quran/layouts/qpc-v2.db'),
            dirname(base_path()).'/resources/raw-data/quran/layouts/qpc-v2.db',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/layouts/qpc-v2.db'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
