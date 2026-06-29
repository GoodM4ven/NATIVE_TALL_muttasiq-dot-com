<?php

declare(strict_types=1);

namespace App\Services\Quran;

use GoodMaven\Arabicable\Facades\ArabicFilter;
use GoodMaven\Arabicable\Support\Quran\QpcWordsDatabase;
use GoodMaven\Arabicable\Support\Quran\QuranSearchText;
use GoodMaven\Arabicable\Support\Quran\QuranWordCopyText;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QuranReaderDataService
{
    private const READY_CACHE_KEY = 'quran-reader-ready-v2';

    private const MAX_PAGE_CACHE_KEY = 'quran-reader-max-page-v2';

    private const SEARCH_RESULTS_CACHE_PREFIX = 'quran-reader-search-results-v14';

    private const UNBOUNDED_STAGE_RESULT_LIMIT = 6200;

    private const DISPLAYED_PAGE_CACHE_PREFIX = 'quran-reader-display-page-v2';

    private const DISPLAYED_PAGE_LOOKUP_CACHE_KEY = 'quran-reader-qpc-displayed-page-lookup-v1';

    /**
     * @var array<int, string>
     */
    private const SACRED_NAME_SEARCH_TOKENS = [
        'الله',
        'والله',
        'فالله',
        'بالله',
        'تالله',
        'كالله',
        'لله',
        'ولله',
        'فلله',
        'تلله',
        'بلله',
    ];

    /**
     * @var array<int, string>
     */
    private const SEARCH_PROGRESS_STAGE_ORDER = [
        'surah_exact',
        'surah_close',
        'surah_sarf',
        'ayah_exact',
        'ayah_close',
        'ayah_sarf',
        'ayah_jathr',
    ];

    /**
     * @var array<string, array<int, int>>
     */
    private const OPENING_AYAH_QUERY_MAP = [
        'حم' => [40, 41, 42, 43, 44, 45, 46],
        'حم عسق' => [42],
    ];

    /**
     * @var array<int, string>
     */
    private const LONE_SEARCH_BLOCKED_WORDS = [
        'قال',
        'كان',
    ];

    /**
     * @var array<int, string>
     */
    private const SEMANTIC_FOCUS_SKIP_STEMS = [
        'قال',
        'قول',
        'كان',
        'كون',
    ];

    private const SEMANTIC_STAGE_RESULT_LIMIT = 50;

    /**
     * @phpstan-impure
     */
    public function isReady(): bool
    {
        try {
            return (bool) Cache::memo()->remember(self::READY_CACHE_KEY, now()->addMinutes(5), static function (): bool {
                $hasVersesTable = Schema::hasTable('quran_verses');
                $hasWordsTable = Schema::hasTable('quran_words');
                $hasMushafLinesTable = Schema::hasTable('quran_mushaf_lines');
                $hasTypedSearchColumn = $hasVersesTable && Schema::hasColumn('quran_verses', 'text_searchable_typed');

                if (! $hasVersesTable || ! $hasWordsTable || ! $hasMushafLinesTable || ! $hasTypedSearchColumn) {
                    return false;
                }

                $verseCount = (int) DB::table('quran_verses')->count();
                $wordCount = (int) DB::table('quran_words')->count();
                $maxPage = (int) DB::table('quran_mushaf_lines')->max('page_number');

                return $verseCount >= 6200 && $wordCount >= 77000 && $maxPage >= 604;
            });
        } catch (Throwable $exception) {
            Log::warning('Failed to check Quran reader readiness.', [
                'message' => $exception->getMessage(),
            ]);

            try {
                Cache::memo()->forget(self::READY_CACHE_KEY);
            } catch (Throwable) {
                // Ignore cache cleanup failures when the database is unavailable.
            }

            return false;
        }
    }

    /**
     * @var array<int, int>
     */
    private const TARGETED_SURAH_HEADER_CARRYOVER_NUMBERS = [
        4, 10, 22, 23, 24, 26, 27, 32, 33, 37, 38, 45, 47, 53, 60, 64, 65, 80,
    ];

    public function maxPage(): int
    {
        return (int) Cache::memo()->remember(self::MAX_PAGE_CACHE_KEY, now()->addMinutes(30), function (): int {
            if (! $this->isReady()) {
                return 0;
            }

            return (int) DB::table('quran_mushaf_lines')->max('page_number');
        });
    }

    public function forgetReadinessCaches(): void
    {
        Cache::forget(self::READY_CACHE_KEY);
        Cache::forget(self::MAX_PAGE_CACHE_KEY);
        Cache::memo()->forget(self::READY_CACHE_KEY);
        Cache::memo()->forget(self::MAX_PAGE_CACHE_KEY);
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
     *     basmallahFontFamily: string|null,
     *     basmallahFontUrl: string|null,
     *     basmallahFontFormat: string|null,
     *     basmallahText: string|null,
     *     surahHeaderFontFamily: string|null,
     *     surahHeaderFontUrl: string|null,
     *     surahHeaderFontFormat: string|null,
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
                'basmallahFontFamily' => null,
                'basmallahFontUrl' => null,
                'basmallahFontFormat' => null,
                'basmallahText' => null,
                'surahHeaderFontFamily' => null,
                'surahHeaderFontUrl' => null,
                'surahHeaderFontFormat' => null,
                'useCenteredAyahLayout' => true,
            ];
        }

        $maxPage = $this->maxPage();
        $normalizedPage = $maxPage > 0 ? max(1, min($pageNumber, $maxPage)) : 1;
        $basmallahConfigFingerprint = $this->basmallahConfigFingerprint();
        $cacheKey = sprintf(
            'quran-reader-page-v20:%d:%s',
            $normalizedPage,
            $basmallahConfigFingerprint,
        );

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
         *     basmallahFontFamily: string|null,
         *     basmallahFontUrl: string|null,
         *     basmallahFontFormat: string|null,
         *     basmallahText: string|null,
         *     useCenteredAyahLayout: bool
         * } $staticPayload
         */
        $staticPayload = Cache::remember($cacheKey, now()->addDays(30), function () use ($maxPage, $normalizedPage): array {
            $qpcPageFont = $this->resolveQpcPageFont($normalizedPage);
            $basmallahFont = $this->resolveBasmallahFont();
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
                'basmallahFontFamily' => $basmallahFont['family'] ?? null,
                'basmallahFontUrl' => $basmallahFont['url'] ?? null,
                'basmallahFontFormat' => $basmallahFont['format'] ?? null,
                'basmallahText' => $basmallahFont['text'] ?? null,
                'useCenteredAyahLayout' => $useCenteredAyahLayout,
            ];
        });

        $effectiveAyahIndex = $activeAyahIndex;
        $surahHeaderFont = $this->resolveSurahHeaderFont();

        if ($effectiveAyahIndex < 1) {
            $effectiveAyahIndex = $this->firstAyahIndexInPage($staticPayload['mushafLines']) ?? 0;
        }

        return [
            ...$staticPayload,
            'activeAyahIndex' => $effectiveAyahIndex,
            'surahHeaderFontFamily' => $surahHeaderFont['family'] ?? null,
            'surahHeaderFontUrl' => $surahHeaderFont['url'] ?? null,
            'surahHeaderFontFormat' => $surahHeaderFont['format'] ?? null,
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
     *     text_searchable_typed: string,
     *     text_searchable: string
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
         *     text_searchable_typed: string,
         *     text_searchable: string
         * }> $index
         */
        $index = Cache::remember('quran-reader-search-index-v4', now()->addDays(30), function (): array {
            return DB::table('quran_verses')
                ->select([
                    'id',
                    'ayah_index',
                    'surah_number',
                    'ayah_number',
                    'mushaf_page',
                    'text_uthmani',
                    'text_searchable_typed',
                    'text_searchable',
                ])
                ->whereNotNull('text_searchable_typed')
                ->orderBy('ayah_index')
                ->get()
                ->map(function (object $row): array {
                    $surahNumber = (int) ($row->surah_number ?? 0);
                    $ayahNumber = max(1, (int) ($row->ayah_number ?? 1));
                    $mushafPage = $row->mushaf_page !== null ? (int) $row->mushaf_page : 0;
                    $displayPage = $this->resolveDisplayedMushafPage(
                        $surahNumber,
                        $ayahNumber,
                        $mushafPage > 0 ? $mushafPage : null,
                    );

                    return [
                        'id' => (int) $row->id,
                        'ayah_index' => (int) $row->ayah_index,
                        'surah_number' => $surahNumber,
                        'ayah_number' => $ayahNumber,
                        'page_number' => max(1, (int) ($displayPage ?? max(1, $mushafPage))),
                        'text_uthmani' => trim((string) $row->text_uthmani),
                        'text_searchable_typed' => trim((string) $row->text_searchable_typed),
                        'text_searchable' => trim((string) ($row->text_searchable ?? '')),
                    ];
                })
                ->values()
                ->all();
        });

        return $index;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    public function search(string $query, int $limit = 24): array
    {
        return $this->searchProgressively($query, $limit);
    }

    public function primeSearchCaches(): void
    {
        $this->searchIndex();
    }

    /**
     * @param  array<int, string>  $stages
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    public function searchByStages(string $query, array $stages, int $limit = 24): array
    {
        if (! $this->isReady()) {
            return [];
        }

        $queryParts = $this->splitQuranSearchQuery($query);

        if ($queryParts === []) {
            return [];
        }

        if ($this->shouldSkipSearchEntirelyForQuery($queryParts)) {
            return [];
        }

        $resolvedStages = [];

        foreach ($stages as $stage) {
            $normalizedStage = trim((string) $stage);

            if ($normalizedStage === '') {
                continue;
            }

            if (! in_array($normalizedStage, self::SEARCH_PROGRESS_STAGE_ORDER, true)) {
                continue;
            }

            $resolvedStages[$normalizedStage] = true;
        }

        if ($resolvedStages === []) {
            return [];
        }

        $resolvedLimit = self::UNBOUNDED_STAGE_RESULT_LIMIT;
        $hasTypedWordColumn = $this->hasQuranWordColumn('token_searchable_typed');
        $resolvedMatches = [];
        $resolvedMatchIndexes = [];
        foreach ($queryParts as $queryPart) {
            $tokens = $this->prepareSearchTokens(array_values(array_unique(array_filter(
                preg_split('/\s+/u', trim($queryPart)) ?: [],
                static fn (string $token): bool => $token !== '',
            ))));

            if ($tokens === []) {
                continue;
            }

            $partLimit = $this->resolveSearchPartLimit(
                $queryPart,
                $resolvedLimit,
                count($queryParts) > 1,
            );

            $partMatches = $this->buildSearchMatchesForStageSet(
                $queryPart,
                $tokens,
                $partLimit,
                $hasTypedWordColumn,
                array_keys($resolvedStages),
            );

            $this->mergeSearchMatches($resolvedMatches, $resolvedMatchIndexes, $partMatches);
        }

        return $resolvedMatches;
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $resolvedMatches
     * @param  array<int, int>  $resolvedMatchIndexes
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $partMatches
     */
    private function mergeSearchMatches(
        array &$resolvedMatches,
        array &$resolvedMatchIndexes,
        array $partMatches,
    ): void {
        foreach ($partMatches as $match) {
            $verseId = (int) $match['id'];
            $existingIndex = $resolvedMatchIndexes[$verseId] ?? null;

            if ($existingIndex === null) {
                $resolvedMatchIndexes[$verseId] = count($resolvedMatches);
                $resolvedMatches[] = $match;

                continue;
            }

            $existingMatch = $resolvedMatches[$existingIndex] ?? null;

            if (! is_array($existingMatch)) {
                $resolvedMatches[$existingIndex] = $match;

                continue;
            }

            $existingRank = (int) $existingMatch['match_rank'];
            $newRank = (int) $match['match_rank'];

            if ($newRank < $existingRank) {
                $resolvedMatches[$existingIndex] = $match;
            }
        }
    }

    /**
     * @return array{
     *     page_number: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     ayah_index: int,
     *     highlight_ayah_index: int
     * }
     */
    public function resolveSearchNavigationTarget(
        ?int $verseId,
        int $fallbackPageNumber = 1,
        int $fallbackAyahIndex = 0,
        int $fallbackSurahNumber = 1,
        int $fallbackAyahNumber = 0,
    ): array {
        $resolvedPageNumber = max(1, $fallbackPageNumber);
        $resolvedAyahIndex = max(0, $fallbackAyahIndex);
        $resolvedSurahNumber = max(1, $fallbackSurahNumber);
        $resolvedAyahNumber = max(0, $fallbackAyahNumber);

        if ($verseId !== null && $verseId > 0) {
            $row = DB::table('quran_verses')
                ->select(['ayah_index', 'surah_number', 'ayah_number', 'mushaf_page'])
                ->where('id', $verseId)
                ->first();

            if ($row !== null) {
                $resolvedAyahIndex = max(0, (int) ($row->ayah_index ?? 0));
                $resolvedSurahNumber = max(1, (int) ($row->surah_number ?? $resolvedSurahNumber));
                $resolvedAyahNumber = max(0, (int) ($row->ayah_number ?? $resolvedAyahNumber));
                $resolvedDisplayPage = $this->resolveDisplayedMushafPage(
                    $resolvedSurahNumber,
                    max(1, $resolvedAyahNumber),
                    $row->mushaf_page !== null ? (int) $row->mushaf_page : $resolvedPageNumber,
                );

                if ($resolvedDisplayPage !== null && $resolvedDisplayPage > 0) {
                    $resolvedPageNumber = $resolvedDisplayPage;
                }
            }
        }

        $highlightAyahIndex = $resolvedAyahIndex > 0 ? $resolvedAyahIndex : $resolvedAyahNumber;

        return [
            'page_number' => max(1, $resolvedPageNumber),
            'surah_number' => max(1, $resolvedSurahNumber),
            'ayah_number' => max(0, $resolvedAyahNumber),
            'ayah_index' => max(0, $resolvedAyahIndex),
            'highlight_ayah_index' => max(0, $highlightAyahIndex),
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
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    public function searchProgressively(
        string $query,
        int $limit = 24,
        ?callable $onProgress = null,
        ?callable $shouldCancel = null,
    ): array {
        if (! $this->isReady()) {
            return [];
        }

        $queryParts = $this->splitQuranSearchQuery($query);

        if ($queryParts === []) {
            return [];
        }

        if ($this->shouldSkipSearchEntirelyForQuery($queryParts)) {
            return [];
        }

        $resolvedLimit = self::UNBOUNDED_STAGE_RESULT_LIMIT;
        $hasTypedWordColumn = $this->hasQuranWordColumn('token_searchable_typed');
        $cacheKey = sprintf(
            '%s:%d:%d:%s',
            self::SEARCH_RESULTS_CACHE_PREFIX,
            $resolvedLimit,
            $hasTypedWordColumn ? 1 : 0,
            sha1(json_encode($queryParts, JSON_UNESCAPED_UNICODE) ?: implode("\n", $queryParts)),
        );

        $cachedMatches = Cache::memo()->get($cacheKey);

        if (is_array($cachedMatches)) {
            $this->emitProgressFromResolvedMatches($onProgress, $cachedMatches);

            return $cachedMatches;
        }

        if ($shouldCancel !== null && $shouldCancel() === true) {
            return [];
        }

        $resolvedMatches = [];
        $resolvedMatchIndexes = [];
        $forwardProgress = null;

        if ($onProgress !== null) {
            $forwardProgress = static function (array $matches, string $stage, bool $isComplete) use ($onProgress): void {
                if ($isComplete) {
                    return;
                }

                $onProgress($matches, $stage, false);
            };
        }

        foreach ($queryParts as $queryPart) {
            if ($shouldCancel !== null && $shouldCancel() === true) {
                return [];
            }

            $partLimit = $this->resolveSearchPartLimit(
                $queryPart,
                $resolvedLimit,
                count($queryParts) > 1,
            );

            $partMatches = $this->buildSearchMatches(
                $queryPart,
                $partLimit,
                $hasTypedWordColumn,
                $forwardProgress,
                $shouldCancel,
            );

            $this->mergeSearchMatches($resolvedMatches, $resolvedMatchIndexes, $partMatches);
        }

        if ($shouldCancel !== null && $shouldCancel() === true) {
            return [];
        }

        Cache::memo()->put($cacheKey, $resolvedMatches, now()->addHours(12));

        $this->emitSearchProgress($onProgress, $resolvedMatches, 'complete', true);

        return $resolvedMatches;
    }

    /**
     * @return array<int, array{surah_number: int, page_number: int}>
     */
    public function surahDirectory(): array
    {
        if (! $this->isReady()) {
            return array_map(
                static fn (int $surahNumber): array => [
                    'surah_number' => $surahNumber,
                    'page_number' => 1,
                ],
                range(1, 114),
            );
        }

        /** @var array<int, array{surah_number: int, page_number: int}> $resolved */
        $resolved = Cache::remember('quran-reader-surah-directory-v2', now()->addDays(30), function (): array {
            $rows = DB::table('quran_mushaf_lines')
                ->selectRaw('surah_number, MIN(page_number) AS page_number')
                ->whereNotNull('surah_number')
                ->whereBetween('surah_number', [1, 114])
                ->groupBy('surah_number')
                ->orderBy('surah_number')
                ->get();

            $firstPageBySurah = [];

            foreach ($rows as $row) {
                $surahNumber = (int) ($row->surah_number ?? 0);
                $pageNumber = (int) ($row->page_number ?? 0);

                if ($surahNumber < 1 || $surahNumber > 114 || $pageNumber < 1) {
                    continue;
                }

                $firstPageBySurah[$surahNumber] = $pageNumber;
            }

            $directory = [];

            for ($surahNumber = 1; $surahNumber <= 114; $surahNumber++) {
                $pageNumber = (int) ($firstPageBySurah[$surahNumber] ?? 1);
                $shouldShiftToNextPage = in_array(
                    $surahNumber,
                    self::TARGETED_SURAH_HEADER_CARRYOVER_NUMBERS,
                    true,
                );

                if ($shouldShiftToNextPage && $pageNumber < 604) {
                    $pageNumber++;
                }

                $directory[] = [
                    'surah_number' => $surahNumber,
                    'page_number' => $pageNumber,
                ];
            }

            return $directory;
        });

        return $resolved;
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
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function buildSearchMatches(
        string $searchQuery,
        int $limit,
        bool $hasTypedWordColumn,
        ?callable $onProgress = null,
        ?callable $shouldCancel = null,
    ): array {
        $tokens = $this->prepareSearchTokens(array_values(array_unique(array_filter(
            preg_split('/\s+/u', trim($searchQuery)) ?: [],
            static fn (string $token): bool => $token !== '',
        ))));

        if ($tokens === []) {
            return [];
        }

        $matches = [];
        $seenAyahIndexes = [];
        $seenSurahNumbers = [];
        $openingAyahMatches = $this->collectOpeningAyahMatches(
            $searchQuery,
            self::UNBOUNDED_STAGE_RESULT_LIMIT,
        );

        if ($openingAyahMatches !== []) {
            $matches = $openingAyahMatches;

            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        $shouldPrioritizeExactPhrase = count($tokens) > 1;

        if ($shouldPrioritizeExactPhrase) {
            $this->appendExactPhraseMatchesFromSearchIndex(
                $matches,
                $seenAyahIndexes,
                $limit,
                $searchQuery,
                $onProgress,
                $shouldCancel,
            );

            if ($matches !== []) {
                $this->emitSearchProgress($onProgress, $matches, 'complete', true);
            }

            if ($shouldCancel !== null && $shouldCancel() === true) {
                return [];
            }
        }

        $surahExactMatches = $this->collectSurahMatchesByExactQuery($searchQuery, $tokens, $limit);
        $surahExactStageMatches = $this->appendSurahMatches(
            $matches,
            $seenAyahIndexes,
            $seenSurahNumbers,
            $surahExactMatches,
            $limit,
            $searchQuery,
            'surah_exact',
        );
        $this->emitIncrementalSearchProgress(
            $onProgress,
            $matches,
            'surah_exact',
            $surahExactStageMatches,
        );

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        if ($shouldCancel !== null && $shouldCancel() === true) {
            return [];
        }

        if (! $shouldPrioritizeExactPhrase) {
            $this->appendExactPhraseMatchesFromSearchIndex(
                $matches,
                $seenAyahIndexes,
                $limit,
                $searchQuery,
                $onProgress,
                $shouldCancel,
            );

            if ($matches !== []) {
                $this->emitSearchProgress($onProgress, $matches, 'complete', true);
            }

            if ($shouldCancel !== null && $shouldCancel() === true) {
                return [];
            }
        }

        $this->appendExactTokenMatchesFromSearchIndex(
            $matches,
            $seenAyahIndexes,
            $tokens,
            $limit,
            $searchQuery,
            $onProgress,
            $shouldCancel,
        );

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        if ($shouldCancel !== null && $shouldCancel() === true) {
            return [];
        }

        if ($shouldPrioritizeExactPhrase) {
            $surahExactMatches = $this->collectSurahMatchesByExactQuery($searchQuery, $tokens, $limit);
            $surahExactStageMatches = $this->appendSurahMatches(
                $matches,
                $seenAyahIndexes,
                $seenSurahNumbers,
                $surahExactMatches,
                $limit,
                $searchQuery,
                'surah_exact',
            );
            $this->emitIncrementalSearchProgress(
                $onProgress,
                $matches,
                'surah_exact',
                $surahExactStageMatches,
            );

            if (count($matches) >= $limit) {
                $this->emitSearchProgress($onProgress, $matches, 'complete', true);

                return $matches;
            }

            if ($shouldCancel !== null && $shouldCancel() === true) {
                return [];
            }
        }

        $surahCloseMatches = $this->collectSurahMatchesByCloseQuery($searchQuery, $tokens, $limit);
        $surahCloseStageMatches = $this->appendSurahMatches(
            $matches,
            $seenAyahIndexes,
            $seenSurahNumbers,
            $surahCloseMatches,
            $limit,
            $searchQuery,
            'surah_close',
        );
        $this->emitIncrementalSearchProgress(
            $onProgress,
            $matches,
            'surah_close',
            $surahCloseStageMatches,
        );

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        if ($shouldCancel !== null && $shouldCancel() === true) {
            return [];
        }

        $surahSarfMatches = $this->collectSurahMatchesBySarfQuery($searchQuery, $tokens, $limit);
        $surahSarfStageMatches = $this->appendSurahMatches(
            $matches,
            $seenAyahIndexes,
            $seenSurahNumbers,
            $surahSarfMatches,
            $limit,
            $searchQuery,
            'surah_sarf',
        );
        $this->emitIncrementalSearchProgress(
            $onProgress,
            $matches,
            'surah_sarf',
            $surahSarfStageMatches,
        );

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        if ($shouldCancel !== null && $shouldCancel() === true) {
            return [];
        }

        if ($hasTypedWordColumn) {
            $wordLikeVerseIds = $this->collectVerseIdsByWordLikeFallback($searchQuery, $limit);
            $wordPrefixStageMatches = $this->appendVerseMatches(
                $matches,
                $seenAyahIndexes,
                $wordLikeVerseIds,
                $limit,
                $searchQuery,
                'ayah_close',
            );
            $this->emitIncrementalSearchProgress(
                $onProgress,
                $matches,
                'ayah_close',
                $wordPrefixStageMatches,
            );
        }

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        $semanticTokens = $this->resolveSemanticFocusTokens($tokens);
        $hasRootAndStemTokens = $semanticTokens !== [];
        $shouldUseExpandedRoots = count($tokens) <= 6;
        $shouldUseRootStage = count($tokens) <= 6;

        if ($shouldUseExpandedRoots && $hasRootAndStemTokens) {
            $this->appendStemTokenMatchesFromQuranWords(
                $matches,
                $seenAyahIndexes,
                $semanticTokens,
                $limit,
                $searchQuery,
                $onProgress,
                $shouldCancel,
            );
        }

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        if ($shouldUseExpandedRoots && $shouldUseRootStage && $hasRootAndStemTokens) {
            $this->appendRootTokenMatchesFromQuranWords(
                $matches,
                $seenAyahIndexes,
                $semanticTokens,
                $limit,
                $searchQuery,
                $onProgress,
                $shouldCancel,
            );
        }

        if (count($matches) >= $limit || ! $hasTypedWordColumn) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        $this->emitSearchProgress($onProgress, $matches, 'complete', true);

        return $matches;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, string>  $stages
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function buildSearchMatchesForStageSet(
        string $searchQuery,
        array $tokens,
        int $limit,
        bool $hasTypedWordColumn,
        array $stages,
    ): array {
        $stageSet = array_fill_keys($stages, true);
        $matches = [];
        $seenAyahIndexes = [];
        $seenSurahNumbers = [];
        $openingAyahMatches = $this->collectOpeningAyahMatches($searchQuery, $limit);

        if ($openingAyahMatches !== []) {
            if (isset($stageSet['ayah_exact'])) {
                $matches = $openingAyahMatches;
            }

            return $matches;
        }

        $shouldPrioritizeExactPhrase = count($tokens) > 1;

        if ($shouldPrioritizeExactPhrase) {
            if (isset($stageSet['ayah_exact'])) {
                $this->appendExactPhraseMatchesFromSearchIndex(
                    $matches,
                    $seenAyahIndexes,
                    self::UNBOUNDED_STAGE_RESULT_LIMIT,
                    $searchQuery,
                    null,
                    null,
                );
            }
        }

        if (isset($stageSet['surah_exact'])) {
            $surahExactMatches = $this->collectSurahMatchesByExactQuery(
                $searchQuery,
                $tokens,
                self::UNBOUNDED_STAGE_RESULT_LIMIT,
            );
            $this->appendSurahMatches(
                $matches,
                $seenAyahIndexes,
                $seenSurahNumbers,
                $surahExactMatches,
                self::UNBOUNDED_STAGE_RESULT_LIMIT,
                $searchQuery,
                'surah_exact',
            );
        }

        if (! $shouldPrioritizeExactPhrase) {
            if (isset($stageSet['ayah_exact'])) {
                $this->appendExactPhraseMatchesFromSearchIndex(
                    $matches,
                    $seenAyahIndexes,
                    self::UNBOUNDED_STAGE_RESULT_LIMIT,
                    $searchQuery,
                    null,
                    null,
                );
            }
        }

        if (isset($stageSet['surah_close'])) {
            $surahCloseMatches = $this->collectSurahMatchesByCloseQuery(
                $searchQuery,
                $tokens,
                self::UNBOUNDED_STAGE_RESULT_LIMIT,
            );
            $this->appendSurahMatches(
                $matches,
                $seenAyahIndexes,
                $seenSurahNumbers,
                $surahCloseMatches,
                self::UNBOUNDED_STAGE_RESULT_LIMIT,
                $searchQuery,
                'surah_close',
            );
        }

        if (isset($stageSet['surah_sarf'])) {
            $surahSarfMatches = $this->collectSurahMatchesBySarfQuery(
                $searchQuery,
                $tokens,
                self::SEMANTIC_STAGE_RESULT_LIMIT,
            );
            $this->appendSurahMatches(
                $matches,
                $seenAyahIndexes,
                $seenSurahNumbers,
                $surahSarfMatches,
                self::SEMANTIC_STAGE_RESULT_LIMIT,
                $searchQuery,
                'surah_sarf',
            );
        }

        if (isset($stageSet['ayah_close'])) {
            $this->appendExactTokenMatchesFromSearchIndex(
                $matches,
                $seenAyahIndexes,
                $tokens,
                self::UNBOUNDED_STAGE_RESULT_LIMIT,
                $searchQuery,
                null,
                null,
            );

            if ($hasTypedWordColumn) {
                $wordLikeVerseIds = $this->collectVerseIdsByWordLikeFallback(
                    $searchQuery,
                    self::UNBOUNDED_STAGE_RESULT_LIMIT,
                );
                $this->appendVerseMatches(
                    $matches,
                    $seenAyahIndexes,
                    $wordLikeVerseIds,
                    self::UNBOUNDED_STAGE_RESULT_LIMIT,
                    $searchQuery,
                    'ayah_close',
                );
            }
        }

        $semanticTokens = $this->resolveSemanticFocusTokens($tokens);
        $hasRootAndStemTokens = $semanticTokens !== [];
        $shouldUseExpandedRoots = count($tokens) <= 6;
        $shouldUseRootStage = count($tokens) <= 6;

        if (
            isset($stageSet['ayah_sarf']) &&
            $shouldUseExpandedRoots &&
            $hasRootAndStemTokens
        ) {
            $this->appendStemTokenMatchesFromQuranWords(
                $matches,
                $seenAyahIndexes,
                $semanticTokens,
                self::SEMANTIC_STAGE_RESULT_LIMIT,
                $searchQuery,
                null,
                null,
            );
        }

        if (
            isset($stageSet['ayah_jathr']) &&
            $shouldUseExpandedRoots &&
            $shouldUseRootStage &&
            $hasRootAndStemTokens
        ) {
            $this->appendRootTokenMatchesFromQuranWords(
                $matches,
                $seenAyahIndexes,
                $semanticTokens,
                self::SEMANTIC_STAGE_RESULT_LIMIT,
                $searchQuery,
                null,
                null,
            );
        }

        return $matches;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function collectOpeningAyahMatches(string $searchQuery, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $normalizedQuery = trim($this->normalizeQuranSearchQuery($searchQuery));

        if ($normalizedQuery === '') {
            return [];
        }

        $surahNumbers = self::OPENING_AYAH_QUERY_MAP[$normalizedQuery] ?? [];

        if ($surahNumbers === []) {
            return [];
        }

        $entriesBySurahNumber = [];

        foreach ($this->surahSearchEntries() as $entry) {
            $surahNumber = (int) $entry['surah_number'];

            if ($surahNumber < 1) {
                continue;
            }

            $entriesBySurahNumber[$surahNumber] = $entry;
        }

        $verseIds = [];
        $openings = [];

        foreach ($surahNumbers as $surahNumber) {
            $entry = $entriesBySurahNumber[$surahNumber] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            $verseId = max(0, (int) $entry['verse_id']);

            if ($verseId < 1) {
                continue;
            }

            $verseIds[] = $verseId;
            $openings[$surahNumber] = $entry;
        }

        if ($verseIds === []) {
            return [];
        }

        $verseRowsById = DB::table('quran_verses')
            ->select([
                'id',
                'ayah_index',
                'surah_number',
                'ayah_number',
                'mushaf_page',
                'text_uthmani',
                'text_searchable_typed',
            ])
            ->whereIn('id', $verseIds)
            ->get()
            ->keyBy(static fn (object $row): int => (int) $row->id);

        $resolved = [];

        foreach ($surahNumbers as $surahNumber) {
            $entry = $openings[$surahNumber] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            $verseId = max(0, (int) $entry['verse_id']);
            $row = $verseRowsById->get($verseId);

            if (! is_object($row)) {
                continue;
            }

            $surahNumber = max(1, (int) ($row->surah_number ?? $surahNumber));
            $ayahNumber = max(1, (int) ($row->ayah_number ?? 1));
            $mushafPage = $row->mushaf_page !== null ? (int) $row->mushaf_page : 0;
            $displayPage = $this->resolveDisplayedMushafPage(
                $surahNumber,
                $ayahNumber,
                $mushafPage > 0 ? $mushafPage : null,
            );

            $resolved[] = [
                'id' => (int) $row->id,
                'ayah_index' => max(0, (int) ($row->ayah_index ?? 0)),
                'surah_number' => $surahNumber,
                'ayah_number' => $ayahNumber,
                'page_number' => max(1, (int) ($displayPage ?? max(1, $mushafPage))),
                'text_uthmani' => trim((string) $row->text_uthmani),
                'text_searchable_typed' => trim((string) $row->text_searchable_typed),
                'search_snippet' => $this->buildSearchSnippet(
                    trim((string) $row->text_searchable_typed),
                    $searchQuery,
                ),
                'match_strategy' => 'ayah_exact',
                'match_tone' => 'success',
                'match_shade' => 500,
                'match_label' => 'مطابقة آية تامة',
                'match_rank' => 4,
            ];

            if (count($resolved) >= $limit) {
                break;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, array{
     *     surah_number: int,
     *     ayah_index: int,
     *     ayah_number: int,
     *     page_number: int,
     *     verse_id: int,
     *     surah_name: string
     * }>
     */
    private function collectSurahMatchesByExactQuery(string $searchQuery, array $tokens, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $queryVariants = $this->exactPhraseQueryVariants($searchQuery);

        if ($queryVariants === []) {
            return [];
        }

        $entries = $this->surahSearchEntries();

        if ($entries === []) {
            return [];
        }

        $matches = [];

        foreach ($entries as $entry) {
            $nameSearchable = trim((string) $entry['name_searchable']);

            if (! $this->isSurahNameExactMatch($nameSearchable, $queryVariants, $tokens)) {
                continue;
            }

            $matches[] = [
                'surah_number' => (int) $entry['surah_number'],
                'ayah_index' => max(0, (int) $entry['ayah_index']),
                'ayah_number' => max(1, (int) $entry['ayah_number']),
                'page_number' => max(1, (int) $entry['page_number']),
                'verse_id' => max(0, (int) $entry['verse_id']),
                'surah_name' => trim((string) $entry['surah_name']),
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, array{
     *     surah_number: int,
     *     ayah_index: int,
     *     ayah_number: int,
     *     page_number: int,
     *     verse_id: int,
     *     surah_name: string
     * }>
     */
    private function collectSurahMatchesByCloseQuery(string $searchQuery, array $tokens, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $entries = $this->surahSearchEntries();

        if ($entries === []) {
            return [];
        }

        $normalizedQueryTokens = $this->surahNameStageTokens($tokens);
        $rawQueryTokens = $this->surahNameStageTokens($this->rawSearchQueryTokens($searchQuery));

        if (count($normalizedQueryTokens) !== 1 || count($rawQueryTokens) !== 1) {
            return [];
        }

        $queryStemCandidates = [];
        $rawQueryStem = trim(ArabicFilter::forStem($searchQuery));

        if (mb_strlen($rawQueryStem) >= 3) {
            $queryStemCandidates[$rawQueryStem] = true;
        }

        $tokenStem = trim(ArabicFilter::forStem(implode(' ', $normalizedQueryTokens)));

        if (mb_strlen($tokenStem) >= 3) {
            $queryStemCandidates[$tokenStem] = true;
        }

        $queryStems = array_keys($queryStemCandidates);

        $matches = [];

        foreach ($entries as $entry) {
            $nameSearchable = trim((string) $entry['name_searchable']);
            $nameStem = trim((string) $entry['name_stem']);

            if (
                ! $this->isSurahNameCloseMatch(
                    $nameSearchable,
                    $nameStem,
                    $searchQuery,
                    $normalizedQueryTokens,
                    $queryStems,
                    true,
                )
            ) {
                continue;
            }

            $matches[] = [
                'surah_number' => (int) $entry['surah_number'],
                'ayah_index' => max(0, (int) $entry['ayah_index']),
                'ayah_number' => max(1, (int) $entry['ayah_number']),
                'page_number' => max(1, (int) $entry['page_number']),
                'verse_id' => max(0, (int) $entry['verse_id']),
                'surah_name' => trim((string) $entry['surah_name']),
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, array{
     *     surah_number: int,
     *     ayah_index: int,
     *     ayah_number: int,
     *     page_number: int,
     *     verse_id: int,
     *     surah_name: string
     * }>
     */
    private function collectSurahMatchesBySarfQuery(string $searchQuery, array $tokens, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $normalizedQueryTokens = $this->surahNameStageTokens($tokens);
        $rawQueryTokens = $this->surahNameStageTokens($this->rawSearchQueryTokens($searchQuery));

        if (count($normalizedQueryTokens) !== 1 || count($rawQueryTokens) !== 1) {
            return [];
        }

        $entries = $this->surahSearchEntries();

        if ($entries === []) {
            return [];
        }

        $queryStemCandidates = [];
        $rawQueryStem = trim(ArabicFilter::forStem($searchQuery));

        if (mb_strlen($rawQueryStem) >= 2) {
            $queryStemCandidates[$rawQueryStem] = true;
        }

        foreach ($normalizedQueryTokens as $token) {
            $tokenStem = trim(ArabicFilter::forStem($token));

            if (mb_strlen($tokenStem) < 2) {
                continue;
            }

            $queryStemCandidates[$tokenStem] = true;
        }

        $queryStems = array_keys($queryStemCandidates);

        if ($queryStems === []) {
            return [];
        }

        $matches = [];

        foreach ($entries as $entry) {
            $nameStem = trim((string) $entry['name_stem']);

            if (! $this->isSurahNameStemMatch($nameStem, $queryStems)) {
                continue;
            }

            $matches[] = [
                'surah_number' => (int) $entry['surah_number'],
                'ayah_index' => max(0, (int) $entry['ayah_index']),
                'ayah_number' => max(1, (int) $entry['ayah_number']),
                'page_number' => max(1, (int) $entry['page_number']),
                'verse_id' => max(0, (int) $entry['verse_id']),
                'surah_name' => trim((string) $entry['surah_name']),
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function surahNameStageTokens(array $tokens): array
    {
        $normalizedTokens = array_values(array_filter(
            array_map(static fn (string $token): string => trim($token), $tokens),
            static fn (string $token): bool => $token !== '',
        ));

        if ($normalizedTokens === []) {
            return [];
        }

        $minimumLength = count($normalizedTokens) <= 1 ? 3 : 4;

        return array_values(array_filter(
            $normalizedTokens,
            static fn (string $token): bool => mb_strlen($token) >= $minimumLength,
        ));
    }

    /**
     * @return array<int, string>
     */
    private function rawSearchQueryTokens(string $searchQuery): array
    {
        return array_values(array_filter(
            preg_split('/\s+/u', trim($searchQuery)) ?: [],
            static fn (string $token): bool => $token !== '',
        ));
    }

    private function isSurahNameExactMatch(
        string $nameSearchable,
        array $queryVariants,
        array $tokens,
    ): bool {
        if ($nameSearchable === '') {
            return false;
        }

        foreach ($queryVariants as $variant) {
            $normalizedVariant = trim((string) $variant);

            if ($normalizedVariant === '') {
                continue;
            }

            if ($nameSearchable === $normalizedVariant) {
                return true;
            }

            if ($this->containsWholePhrase($nameSearchable, $normalizedVariant)) {
                return true;
            }
        }

        if ($tokens === []) {
            return false;
        }

        $nameTokens = array_values(array_filter(
            preg_split('/\s+/u', $nameSearchable) ?: [],
            static fn (string $token): bool => $token !== '',
        ));
        $nameTokenLookup = array_fill_keys($nameTokens, true);

        if ($nameTokenLookup === []) {
            return false;
        }

        foreach ($tokens as $token) {
            if (! isset($nameTokenLookup[$token])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, string>  $queryStems
     */
    private function isSurahNameCloseMatch(
        string $nameSearchable,
        string $nameStem,
        string $searchQuery,
        array $tokens,
        array $queryStems,
        bool $shouldUseStemFallback,
    ): bool {
        if ($nameSearchable === '') {
            return false;
        }

        $queryVariants = $this->exactPhraseQueryVariants($searchQuery);

        if ($this->isSurahNameExactMatch($nameSearchable, $queryVariants, $tokens)) {
            return false;
        }

        if ($searchQuery !== '' && str_contains($nameSearchable, $searchQuery)) {
            return true;
        }

        $nameTokens = array_values(array_filter(
            preg_split('/\s+/u', $nameSearchable) ?: [],
            static fn (string $token): bool => $token !== '',
        ));

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 3) {
                continue;
            }

            foreach ($nameTokens as $nameToken) {
                if (
                    (mb_strlen($nameToken) >= 3 && str_starts_with($nameToken, $token)) ||
                    (mb_strlen($nameToken) >= 3 && str_starts_with($token, $nameToken))
                ) {
                    return true;
                }
            }
        }

        return $shouldUseStemFallback
            && $nameStem !== ''
            && $this->isSurahNameStemMatch($nameStem, $queryStems);
    }

    /**
     * @param  array<int, string>  $queryStems
     */
    private function isSurahNameStemMatch(string $nameStem, array $queryStems): bool
    {
        if ($nameStem === '' || $queryStems === []) {
            return false;
        }

        $nameStemTokens = array_values(array_filter(
            preg_split('/\s+/u', $nameStem) ?: [],
            static fn (string $token): bool => $token !== '',
        ));
        $nameStemLookup = array_fill_keys($nameStemTokens, true);

        foreach ($queryStems as $queryStem) {
            $normalizedQueryStem = trim($queryStem);

            if ($normalizedQueryStem === '') {
                continue;
            }

            if (
                $nameStem === $normalizedQueryStem ||
                $this->containsWholePhrase($nameStem, $normalizedQueryStem)
            ) {
                return true;
            }

            $queryStemTokens = array_values(array_filter(
                preg_split('/\s+/u', $normalizedQueryStem) ?: [],
                static fn (string $token): bool => $token !== '',
            ));

            if ($queryStemTokens === []) {
                continue;
            }

            $allTokensExist = true;

            foreach ($queryStemTokens as $queryStemToken) {
                if (! isset($nameStemLookup[$queryStemToken])) {
                    $allTokensExist = false;

                    break;
                }
            }

            if ($allTokensExist) {
                return true;
            }
        }

        return false;
    }

    private function containsWholePhrase(string $haystack, string $needle): bool
    {
        if ($haystack === '' || $needle === '') {
            return false;
        }

        if ($this->containsWholePhraseBounded($haystack, $needle)) {
            return true;
        }

        $normalizedHaystack = $this->normalizeLeadingHamzatedAlifForExactPhrase($haystack);
        $normalizedNeedle = $this->normalizeLeadingHamzatedAlifForExactPhrase($needle);

        if ($normalizedHaystack === $haystack && $normalizedNeedle === $needle) {
            return false;
        }

        return $this->containsWholePhraseBounded($normalizedHaystack, $normalizedNeedle);
    }

    private function containsWholePhraseBounded(string $haystack, string $needle): bool
    {
        return $haystack === $needle
            || str_starts_with($haystack, $needle.' ')
            || str_ends_with($haystack, ' '.$needle)
            || str_contains($haystack, ' '.$needle.' ');
    }

    private function normalizeLeadingHamzatedAlifForExactPhrase(string $text): string
    {
        $trimmedText = trim($text);

        if ($trimmedText === '') {
            return '';
        }

        return trim((string) (preg_replace('/(^|\s)ءا(?=[\p{Arabic}])/u', '$1ا', $trimmedText) ?? $trimmedText));
    }

    /**
     * @return array<int, array{
     *     surah_number: int,
     *     surah_name: string,
     *     name_searchable: string,
     *     name_stem: string,
     *     ayah_index: int,
     *     ayah_number: int,
     *     page_number: int,
     *     verse_id: int
     * }>
     */
    private function surahSearchEntries(): array
    {
        /** @var array<int, array{
         *     surah_number: int,
         *     surah_name: string,
         *     name_searchable: string,
         *     name_stem: string,
         *     ayah_index: int,
         *     ayah_number: int,
         *     page_number: int,
         *     verse_id: int
         * }> $entries
         */
        $entries = Cache::memo()->remember(
            'quran-reader-surah-search-index-v1',
            now()->addDays(30),
            function (): array {
                $surahNames = $this->surahNames();
                $surahDirectory = $this->surahDirectory();
                $firstVerseRows = DB::table('quran_verses')
                    ->selectRaw(
                        'surah_number, MIN(id) AS verse_id, MIN(ayah_index) AS ayah_index, MIN(ayah_number) AS ayah_number, MIN(mushaf_page) AS mushaf_page',
                    )
                    ->whereBetween('surah_number', [1, 114])
                    ->groupBy('surah_number')
                    ->orderBy('surah_number')
                    ->get();

                $firstVerseBySurah = [];

                foreach ($firstVerseRows as $row) {
                    $surahNumber = (int) ($row->surah_number ?? 0);

                    if ($surahNumber < 1 || $surahNumber > 114) {
                        continue;
                    }

                    $ayahNumber = max(1, (int) ($row->ayah_number ?? 1));
                    $mushafPage = $row->mushaf_page !== null ? (int) $row->mushaf_page : null;
                    $displayPage = $this->resolveDisplayedMushafPage($surahNumber, $ayahNumber, $mushafPage);

                    $firstVerseBySurah[$surahNumber] = [
                        'ayah_index' => max(0, (int) ($row->ayah_index ?? 0)),
                        'ayah_number' => $ayahNumber,
                        'page_number' => max(1, (int) ($displayPage ?? 1)),
                        'verse_id' => max(0, (int) ($row->verse_id ?? 0)),
                    ];
                }

                $directoryBySurah = [];

                foreach ($surahDirectory as $entry) {
                    $surahNumber = (int) $entry['surah_number'];
                    $pageNumber = (int) $entry['page_number'];

                    if ($surahNumber < 1 || $surahNumber > 114 || $pageNumber < 1) {
                        continue;
                    }

                    $directoryBySurah[$surahNumber] = $pageNumber;
                }

                $resolved = [];

                for ($surahNumber = 1; $surahNumber <= 114; $surahNumber++) {
                    $surahName = trim((string) ($surahNames[$surahNumber] ?? ''));

                    if ($surahName === '') {
                        continue;
                    }

                    $nameSearchable = trim($this->normalizeQuranSearchQuery($surahName));

                    if ($nameSearchable === '') {
                        continue;
                    }

                    $verseMeta = $firstVerseBySurah[$surahNumber] ?? [];

                    $resolved[] = [
                        'surah_number' => $surahNumber,
                        'surah_name' => $surahName,
                        'name_searchable' => $nameSearchable,
                        'name_stem' => trim(ArabicFilter::forStem($surahName)),
                        'ayah_index' => max(0, (int) ($verseMeta['ayah_index'] ?? 0)),
                        'ayah_number' => max(1, (int) ($verseMeta['ayah_number'] ?? 1)),
                        'page_number' => max(
                            1,
                            (int) ($verseMeta['page_number'] ?? ($directoryBySurah[$surahNumber] ?? 1)),
                        ),
                        'verse_id' => max(0, (int) ($verseMeta['verse_id'] ?? 0)),
                    ];
                }

                return $resolved;
            },
        );

        return $entries;
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array<int, true>  $seenSurahNumbers
     * @param  array<int, array{
     *     surah_number: int,
     *     ayah_index: int,
     *     ayah_number: int,
     *     page_number: int,
     *     verse_id: int,
     *     surah_name: string
     * }>  $surahMatches
     */
    private function appendSurahMatches(
        array &$matches,
        array &$seenAyahIndexes,
        array &$seenSurahNumbers,
        array $surahMatches,
        int $limit,
        string $searchQuery,
        string $matchStrategy,
    ): array {
        if ($surahMatches === [] || count($matches) >= $limit) {
            return [];
        }

        $matchMeta = $this->resolveSearchMatchMeta($matchStrategy);
        $addedMatches = [];

        foreach ($surahMatches as $surahMatch) {
            $surahNumber = max(1, (int) $surahMatch['surah_number']);

            if (isset($seenSurahNumbers[$surahNumber])) {
                continue;
            }

            $seenSurahNumbers[$surahNumber] = true;
            $ayahIndex = max(0, (int) $surahMatch['ayah_index']);

            if ($ayahIndex > 0) {
                $seenAyahIndexes[$ayahIndex] = true;
            }

            $surahName = trim((string) $surahMatch['surah_name']);
            $title = $surahName !== '' ? 'سورة '.$surahName : 'سورة '.$surahNumber;

            $resolvedMatch = [
                'id' => max(0, (int) $surahMatch['verse_id']),
                'ayah_index' => $ayahIndex,
                'surah_number' => $surahNumber,
                'ayah_number' => max(1, (int) $surahMatch['ayah_number']),
                'page_number' => max(1, (int) $surahMatch['page_number']),
                'text_uthmani' => $title,
                'text_searchable_typed' => $surahName,
                'search_snippet' => $this->buildSearchSnippet($surahName, $searchQuery),
                'match_strategy' => $matchStrategy,
                'match_tone' => $matchMeta['tone'],
                'match_shade' => $matchMeta['shade'],
                'match_label' => $matchMeta['label'],
                'match_rank' => $matchMeta['rank'],
            ];
            $matches[] = $resolvedMatch;
            $addedMatches[] = $resolvedMatch;

            if (count($matches) >= $limit) {
                return $addedMatches;
            }
        }

        return $addedMatches;
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function appendExactPhraseMatchesFromSearchIndex(
        array &$matches,
        array &$seenAyahIndexes,
        int $limit,
        string $searchQuery,
        ?callable $onProgress,
        ?callable $shouldCancel = null,
    ): array {
        $queryVariants = $this->exactPhraseQueryVariants($searchQuery);

        if ($queryVariants === []) {
            return [];
        }

        $addedMatches = [];
        $prefixMatches = $this->collectExactPhrasePrefixMatchesFromSearchIndex($queryVariants, $limit);

        if ($prefixMatches !== []) {
            foreach ($prefixMatches as $row) {
                if ($shouldCancel !== null && $shouldCancel() === true) {
                    return $addedMatches;
                }

                $addedMatch = $this->appendSearchIndexVerseMatch(
                    $matches,
                    $seenAyahIndexes,
                    $row,
                    $limit,
                    $searchQuery,
                    'ayah_exact',
                );

                if ($addedMatch === null) {
                    continue;
                }

                $addedMatches[] = $addedMatch;
                $this->emitSearchProgress($onProgress, $matches, 'ayah_exact');
            }

            return $addedMatches;
        }

        $candidateVerseIds = $this->collectExactPhraseCandidateVerseIds($searchQuery);

        if ($candidateVerseIds !== []) {
            $candidateRows = DB::table('quran_verses')
                ->select([
                    'id',
                    'ayah_index',
                    'surah_number',
                    'ayah_number',
                    'mushaf_page',
                    'text_uthmani',
                    'text_searchable_typed',
                    'text_searchable',
                ])
                ->whereIn('id', $candidateVerseIds)
                ->orderBy('ayah_index')
                ->orderBy('id')
                ->get();

            foreach ($candidateRows as $row) {
                if ($shouldCancel !== null && $shouldCancel() === true) {
                    return $addedMatches;
                }

                $typedText = trim((string) $row->text_searchable_typed);
                $searchableText = trim((string) $row->text_searchable);
                $matchesVariant = false;

                foreach ($queryVariants as $variant) {
                    if (
                        $this->containsWholePhrase($typedText, $variant) ||
                        $this->containsWholePhrase($searchableText, $variant)
                    ) {
                        $matchesVariant = true;

                        break;
                    }
                }

                if (! $matchesVariant) {
                    continue;
                }

                $addedMatch = $this->appendSearchIndexVerseMatch(
                    $matches,
                    $seenAyahIndexes,
                    [
                        'id' => (int) $row->id,
                        'ayah_index' => (int) $row->ayah_index,
                        'surah_number' => (int) $row->surah_number,
                        'ayah_number' => (int) $row->ayah_number,
                        'page_number' => (int) $row->mushaf_page,
                        'text_uthmani' => (string) $row->text_uthmani,
                        'text_searchable_typed' => (string) $row->text_searchable_typed,
                    ],
                    $limit,
                    $searchQuery,
                    'ayah_exact',
                );

                if ($addedMatch === null) {
                    continue;
                }

                $addedMatches[] = $addedMatch;
                $this->emitSearchProgress($onProgress, $matches, 'ayah_exact');
            }

            return $addedMatches;
        }

        foreach ($this->searchIndex() as $row) {
            if ($shouldCancel !== null && $shouldCancel() === true) {
                return $addedMatches;
            }

            if (count($matches) >= $limit) {
                return $addedMatches;
            }

            $typedText = trim((string) $row['text_searchable_typed']);
            $searchableText = trim((string) $row['text_searchable']);
            $matchesVariant = false;

            foreach ($queryVariants as $variant) {
                if (
                    $this->containsWholePhrase($typedText, $variant) ||
                    $this->containsWholePhrase($searchableText, $variant)
                ) {
                    $matchesVariant = true;

                    break;
                }
            }

            if (! $matchesVariant) {
                continue;
            }

            $addedMatch = $this->appendSearchIndexVerseMatch(
                $matches,
                $seenAyahIndexes,
                $row,
                $limit,
                $searchQuery,
                'ayah_exact',
            );

            if ($addedMatch === null) {
                continue;
            }

            $addedMatches[] = $addedMatch;
            $this->emitSearchProgress($onProgress, $matches, 'ayah_exact');
        }

        return $addedMatches;
    }

    /**
     * @param  array<int, string>  $queryVariants
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     text_searchable: string
     * }>
     */
    private function collectExactPhrasePrefixMatchesFromSearchIndex(array $queryVariants, int $limit): array
    {
        if ($queryVariants === [] || $limit < 1) {
            return [];
        }

        $resolvedVariants = [];

        foreach ($queryVariants as $variant) {
            $normalizedVariant = trim((string) $variant);

            if ($normalizedVariant === '') {
                continue;
            }

            $resolvedVariants[$normalizedVariant] = true;
        }

        if ($resolvedVariants === []) {
            return [];
        }

        $rows = DB::table('quran_verses')
            ->select([
                'id',
                'ayah_index',
                'surah_number',
                'ayah_number',
                'mushaf_page',
                'text_uthmani',
                'text_searchable_typed',
                'text_searchable',
            ])
            ->where(function (Builder $builder) use ($resolvedVariants): void {
                foreach (array_keys($resolvedVariants) as $variant) {
                    $builder->orWhere(function (Builder $variantBuilder) use ($variant): void {
                        $variantBuilder
                            ->where('text_searchable_typed', 'like', $variant.'%')
                            ->orWhere('text_searchable', 'like', $variant.'%');
                    });
                }
            })
            ->orderBy('ayah_index')
            ->orderBy('id')
            ->limit(max(1, min(self::UNBOUNDED_STAGE_RESULT_LIMIT, $limit * 4)))
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $resolved = [];

        foreach ($rows as $row) {
            $surahNumber = (int) ($row->surah_number ?? 0);
            $ayahNumber = max(1, (int) ($row->ayah_number ?? 1));
            $mushafPage = $row->mushaf_page !== null ? (int) $row->mushaf_page : 0;
            $displayPage = $this->resolveDisplayedMushafPage(
                $surahNumber,
                $ayahNumber,
                $mushafPage > 0 ? $mushafPage : null,
            );

            $resolved[] = [
                'id' => (int) $row->id,
                'ayah_index' => max(0, (int) ($row->ayah_index ?? 0)),
                'surah_number' => $surahNumber,
                'ayah_number' => $ayahNumber,
                'page_number' => max(1, (int) ($displayPage ?? max(1, $mushafPage))),
                'text_uthmani' => trim((string) $row->text_uthmani),
                'text_searchable_typed' => trim((string) $row->text_searchable_typed),
                'text_searchable' => trim((string) $row->text_searchable),
            ];
        }

        return $resolved;
    }

    /**
     * @return array<int, int>
     */
    private function collectExactPhraseCandidateVerseIds(string $searchQuery): array
    {
        $candidateToken = $this->resolveExactPhraseCandidateToken($searchQuery);

        if ($candidateToken === null) {
            return [];
        }

        $tokenVariants = $this->resolveExactTokenSearchCandidates($candidateToken);
        $candidateColumns = array_values(array_filter([
            $this->hasQuranWordColumn('token_searchable_typed') ? 'token_searchable_typed' : null,
            $this->hasQuranWordColumn('token_searchable') ? 'token_searchable' : null,
        ]));

        if ($tokenVariants === [] || $candidateColumns === []) {
            return [];
        }

        return DB::table('quran_words')
            ->selectRaw('verse_id, MIN(ayah_index) AS ayah_index')
            ->where(function (Builder $whereBuilder) use ($tokenVariants, $candidateColumns): void {
                foreach ($tokenVariants as $variant) {
                    foreach ($candidateColumns as $column) {
                        $this->addTokenPrefixConditions($whereBuilder, $column, $variant);
                    }
                }
            })
            ->groupBy('verse_id')
            ->orderBy('ayah_index')
            ->orderBy('verse_id')
            ->pluck('verse_id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    private function resolveExactPhraseCandidateToken(string $searchQuery): ?string
    {
        $tokens = array_values(array_filter(
            preg_split('/\s+/u', trim($searchQuery)) ?: [],
            static fn (string $token): bool => $token !== '',
        ));

        if ($tokens === []) {
            return null;
        }

        $candidateToken = null;
        $candidateLength = 0;

        foreach ($tokens as $token) {
            $normalizedToken = trim((string) $token);

            if ($normalizedToken === '' || $this->isSacredNameToken($normalizedToken)) {
                continue;
            }

            $tokenLength = mb_strlen($normalizedToken);

            if ($tokenLength < 3 || $tokenLength <= $candidateLength) {
                continue;
            }

            $candidateToken = $normalizedToken;
            $candidateLength = $tokenLength;
        }

        return $candidateToken;
    }

    private function resolveSearchPartLimit(string $queryPart, int $resolvedLimit, bool $isMultiPartQuery): int
    {
        if (! $isMultiPartQuery) {
            return $resolvedLimit;
        }

        return $this->isOpeningAyahQueryPart($queryPart) ? $resolvedLimit : 1;
    }

    private function isOpeningAyahQueryPart(string $queryPart): bool
    {
        $normalizedQueryPart = trim($this->normalizeQuranSearchQuery($queryPart));

        if ($normalizedQueryPart === '') {
            return false;
        }

        return array_key_exists($normalizedQueryPart, self::OPENING_AYAH_QUERY_MAP);
    }

    /**
     * @return array<int, string>
     */
    private function exactPhraseQueryVariants(string $searchQuery): array
    {
        $strictVariants = $this->expandStrictExactPhraseVariants($searchQuery);
        $legacySpellingVariants = $this->expandLegacySpellingExactPhraseVariants($searchQuery);
        $quranOrthographyVariants = $this->expandAdditionalQuranPhraseVariants($searchQuery);
        $variants = [];

        foreach ([$searchQuery, ...$strictVariants, ...$legacySpellingVariants, ...$quranOrthographyVariants] as $variant) {
            $normalizedVariant = trim((string) $variant);

            if ($normalizedVariant === '') {
                continue;
            }

            $variants[$normalizedVariant] = true;
        }

        return array_keys($variants);
    }

    /**
     * @return array<int, string>
     */
    private function expandAdditionalQuranPhraseVariants(string $searchQuery): array
    {
        $tokens = array_values(array_filter(
            preg_split('/\s+/u', trim($searchQuery)) ?: [],
            static fn (string $token): bool => $token !== '',
        ));

        if ($tokens === []) {
            return [];
        }

        $phraseVariants = [[]];

        foreach ($tokens as $token) {
            $tokenVariants = $this->expandAdditionalQuranTokenVariants($token);

            if ($tokenVariants === []) {
                $tokenVariants = [trim($token)];
            }

            $nextPhraseVariants = [];

            foreach ($phraseVariants as $phraseVariantParts) {
                foreach ($tokenVariants as $tokenVariant) {
                    $normalizedTokenVariant = trim($tokenVariant);

                    if ($normalizedTokenVariant === '') {
                        continue;
                    }

                    $nextPhraseVariants[] = [...$phraseVariantParts, $normalizedTokenVariant];
                }
            }

            $phraseVariants = array_slice($nextPhraseVariants, 0, 24);
        }

        $resolvedVariants = [];

        foreach ($phraseVariants as $phraseVariantParts) {
            $variant = trim(implode(' ', $phraseVariantParts));

            if ($variant === '' || $variant === trim($searchQuery)) {
                continue;
            }

            $resolvedVariants[$variant] = true;
        }

        return array_keys($resolvedVariants);
    }

    /**
     * @return array<int, string>
     */
    private function expandLegacySpellingExactPhraseVariants(string $searchQuery): array
    {
        $trimmedQuery = trim($searchQuery);

        if ($trimmedQuery === '') {
            return [];
        }

        $normalizedIla = preg_replace('/(^|\s)الي(?=\s|$)/u', '$1اليا', $trimmedQuery) ?? $trimmedQuery;
        $normalizedWawVerb = preg_replace(
            '/(^|\s)([\p{Arabic}]{2,}عو)(?=\s|$)/u',
            '$1$2ا',
            $trimmedQuery,
        ) ?? $trimmedQuery;
        $normalizedCombined = preg_replace(
            '/(^|\s)([\p{Arabic}]{2,}عو)(?=\s|$)/u',
            '$1$2ا',
            $normalizedIla,
        ) ?? $normalizedIla;

        $variants = [];

        foreach ([$normalizedIla, $normalizedWawVerb, $normalizedCombined] as $candidateVariant) {
            $normalizedVariant = trim((string) $candidateVariant);

            if ($normalizedVariant === '' || $normalizedVariant === $trimmedQuery) {
                continue;
            }

            $variants[$normalizedVariant] = true;
        }

        return array_keys($variants);
    }

    /**
     * @return array<int, int>
     */
    private function collectVerseIdsByWordLikeFallback(string $searchQuery, int $limit): array
    {
        $queryVariants = $this->expandSearchTextVariants($searchQuery);
        $candidateColumns = array_values(array_filter([
            $this->hasQuranWordColumn('token_searchable_typed') ? 'token_searchable_typed' : null,
            $this->hasQuranWordColumn('token_searchable') ? 'token_searchable' : null,
        ]));

        if ($queryVariants === [] || $candidateColumns === []) {
            return [];
        }

        return DB::table('quran_words')
            ->selectRaw('verse_id, MIN(ayah_index) AS ayah_index')
            ->where(function (Builder $whereBuilder) use ($queryVariants, $candidateColumns): void {
                foreach ($queryVariants as $variant) {
                    foreach ($candidateColumns as $column) {
                        $this->addTokenPrefixConditions($whereBuilder, $column, $variant);
                    }
                }
            })
            ->groupBy('verse_id')
            ->orderBy('ayah_index')
            ->orderBy('verse_id')
            ->limit($limit * 4)
            ->pluck('verse_id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array<int, string>  $tokens
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function appendExactTokenMatchesFromSearchIndex(
        array &$matches,
        array &$seenAyahIndexes,
        array $tokens,
        int $limit,
        string $searchQuery,
        ?callable $onProgress,
        ?callable $shouldCancel = null,
    ): array {
        if ($tokens === []) {
            return [];
        }

        $tokenCandidateSets = array_map(
            fn (string $token): array => $this->resolveExactTokenSearchCandidates($token),
            $tokens,
        );

        if (in_array([], $tokenCandidateSets, true)) {
            return [];
        }

        $addedMatches = [];

        foreach ($this->searchIndex() as $row) {
            if ($shouldCancel !== null && $shouldCancel() === true) {
                return $addedMatches;
            }

            $verseTokenLookup = $this->searchIndexVerseTokenLookup($row);

            if ($verseTokenLookup === []) {
                continue;
            }

            foreach ($tokenCandidateSets as $tokenCandidates) {
                $hasToken = false;

                foreach ($tokenCandidates as $tokenCandidate) {
                    if (isset($verseTokenLookup[$tokenCandidate])) {
                        $hasToken = true;

                        break;
                    }
                }

                if (! $hasToken) {
                    continue 2;
                }
            }

            $addedMatch = $this->appendSearchIndexVerseMatch(
                $matches,
                $seenAyahIndexes,
                $row,
                $limit,
                $searchQuery,
                'ayah_close',
            );

            if ($addedMatch === null) {
                continue;
            }

            $addedMatches[] = $addedMatch;
            $this->emitSearchProgress($onProgress, $matches, 'ayah_close');
        }

        return $addedMatches;
    }

    /**
     * @return array<int, string>
     */
    private function resolveExactTokenSearchCandidates(string $token): array
    {
        $expandedVariants = $this->expandSearchTextVariants($token);
        $variants = [];

        foreach ($expandedVariants as $variant) {
            $normalizedVariant = trim((string) $variant);

            if ($normalizedVariant === '') {
                continue;
            }

            $variants[$normalizedVariant] = true;

            $strippedVariant = $this->stripLeadingConjunctionToken($normalizedVariant);

            if ($strippedVariant !== '') {
                $variants[$strippedVariant] = true;
            }

            foreach ($this->expandAdditionalQuranTokenVariants($normalizedVariant) as $quranVariant) {
                $variants[$quranVariant] = true;
            }

            foreach (['و', 'ف'] as $conjunctionPrefix) {
                $prefixedVariant = $conjunctionPrefix.$normalizedVariant;
                $variants[$prefixedVariant] = true;

                $hamzatedPrefixedVariant = $this->hamzateLeadingAlifAfterPrefix($prefixedVariant);

                if ($hamzatedPrefixedVariant !== null && $hamzatedPrefixedVariant !== '') {
                    $variants[$hamzatedPrefixedVariant] = true;
                }
            }
        }

        return array_keys($variants);
    }

    /**
     * @return array<int, string>
     */
    private function expandAdditionalQuranTokenVariants(string $token): array
    {
        $trimmedToken = trim($token);

        if ($trimmedToken === '') {
            return [];
        }

        $variants = [];
        $matches = [];

        if (
            preg_match(
                '/^([وفبكل]?)(ال)?(قان|قران|قرءان|قرءن)$/u',
                $trimmedToken,
                $matches,
            ) === 1
        ) {
            $prefix = trim((string) $matches[1]);
            $article = trim((string) $matches[2]);

            foreach (['قران', 'قرءان', 'قرءن'] as $quranWordVariant) {
                $variants[$prefix.$article.$quranWordVariant] = true;
            }
        }

        unset($variants[$trimmedToken]);

        return array_keys($variants);
    }

    private function stripLeadingConjunctionToken(string $token): string
    {
        $trimmedToken = trim($token);

        if (mb_strlen($trimmedToken) < 3) {
            return $trimmedToken;
        }

        if (preg_match('/^[وف][\p{Arabic}]/u', $trimmedToken) !== 1) {
            return $trimmedToken;
        }

        return mb_substr($trimmedToken, 1);
    }

    private function hamzateLeadingAlifAfterPrefix(string $token): ?string
    {
        $trimmedToken = trim($token);
        $matches = [];

        if (preg_match('/^([وف])ا([\p{Arabic}].*)$/u', $trimmedToken, $matches) !== 1) {
            return null;
        }

        return trim($matches[1].'ءا'.$matches[2]);
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array<int, string>  $tokens
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function appendStemTokenMatchesFromQuranWords(
        array &$matches,
        array &$seenAyahIndexes,
        array $tokens,
        int $limit,
        string $searchQuery,
        ?callable $onProgress,
        ?callable $shouldCancel = null,
    ): array {
        return $this->appendSemanticTokenMatchesFromQuranWords(
            $matches,
            $seenAyahIndexes,
            $tokens,
            min($limit, self::SEMANTIC_STAGE_RESULT_LIMIT),
            $searchQuery,
            $onProgress,
            $shouldCancel,
            'ayah_sarf',
            'token_stem',
        );
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array<int, string>  $tokens
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function appendRootTokenMatchesFromQuranWords(
        array &$matches,
        array &$seenAyahIndexes,
        array $tokens,
        int $limit,
        string $searchQuery,
        ?callable $onProgress,
        ?callable $shouldCancel = null,
    ): array {
        return $this->appendSemanticTokenMatchesFromQuranWords(
            $matches,
            $seenAyahIndexes,
            $tokens,
            min($limit, self::SEMANTIC_STAGE_RESULT_LIMIT),
            $searchQuery,
            $onProgress,
            $shouldCancel,
            'ayah_jathr',
            'token_root',
        );
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array<int, string>  $tokens
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function appendSemanticTokenMatchesFromQuranWords(
        array &$matches,
        array &$seenAyahIndexes,
        array $tokens,
        int $limit,
        string $searchQuery,
        ?callable $onProgress,
        ?callable $shouldCancel,
        string $matchStrategy,
        string $semanticColumn,
    ): array {
        $hasSemanticColumn = $this->hasQuranWordColumn($semanticColumn);
        $searchColumns = $this->resolveTokenSearchColumns();
        $semanticTokenPools = [];
        $stageMatchLimit = max(1, $limit);

        if ((! $hasSemanticColumn && $searchColumns === []) || $tokens === []) {
            return [];
        }

        foreach ($tokens as $token) {
            $verseIds = $this->collectSemanticTokenVerseIds(
                $token,
                $semanticColumn,
                $hasSemanticColumn,
                $searchColumns,
            );

            if ($verseIds === []) {
                continue;
            }

            $verseIds = array_values(array_filter(
                $verseIds,
                static fn (int $verseId): bool => ! isset($seenAyahIndexes[$verseId]),
            ));

            if ($verseIds === []) {
                continue;
            }

            $semanticTokenPools[] = $verseIds;
        }

        if ($semanticTokenPools === []) {
            return [];
        }

        shuffle($semanticTokenPools);

        $matchedVerseIds = $this->interleaveSemanticTokenVerseIds(
            $semanticTokenPools,
            min(
                self::UNBOUNDED_STAGE_RESULT_LIMIT,
                max($stageMatchLimit * 4, self::SEMANTIC_STAGE_RESULT_LIMIT),
            ),
        );

        if ($matchedVerseIds === []) {
            return [];
        }

        $addedMatches = [];

        $addedMatches = $this->appendVerseMatches(
            $matches,
            $seenAyahIndexes,
            $matchedVerseIds,
            self::UNBOUNDED_STAGE_RESULT_LIMIT,
            $searchQuery,
            $matchStrategy,
            $limit,
        );

        if ($addedMatches !== []) {
            $this->emitSearchProgress($onProgress, $matches, $matchStrategy);
        }

        return $addedMatches;
    }

    /**
     * @return array<int, int>
     */
    private function collectSemanticTokenVerseIds(
        string $token,
        string $semanticColumn,
        bool $hasSemanticColumn,
        array $searchColumns,
    ): array {
        if ($token === '') {
            return [];
        }

        $semanticCandidates = $hasSemanticColumn
            ? ($semanticColumn === 'token_root'
                ? $this->resolveRootCandidatesForToken($token)
                : $this->resolveStemCandidatesForToken($token))
            : [];
        $tokenVariants = $this->expandSearchTextVariants($token);

        if ($semanticCandidates === [] && $tokenVariants === []) {
            return [];
        }

        $query = DB::table('quran_words')
            ->select('verse_id')
            ->distinct()
            ->where(function (Builder $builder) use (
                $semanticColumn,
                $semanticCandidates,
                $tokenVariants,
                $searchColumns,
                $hasSemanticColumn,
            ): void {
                $hasSeedCondition = false;

                if ($hasSemanticColumn && $semanticCandidates !== []) {
                    $builder->whereIn($semanticColumn, $semanticCandidates);
                    $hasSeedCondition = true;
                }

                if ($searchColumns !== [] && $tokenVariants !== []) {
                    foreach ($searchColumns as $searchColumn) {
                        if (! $hasSeedCondition) {
                            $builder->whereIn($searchColumn, $tokenVariants);
                            $hasSeedCondition = true;

                            continue;
                        }

                        $builder->orWhereIn($searchColumn, $tokenVariants);
                    }
                }
            });

        $verseIds = $query
            ->pluck('verse_id')
            ->map(static fn (mixed $verseId): int => max(0, (int) $verseId))
            ->filter(static fn (int $verseId): bool => $verseId > 0)
            ->values()
            ->all();

        if ($verseIds === []) {
            return [];
        }

        shuffle($verseIds);

        return $verseIds;
    }

    /**
     * @param  array<int, array<int, int>>  $verseIdPools
     * @return array<int, int>
     */
    private function interleaveSemanticTokenVerseIds(array $verseIdPools, int $limit): array
    {
        if ($verseIdPools === [] || $limit < 1) {
            return [];
        }

        $selectedVerseIds = [];
        $seenVerseIds = [];
        $poolOffsets = array_fill(0, count($verseIdPools), 0);

        while (count($selectedVerseIds) < $limit) {
            $didAddVerseId = false;

            foreach ($verseIdPools as $poolIndex => $verseIdPool) {
                $poolOffset = $poolOffsets[$poolIndex] ?? 0;

                while (isset($verseIdPool[$poolOffset])) {
                    $verseId = (int) $verseIdPool[$poolOffset];
                    $poolOffsets[$poolIndex] = $poolOffset + 1;
                    $poolOffset++;

                    if ($verseId < 1 || isset($seenVerseIds[$verseId])) {
                        continue;
                    }

                    $seenVerseIds[$verseId] = true;
                    $selectedVerseIds[] = $verseId;
                    $didAddVerseId = true;

                    break;
                }

                if (count($selectedVerseIds) >= $limit) {
                    break 2;
                }
            }

            if (! $didAddVerseId) {
                break;
            }
        }

        return $selectedVerseIds;
    }

    /**
     * @return array<int, string>
     */
    private function resolveStemCandidatesForToken(string $token): array
    {
        if ($this->isSacredNameToken($token)) {
            return [];
        }

        $tokenVariants = $this->expandSearchTextVariants($token);
        $seedCandidates = array_map(
            static fn (string $value): string => ArabicFilter::forSearch($value),
            $tokenVariants,
        );
        $searchColumns = $this->resolveTokenSearchColumns();

        if ($searchColumns === []) {
            return array_values(array_filter(array_unique($seedCandidates)));
        }

        $dbCandidates = DB::table('quran_words')
            ->where(function (Builder $builder) use ($searchColumns, $tokenVariants): void {
                foreach ($searchColumns as $column) {
                    $builder->orWhereIn($column, $tokenVariants);
                }
            })
            ->whereNotNull('token_stem')
            ->pluck('token_stem')
            ->map(static fn (mixed $value): string => ArabicFilter::forSearch((string) $value))
            ->all();

        return array_values(array_filter(array_unique(array_merge($seedCandidates, $dbCandidates))));
    }

    /**
     * @return array<int, string>
     */
    private function resolveRootCandidatesForToken(string $token): array
    {
        if ($this->isSacredNameToken($token)) {
            return [];
        }

        $stemCandidates = $this->resolveStemCandidatesForToken($token);
        $tokenVariants = $this->expandSearchTextVariants($token);
        $searchColumns = $this->resolveTokenSearchColumns();

        if ($searchColumns === []) {
            return [];
        }

        $dbCandidates = DB::table('quran_words')
            ->where(function (Builder $builder) use ($searchColumns, $tokenVariants, $stemCandidates): void {
                foreach ($searchColumns as $column) {
                    $builder->orWhereIn($column, $tokenVariants);
                }

                if ($stemCandidates !== [] && $this->hasQuranWordColumn('token_stem')) {
                    $builder->orWhereIn('token_stem', $stemCandidates);
                }
            })
            ->whereNotNull('token_root')
            ->pluck('token_root')
            ->map(static fn (mixed $value): string => ArabicFilter::forSearch((string) $value))
            ->all();

        return array_values(array_filter(array_unique($dbCandidates)));
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array<int, int>  $verseIds
     */
    private function appendVerseMatches(
        array &$matches,
        array &$seenAyahIndexes,
        array $verseIds,
        int $limit,
        string $searchQuery,
        string $matchStrategy,
        ?int $maxAddedMatches = null,
    ): array {
        if ($verseIds === [] || count($matches) >= $limit) {
            return [];
        }

        $matchMeta = $this->resolveSearchMatchMeta($matchStrategy);
        $addedMatches = [];
        $rowsById = [];

        foreach (DB::table('quran_verses')
            ->select([
                'id',
                'ayah_index',
                'surah_number',
                'ayah_number',
                'mushaf_page',
                'text_uthmani',
                'text_searchable_typed',
            ])
            ->whereIn('id', $verseIds)
            ->get() as $row) {
            $rowsById[(int) ($row->id ?? 0)] = $row;
        }

        foreach ($verseIds as $verseId) {
            if ($maxAddedMatches !== null && count($addedMatches) >= $maxAddedMatches) {
                return $addedMatches;
            }

            $row = $rowsById[(int) $verseId] ?? null;

            if ($row === null) {
                continue;
            }

            $ayahIndex = (int) ($row->ayah_index ?? 0);

            if ($ayahIndex < 1 || isset($seenAyahIndexes[$ayahIndex])) {
                continue;
            }

            $seenAyahIndexes[$ayahIndex] = true;
            $surahNumber = (int) ($row->surah_number ?? 0);
            $ayahNumber = (int) ($row->ayah_number ?? 0);
            $displayPage = $this->resolveDisplayedMushafPage(
                $surahNumber,
                $ayahNumber,
                $row->mushaf_page !== null ? (int) $row->mushaf_page : null,
            );

            $resolvedMatch = [
                'id' => (int) ($row->id ?? 0),
                'ayah_index' => $ayahIndex,
                'surah_number' => $surahNumber,
                'ayah_number' => $ayahNumber,
                'page_number' => max(1, (int) ($displayPage ?? 1)),
                'text_uthmani' => trim((string) ($row->text_uthmani ?? '')),
                'text_searchable_typed' => trim((string) ($row->text_searchable_typed ?? '')),
                'search_snippet' => $this->buildSearchSnippet(
                    (string) ($row->text_searchable_typed ?? ''),
                    $searchQuery,
                ),
                'match_strategy' => $matchStrategy,
                'match_tone' => $matchMeta['tone'],
                'match_shade' => $matchMeta['shade'],
                'match_label' => $matchMeta['label'],
                'match_rank' => $matchMeta['rank'],
            ];
            $matches[] = $resolvedMatch;
            $addedMatches[] = $resolvedMatch;

            if (
                ($maxAddedMatches === null && count($matches) >= $limit) ||
                ($maxAddedMatches !== null && count($addedMatches) >= $maxAddedMatches)
            ) {
                return $addedMatches;
            }
        }

        return $addedMatches;
    }

    /**
     * @param  array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     text_searchable?: string
     * }  $row
     * @return array<string, true>
     */
    private function searchIndexVerseTokenLookup(array $row): array
    {
        $lookup = [];

        foreach (['text_searchable_typed', 'text_searchable'] as $column) {
            $tokens = preg_split('/\s+/u', trim((string) ($row[$column] ?? ''))) ?: [];

            foreach ($tokens as $token) {
                $normalizedToken = trim($token);

                if ($normalizedToken === '') {
                    continue;
                }

                $lookup[$normalizedToken] = true;
            }
        }

        return $lookup;
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenAyahIndexes
     * @param  array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     text_searchable?: string
     * }  $row
     * @return array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }|null
     */
    private function appendSearchIndexVerseMatch(
        array &$matches,
        array &$seenAyahIndexes,
        array $row,
        int $limit,
        string $searchQuery,
        string $matchStrategy,
    ): ?array {
        if (count($matches) >= $limit) {
            return null;
        }

        $ayahIndex = max(0, (int) $row['ayah_index']);

        if ($ayahIndex < 1 || isset($seenAyahIndexes[$ayahIndex])) {
            return null;
        }

        $seenAyahIndexes[$ayahIndex] = true;
        $surahNumber = (int) $row['surah_number'];
        $ayahNumber = (int) $row['ayah_number'];
        $displayPage = $this->resolveDisplayedMushafPage(
            $surahNumber,
            $ayahNumber,
            max(1, (int) $row['page_number']),
        );
        $matchMeta = $this->resolveSearchMatchMeta($matchStrategy);
        $resolvedMatch = [
            'id' => (int) $row['id'],
            'ayah_index' => $ayahIndex,
            'surah_number' => $surahNumber,
            'ayah_number' => $ayahNumber,
            'page_number' => max(1, (int) ($displayPage ?? 1)),
            'text_uthmani' => trim((string) $row['text_uthmani']),
            'text_searchable_typed' => trim((string) $row['text_searchable_typed']),
            'search_snippet' => $this->buildSearchSnippet(
                (string) $row['text_searchable_typed'],
                $searchQuery,
            ),
            'match_strategy' => $matchStrategy,
            'match_tone' => $matchMeta['tone'],
            'match_shade' => $matchMeta['shade'],
            'match_label' => $matchMeta['label'],
            'match_rank' => $matchMeta['rank'],
        ];

        $matches[] = $resolvedMatch;

        return $resolvedMatch;
    }

    /**
     * @return array<int, string>
     */
    private function resolveTokenSearchColumns(): array
    {
        return array_values(array_filter([
            $this->hasQuranWordColumn('token_searchable_typed') ? 'token_searchable_typed' : null,
            $this->hasQuranWordColumn('token_lemma') ? 'token_lemma' : null,
            $this->hasQuranWordColumn('token_searchable') ? 'token_searchable' : null,
        ]));
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     */
    private function emitSearchProgress(?callable $onProgress, array $matches, string $stage, bool $isComplete = false): void
    {
        if ($onProgress === null) {
            return;
        }

        $onProgress($matches, $stage, $isComplete);
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $stageMatches
     */
    private function emitIncrementalSearchProgress(
        ?callable $onProgress,
        array $matches,
        string $stage,
        array $stageMatches,
    ): void {
        if ($stageMatches === []) {
            return;
        }

        $stageMatchCount = count($stageMatches);
        $baseCount = max(0, count($matches) - $stageMatchCount);

        for ($stageOffset = 1; $stageOffset <= $stageMatchCount; $stageOffset++) {
            $this->emitSearchProgress(
                $onProgress,
                array_slice($matches, 0, $baseCount + $stageOffset),
                $stage,
            );
        }
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     */
    private function emitProgressFromResolvedMatches(?callable $onProgress, array $matches): void
    {
        if ($onProgress === null) {
            return;
        }

        if ($matches === []) {
            $this->emitSearchProgress($onProgress, [], 'complete', true);

            return;
        }

        $accumulatedMatches = [];
        $seenVerseIds = [];
        $didEmitProgress = false;

        foreach (self::SEARCH_PROGRESS_STAGE_ORDER as $stage) {
            $stageMatches = $this->collectStageMatches($matches, $stage, $seenVerseIds);

            if ($stageMatches === []) {
                continue;
            }

            foreach ($stageMatches as $stageMatch) {
                $accumulatedMatches[] = $stageMatch;
                $this->emitSearchProgress($onProgress, $accumulatedMatches, $stage);
            }

            $didEmitProgress = true;
        }

        $fallbackMatches = $this->collectStageMatches($matches, '', $seenVerseIds);

        if ($fallbackMatches !== []) {
            foreach ($fallbackMatches as $fallbackMatch) {
                $accumulatedMatches[] = $fallbackMatch;
                $this->emitSearchProgress($onProgress, $accumulatedMatches, 'fallback');
            }

            $didEmitProgress = true;
        }

        if (! $didEmitProgress) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return;
        }

        $this->emitSearchProgress($onProgress, $accumulatedMatches, 'complete', true);
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>  $matches
     * @param  array<int, true>  $seenVerseIds
     * @return array<int, array{
     *     id: int,
     *     ayah_index: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     page_number: int,
     *     text_uthmani: string,
     *     text_searchable_typed: string,
     *     search_snippet: string,
     *     match_strategy: string,
     *     match_tone: string,
     *     match_shade: int,
     *     match_label: string,
     *     match_rank: int
     * }>
     */
    private function collectStageMatches(array $matches, string $stage, array &$seenVerseIds): array
    {
        $collectedMatches = [];

        foreach ($matches as $match) {
            $matchStage = (string) $match['match_strategy'];
            $isKnownStage = in_array($matchStage, self::SEARCH_PROGRESS_STAGE_ORDER, true);

            if ($stage !== '') {
                if ($matchStage !== $stage) {
                    continue;
                }
            } elseif ($isKnownStage) {
                continue;
            }

            $verseId = max(0, (int) $match['id']);

            if ($verseId < 1 || array_key_exists($verseId, $seenVerseIds)) {
                continue;
            }

            $seenVerseIds[$verseId] = true;
            $collectedMatches[] = $match;
        }

        return $collectedMatches;
    }

    /**
     * @return array{tone: string, shade: int, label: string, rank: int}
     */
    private function resolveSearchMatchMeta(string $matchStrategy): array
    {
        return match ($matchStrategy) {
            'surah_exact' => [
                'tone' => 'success',
                'shade' => 500,
                'label' => 'مطابقة اسم سورة',
                'rank' => 1,
            ],
            'surah_close' => [
                'tone' => 'warning',
                'shade' => 500,
                'label' => 'مطابقة سورة قريبة',
                'rank' => 2,
            ],
            'surah_sarf' => [
                'tone' => 'info',
                'shade' => 500,
                'label' => 'مطابقة سورة صرفية',
                'rank' => 3,
            ],
            'ayah_exact' => [
                'tone' => 'success',
                'shade' => 500,
                'label' => 'مطابقة آية تامة',
                'rank' => 4,
            ],
            'ayah_close' => [
                'tone' => 'warning',
                'shade' => 500,
                'label' => 'مطابقة آية قريبة',
                'rank' => 5,
            ],
            'ayah_sarf' => [
                'tone' => 'info',
                'shade' => 500,
                'label' => 'مطابقة صرفية',
                'rank' => 6,
            ],
            'ayah_jathr' => [
                'tone' => 'danger',
                'shade' => 500,
                'label' => 'مطابقة جذرية',
                'rank' => 7,
            ],
            default => [
                'tone' => 'warning',
                'shade' => 500,
                'label' => 'مطابقة',
                'rank' => 8,
            ],
        };
    }

    private function normalizeQuranSearchQuery(string $text): string
    {
        return QuranSearchText::normalizeQuery($text);
    }

    private function sanitizeQuranSearchQuery(string $text): string
    {
        $normalized = trim($this->normalizeQuranSearchQuery($text));

        if ($normalized === '') {
            return '';
        }

        $arabicOnly = preg_replace('/[^\p{Arabic}\s]+/u', ' ', $normalized) ?? $normalized;
        $collapsedSpaces = preg_replace('/\s+/u', ' ', trim($arabicOnly)) ?? trim($arabicOnly);

        return trim($collapsedSpaces);
    }

    /**
     * @return array<int, string>
     */
    private function splitQuranSearchQuery(string $text): array
    {
        $normalizedSeparators = preg_replace('/[۝\r\n]+/u', "\n", trim($text)) ?? trim($text);

        if ($normalizedSeparators === '') {
            return [];
        }

        $parts = preg_split('/\n+/u', $normalizedSeparators) ?: [];
        $resolvedParts = [];

        foreach ($parts as $part) {
            $sanitizedPart = $this->sanitizeQuranSearchQuery($part);

            if ($sanitizedPart === '') {
                continue;
            }

            $resolvedParts[] = $sanitizedPart;
        }

        if ($resolvedParts !== []) {
            return $resolvedParts;
        }

        $sanitizedQuery = $this->sanitizeQuranSearchQuery($text);

        if ($sanitizedQuery === '') {
            return [];
        }

        return [$sanitizedQuery];
    }

    /**
     * @return array<int, string>
     */
    private function expandSearchTextVariants(string $text): array
    {
        $variants = [];

        foreach (QuranSearchText::expandVariants($text) as $variant) {
            $normalizedVariant = trim((string) $variant);

            if ($normalizedVariant === '') {
                continue;
            }

            $variants[$normalizedVariant] = true;

            foreach ($this->expandDefiniteArticlePhraseVariants($normalizedVariant) as $definiteVariant) {
                $variants[$definiteVariant] = true;
            }
        }

        foreach ($this->expandRaoofSpellingVariants($text) as $raoofVariant) {
            $variants[$raoofVariant] = true;
        }

        foreach ($this->expandRahmaSpellingVariants($text) as $rahmaVariant) {
            $variants[$rahmaVariant] = true;
        }

        return array_keys($variants);
    }

    /**
     * @return array<int, string>
     */
    private function expandDefiniteArticlePhraseVariants(string $text): array
    {
        $trimmedText = trim($text);

        if ($trimmedText === '') {
            return [];
        }

        $tokens = array_values(array_filter(preg_split('/\s+/u', $trimmedText) ?: []));

        if ($tokens === []) {
            return [];
        }

        $strippedTokens = array_map(
            fn (string $token): string => $this->stripDefiniteArticleToken($token),
            $tokens,
        );
        $strippedPhrase = trim(implode(' ', $strippedTokens));

        if ($strippedPhrase === '' || $strippedPhrase === $trimmedText) {
            return [];
        }

        return [$strippedPhrase];
    }

    private function stripDefiniteArticleToken(string $token): string
    {
        $normalizedToken = trim($token);

        if ($normalizedToken === '') {
            return '';
        }

        $strippedToken = preg_replace(
            '/^([وفبكل]?)(?:ال)([\p{Arabic}]{2,})$/u',
            '$1$2',
            $normalizedToken,
        );

        if (! is_string($strippedToken) || trim($strippedToken) === '') {
            return $normalizedToken;
        }

        return trim($strippedToken);
    }

    /**
     * @return array<int, string>
     */
    private function expandStrictExactPhraseVariants(string $text): array
    {
        $variants = [];

        foreach (QuranSearchText::expandStrictExactPhraseVariants($text) as $variant) {
            $normalizedVariant = trim((string) $variant);

            if ($normalizedVariant === '') {
                continue;
            }

            $variants[$normalizedVariant] = true;
        }

        foreach ($this->expandRaoofSpellingVariants($text) as $raoofVariant) {
            $variants[$raoofVariant] = true;
        }

        foreach ($this->expandRahmaSpellingVariants($text) as $rahmaVariant) {
            $variants[$rahmaVariant] = true;
        }

        return array_keys($variants);
    }

    /**
     * @return array<int, string>
     */
    private function expandRahmaSpellingVariants(string $text): array
    {
        $trimmedText = trim($text);

        if ($trimmedText === '') {
            return [];
        }

        $variant = strtr($trimmedText, [
            'رحمة' => 'رحمت',
            'رحمت' => 'رحمة',
        ]);

        if ($variant === $trimmedText) {
            return [];
        }

        return [trim($variant)];
    }

    /**
     * @return array<int, string>
     */
    private function expandRaoofSpellingVariants(string $text): array
    {
        $trimmedText = trim($text);

        if ($trimmedText === '') {
            return [];
        }

        $variant = strtr($trimmedText, [
            'رووف' => 'رءوف',
            'رؤوف' => 'رءوف',
            'رءوف' => 'رووف',
        ]);

        if ($variant === $trimmedText) {
            return [];
        }

        return [trim($variant)];
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function resolveSemanticFocusTokens(array $tokens): array
    {
        if ($tokens === []) {
            return [];
        }

        $semanticTokens = [];
        $seenTokens = [];

        foreach ($tokens as $index => $token) {
            $normalizedToken = trim((string) $token);

            if (
                $normalizedToken === '' ||
                isset($seenTokens[$normalizedToken]) ||
                $this->isSemanticFocusTokenBlocked($normalizedToken)
            ) {
                continue;
            }

            $coreToken = $this->semanticFocusTokenCore($normalizedToken);
            $coreLength = mb_strlen($coreToken);

            if ($coreLength < 3) {
                continue;
            }

            $breadthScore = $this->estimateSemanticFocusBreadth($normalizedToken);
            if ($breadthScore < 1) {
                continue;
            }

            $seenTokens[$normalizedToken] = true;
            $semanticTokens[] = [
                'token' => $normalizedToken,
                'breadth' => $breadthScore,
                'index' => $index,
            ];
        }

        if ($semanticTokens === []) {
            return [];
        }

        usort(
            $semanticTokens,
            static function (array $left, array $right): int {
                $breadthComparison = $left['breadth'] <=> $right['breadth'];

                if ($breadthComparison !== 0) {
                    return $breadthComparison;
                }

                return $left['index'] <=> $right['index'];
            },
        );

        return array_map(
            static fn (array $token): string => $token['token'],
            $semanticTokens,
        );
    }

    private function estimateSemanticFocusBreadth(string $token): int
    {
        static $breadthCache = [];

        $normalizedToken = trim($this->normalizeQuranSearchQuery($token));

        if ($normalizedToken === '') {
            return 0;
        }

        if (isset($breadthCache[$normalizedToken])) {
            return $breadthCache[$normalizedToken];
        }

        $breadth = 0;

        $stemCandidates = $this->resolveStemCandidatesForToken($normalizedToken);

        if ($stemCandidates !== []) {
            $breadth = max(
                $breadth,
                (int) DB::table('quran_words')
                    ->whereIn('token_stem', $stemCandidates)
                    ->distinct()
                    ->count('verse_id'),
            );
        }

        $rootCandidates = $this->resolveRootCandidatesForToken($normalizedToken);

        if ($rootCandidates !== []) {
            $breadth = max(
                $breadth,
                (int) DB::table('quran_words')
                    ->whereIn('token_root', $rootCandidates)
                    ->distinct()
                    ->count('verse_id'),
            );
        }

        return $breadthCache[$normalizedToken] = $breadth;
    }

    private function semanticFocusTokenCore(string $token): string
    {
        $normalizedToken = trim($this->normalizeQuranSearchQuery($token));

        if ($normalizedToken === '') {
            return '';
        }

        $coreToken = preg_replace('/^[وفبكل]+(?:ال)?/u', '', $normalizedToken);

        return trim(is_string($coreToken) ? $coreToken : $normalizedToken);
    }

    private function isSemanticFocusTokenBlocked(string $token): bool
    {
        $normalizedToken = trim($this->normalizeQuranSearchQuery($token));

        if ($normalizedToken === '' || $this->isSacredNameToken($normalizedToken)) {
            return true;
        }

        if ($this->isBlockedExactToken($normalizedToken)) {
            return true;
        }

        $stem = trim((string) ArabicFilter::forStem($normalizedToken));

        return $stem !== '' && in_array($stem, self::SEMANTIC_FOCUS_SKIP_STEMS, true);
    }

    /**
     * @param  array<int, string>  $queryParts
     */
    private function shouldSkipSearchEntirelyForQuery(array $queryParts): bool
    {
        if (count($queryParts) !== 1) {
            return false;
        }

        $tokens = array_values(array_filter(
            preg_split('/\s+/u', trim($queryParts[0] ?? '')) ?: [],
            static fn (string $token): bool => $token !== '',
        ));

        return count($tokens) === 1 && $this->isBlockedExactToken($tokens[0]);
    }

    private function isBlockedExactToken(string $token): bool
    {
        $normalizedToken = trim($this->normalizeQuranSearchQuery($token));

        return $normalizedToken !== '' && in_array($normalizedToken, self::LONE_SEARCH_BLOCKED_WORDS, true);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function prepareSearchTokens(array $tokens): array
    {
        return QuranSearchText::prepareTokens($this->collapseVocativeTokens($tokens));
    }

    /**
     * Keep an explicit vocative particle attached to the following token so
     * searches can still target the fused Uthmani form (for example `ياارض`).
     *
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function collapseVocativeTokens(array $tokens): array
    {
        $collapsedTokens = [];
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; $index++) {
            $token = trim((string) ($tokens[$index] ?? ''));

            if ($token === '') {
                continue;
            }

            if (
                $token === 'يا'
                && array_key_exists($index + 1, $tokens)
            ) {
                $nextToken = trim((string) $tokens[$index + 1]);

                if ($nextToken !== '') {
                    $collapsedTokens[] = 'يا'.$nextToken;
                    $index++;

                    continue;
                }
            }

            $collapsedTokens[] = $token;
        }

        return $collapsedTokens;
    }

    private function addTokenPrefixConditions(Builder $builder, string $column, string $variant): void
    {
        $builder
            ->orWhere($column, $variant)
            ->orWhere($column, 'like', $variant.'%');
    }

    private function buildSearchSnippet(string $normalizedVerseText, string $searchQuery): string
    {
        $verseText = trim($normalizedVerseText);
        $query = trim($searchQuery);

        if ($verseText === '') {
            return '';
        }

        if ($query === '') {
            return mb_strlen($verseText) > 90 ? mb_substr($verseText, 0, 90).'…' : $verseText;
        }

        $position = mb_strpos($verseText, $query);

        if ($position === false) {
            foreach ($this->expandSearchTextVariants($query) as $variant) {
                $position = mb_strpos($verseText, $variant);

                if ($position !== false) {
                    $query = $variant;

                    break;
                }
            }
        }

        if ($position === false) {
            foreach (array_values(array_filter(preg_split('/\s+/u', $query) ?: [])) as $token) {
                foreach ($this->expandSearchTextVariants($token) as $variantToken) {
                    $position = mb_strpos($verseText, $variantToken);

                    if ($position !== false) {
                        $query = $variantToken;

                        break 2;
                    }
                }
            }
        }

        if ($position === false) {
            $snippet = mb_strlen($verseText) > 90 ? mb_substr($verseText, 0, 90).'…' : $verseText;

            return '“'.$snippet.'”';
        }

        $queryLength = max(1, mb_strlen($query));
        $contextBefore = 24;
        $contextAfter = 34;
        $start = max(0, $position - $contextBefore);
        $length = min(mb_strlen($verseText) - $start, $contextBefore + $queryLength + $contextAfter);
        $snippet = trim(mb_substr($verseText, $start, $length));

        if ($start > 0) {
            $snippet = '…'.$snippet;
        }

        if (($start + $length) < mb_strlen($verseText)) {
            $snippet .= '…';
        }

        return '“'.$snippet.'”';
    }

    private function isSacredNameToken(string $token): bool
    {
        $normalizedToken = trim(ArabicFilter::forSearch($token));

        if ($normalizedToken === '') {
            return false;
        }

        if (in_array($normalizedToken, self::SACRED_NAME_SEARCH_TOKENS, true)) {
            return true;
        }

        $strippedToken = preg_replace('/^[وفبكلت]+/u', '', $normalizedToken) ?? $normalizedToken;

        return in_array($strippedToken, ['الله', 'لله'], true);
    }

    private function resolveDisplayedMushafPage(
        int $surahNumber,
        int $ayahNumber,
        ?int $mushafPage,
    ): ?int {
        $qpcPage = $this->resolveMushafPageFromQpcWords($surahNumber, $ayahNumber);

        if ($qpcPage !== null) {
            return $qpcPage;
        }

        return $mushafPage;
    }

    private function resolveMushafPageFromQpcWords(int $surahNumber, int $ayahNumber): ?int
    {
        if ($surahNumber < 1 || $ayahNumber < 1) {
            return null;
        }

        $cacheKey = sprintf(
            '%s:%d:%d',
            self::DISPLAYED_PAGE_CACHE_PREFIX,
            $surahNumber,
            $ayahNumber,
        );
        $cachedPage = (int) Cache::memo()->remember($cacheKey, now()->addDays(30), function () use (
            $surahNumber,
            $ayahNumber,
        ): int {
            $lookup = $this->displayedPageLookupBySurahAyah();
            $key = $this->displayedPageLookupKey($surahNumber, $ayahNumber);

            return (int) ($lookup[$key] ?? 0);
        });

        return $cachedPage > 0 ? $cachedPage : null;
    }

    /**
     * @return array<string, int>
     */
    private function displayedPageLookupBySurahAyah(): array
    {
        /** @var array<string, int> $lookup */
        $lookup = Cache::memo()->remember(
            self::DISPLAYED_PAGE_LOOKUP_CACHE_KEY,
            now()->addDays(30),
            function (): array {
                $databasePath = $this->resolveQpcDisplayWordsDatabasePath();

                if ($databasePath === null) {
                    return [];
                }

                $lineRanges = DB::table('quran_mushaf_lines')
                    ->select(['page_number', 'first_word_index', 'last_word_index'])
                    ->whereNotNull('first_word_index')
                    ->whereNotNull('last_word_index')
                    ->orderBy('first_word_index')
                    ->get();

                if ($lineRanges->isEmpty()) {
                    return [];
                }

                $normalizedLineRanges = [];

                foreach ($lineRanges as $lineRangeRow) {
                    $firstWordIndex = (int) ($lineRangeRow->first_word_index ?? 0);
                    $lastWordIndex = (int) ($lineRangeRow->last_word_index ?? 0);
                    $pageNumber = (int) ($lineRangeRow->page_number ?? 0);

                    if ($firstWordIndex < 1 || $lastWordIndex < $firstWordIndex || $pageNumber < 1) {
                        continue;
                    }

                    $normalizedLineRanges[] = [
                        'start' => $firstWordIndex,
                        'end' => $lastWordIndex,
                        'page' => $pageNumber,
                    ];
                }

                if ($normalizedLineRanges === []) {
                    return [];
                }

                $lookup = [];
                $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
                $statement = $database->prepare(
                    'SELECT surah AS surah_number, ayah AS ayah_number, MIN(id) AS first_word_index FROM words GROUP BY surah, ayah',
                );

                if (! $statement instanceof \SQLite3Stmt) {
                    $database->close();

                    return [];
                }

                $result = $statement->execute();

                if (! $result instanceof \SQLite3Result) {
                    $statement->close();
                    $database->close();

                    return [];
                }

                while (true) {
                    $row = $result->fetchArray(SQLITE3_ASSOC);

                    if (! is_array($row)) {
                        break;
                    }

                    $surahNumber = (int) ($row['surah_number'] ?? 0);
                    $ayahNumber = (int) ($row['ayah_number'] ?? 0);
                    $firstWordIndex = (int) ($row['first_word_index'] ?? 0);

                    if ($surahNumber < 1 || $ayahNumber < 1 || $firstWordIndex < 1) {
                        continue;
                    }

                    $pageNumber = $this->resolveMushafPageFromWordIndex($firstWordIndex, $normalizedLineRanges);

                    if ($pageNumber === null) {
                        continue;
                    }

                    $lookup[$this->displayedPageLookupKey($surahNumber, $ayahNumber)] = $pageNumber;
                }

                $result->finalize();
                $statement->close();
                $database->close();

                return $lookup;
            },
        );

        return $lookup;
    }

    private function displayedPageLookupKey(int $surahNumber, int $ayahNumber): string
    {
        return $surahNumber.':'.$ayahNumber;
    }

    /**
     * @param  array<int, array{start: int, end: int, page: int}>  $lineRanges
     */
    private function resolveMushafPageFromWordIndex(int $wordIndex, array $lineRanges): ?int
    {
        $low = 0;
        $high = count($lineRanges) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            $range = $lineRanges[$middle] ?? null;

            if (! is_array($range)) {
                break;
            }

            if ($wordIndex < $range['start']) {
                $high = $middle - 1;

                continue;
            }

            if ($wordIndex > $range['end']) {
                $low = $middle + 1;

                continue;
            }

            return (int) $range['page'];
        }

        return null;
    }

    private function hasQuranWordColumn(string $column): bool
    {
        static $cache = [];

        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        $cache[$column] = Schema::hasTable('quran_words') && Schema::hasColumn('quran_words', $column);

        return $cache[$column];
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
        $copyWordsByAyahPosition = [];
        $copyWordsByGlobalWordIndex = [];
        $canonicalWordsByIndex = [];
        $canonicalWordRows = collect();

        if (Schema::hasTable('quran_words') && $wordRangeStart !== null && $wordRangeEnd !== null) {
            $canonicalWordRows = DB::table('quran_words')
                ->select([
                    'global_word_index',
                    'surah_number',
                    'ayah_number',
                    'word_position',
                    'token_uthmani',
                    'token_searchable_typed',
                ])
                ->whereBetween('global_word_index', [$wordRangeStart, $wordRangeEnd + 1])
                ->orderBy('global_word_index')
                ->get();

            $copyWordsByGlobalWordIndex = QuranWordCopyText::buildMapByGlobalWordIndex($canonicalWordRows);
            $copyWordsByAyahPosition = QuranWordCopyText::buildMapByAyahPosition($canonicalWordRows);

            foreach ($canonicalWordRows as $canonicalWordRow) {
                $canonicalWordIndex = (int) ($canonicalWordRow->global_word_index ?? 0);

                if ($canonicalWordIndex < 1) {
                    continue;
                }

                $canonicalWordsByIndex[$canonicalWordIndex] = [
                    'surah_number' => (int) ($canonicalWordRow->surah_number ?? 0),
                    'ayah_number' => (int) ($canonicalWordRow->ayah_number ?? 0),
                    'word_position' => (int) ($canonicalWordRow->word_position ?? 0),
                    'copy_text' => trim((string) ($copyWordsByGlobalWordIndex[$canonicalWordIndex] ?? '')),
                ];
            }
        }

        if ($wordRangeStart !== null && $wordRangeEnd !== null) {
            $displayWordsByIndex = $this->loadQpcDisplayWordsByIndex($wordRangeStart, $wordRangeEnd + 1);
        }

        if ($displayWordsByIndex === [] && $canonicalWordsByIndex !== []) {
            foreach ($canonicalWordsByIndex as $canonicalWordIndex => $canonicalWordMeta) {
                $fallbackText = trim((string) $canonicalWordMeta['copy_text']);

                if ($fallbackText === '') {
                    continue;
                }

                $displayWordsByIndex[$canonicalWordIndex] = [
                    'global_word_index' => $canonicalWordIndex,
                    'surah_number' => (int) $canonicalWordMeta['surah_number'],
                    'ayah_number' => (int) $canonicalWordMeta['ayah_number'],
                    'text' => $fallbackText,
                    'copy_text' => $fallbackText,
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

        if (Schema::hasTable('quran_words') && $surahNumbers !== [] && $ayahNumbers !== []) {
            $copyWordRows = DB::table('quran_words')
                ->select([
                    'global_word_index',
                    'surah_number',
                    'ayah_number',
                    'word_position',
                    'token_uthmani',
                    'token_searchable_typed',
                ])
                ->whereIn('surah_number', array_keys($surahNumbers))
                ->whereIn('ayah_number', array_keys($ayahNumbers))
                ->orderBy('surah_number')
                ->orderBy('ayah_number')
                ->orderBy('word_position')
                ->get();

            // QPC page word indices are not guaranteed to align with quran_words global indices.
            // Resolve copy tokens by (surah, ayah, word_position) from the displayed page context.
            $copyWordsByGlobalWordIndex = QuranWordCopyText::buildMapByGlobalWordIndex($copyWordRows);
            $copyWordsByAyahPosition = QuranWordCopyText::buildMapByAyahPosition($copyWordRows);
        }

        if ($surahNumbers !== [] && $ayahNumbers !== []) {
            $verseRows = DB::table('quran_verses')
                ->select([
                    'id',
                    'ayah_index',
                    'surah_number',
                    'ayah_number',
                    'text_uthmani',
                    'text_searchable_typed',
                ])
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
                    'copy_text' => QuranWordCopyText::normalizeToken(
                        $verseRow->text_uthmani,
                        $verseRow->text_searchable_typed,
                    ) ?? '',
                ];
            }
        }

        $lines = [];

        $ayahWordPositions = [];

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
                $currentSegmentCopyTokens = [];
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
                    $ayahWordPositions[$pairKey] = (int) ($ayahWordPositions[$pairKey] ?? 0) + 1;
                    $wordPosition = $ayahWordPositions[$pairKey];
                    $wordCopyTextKey = QuranWordCopyText::ayahWordKey($wordSurahNumber, $wordAyahNumber, $wordPosition);
                    $wordCopyText = '';

                    if ($wordCopyTextKey !== null) {
                        $wordCopyText = trim((string) ($copyWordsByAyahPosition[$wordCopyTextKey] ?? ''));
                    }

                    if ($wordCopyText === '' && $wordCopyTextKey === null) {
                        $wordCopyText = trim((string) ($copyWordsByGlobalWordIndex[(int) $word['global_word_index']] ?? ''));
                    }

                    if ($wordCopyText === '' && ! ((bool) $word['is_glyph'])) {
                        $wordCopyText = trim((string) ($word['copy_text'] ?? ''));
                    }

                    if ($wordCopyText === '' && ! ((bool) $word['is_glyph'])) {
                        $wordCopyText = trim((string) $wordText);
                    }

                    if (preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $wordCopyText)) {
                        if ((bool) $word['is_glyph']) {
                            $wordCopyText = '';
                        } else {
                            $wordCopyText = $wordCopyTextKey !== null
                                ? trim((string) ($copyWordsByAyahPosition[$wordCopyTextKey] ?? ''))
                                : '';
                        }
                    }

                    if ($wordCopyText === '' && ! ((bool) $word['is_glyph'])) {
                        $wordCopyText = trim((string) ($word['copy_text'] ?? ''));
                    }

                    if ($wordCopyText === '' && ! ((bool) $word['is_glyph'])) {
                        $wordCopyText = trim((string) $wordText);
                    }

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
                        'copy_text' => $wordCopyText,
                        'ayah_copy_text' => trim((string) ($verseMeta['copy_text'] ?? '')),
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
                            'copy_text' => trim(implode(' ', $currentSegmentCopyTokens)),
                            'ayah_copy_text' => trim((string) $currentSegmentMeta['copy_text']),
                            'ends_ayah' => $currentSegmentEndsAyah,
                        ];

                        $currentSegmentTokens = [];
                        $currentSegmentCopyTokens = [];
                        $currentSegmentMeta = null;
                        $currentSegmentEndsAyah = false;
                    }

                    if ($currentSegmentMeta === null) {
                        $currentSegmentMeta = [
                            'verse_id' => (int) ($verseMeta['id'] ?? 0),
                            'ayah_index' => (int) ($verseMeta['ayah_index'] ?? 0),
                            'surah_number' => $wordSurahNumber,
                            'ayah_number' => $wordAyahNumber,
                            'copy_text' => trim((string) ($verseMeta['copy_text'] ?? '')),
                        ];
                        $currentSegmentJoiner = ((bool) $word['is_glyph']) ? '' : ' ';
                    }

                    $currentPairKey = $pairKey;
                    $currentSegmentTokens[] = $wordText;
                    if ($wordCopyText !== '') {
                        $currentSegmentCopyTokens[] = $wordCopyText;
                    }
                    $currentSegmentEndsAyah = $wordEndsAyah;
                }

                if ($currentSegmentMeta !== null && $currentSegmentTokens !== []) {
                    $segments[] = [
                        'verse_id' => (int) $currentSegmentMeta['verse_id'],
                        'ayah_index' => (int) $currentSegmentMeta['ayah_index'],
                        'surah_number' => (int) $currentSegmentMeta['surah_number'],
                        'ayah_number' => (int) $currentSegmentMeta['ayah_number'],
                        'text' => trim(implode($currentSegmentJoiner, $currentSegmentTokens)),
                        'copy_text' => trim(implode(' ', $currentSegmentCopyTokens)),
                        'ayah_copy_text' => trim((string) $currentSegmentMeta['copy_text']),
                        'ends_ayah' => $currentSegmentEndsAyah,
                    ];
                }

                $lineText = trim(implode(' ', array_map(static fn (array $segment): string => $segment['text'], $segments)));
            }

            if ($lineText === '' && $lineType === 'basmallah') {
                if ($words === []) {
                    $syntheticBasmallahWords = $this->resolveSyntheticBasmallahWords();
                    $lineSurahNumber = $lineRow->surah_number !== null ? (int) $lineRow->surah_number : null;
                    $words = array_map(
                        static fn (array $word): array => [
                            'verse_id' => 0,
                            'word_index' => (int) $word['word_index'],
                            'ayah_index' => 0,
                            'surah_number' => $lineSurahNumber ?? 0,
                            'ayah_number' => 0,
                            'text' => (string) $word['text'],
                            'copy_text' => (string) $word['text'],
                            'ayah_copy_text' => null,
                            'is_glyph' => (bool) $word['is_glyph'],
                            'ends_ayah' => false,
                        ],
                        $syntheticBasmallahWords,
                    );

                    if ($words !== []) {
                        $lineText = trim(implode('', array_map(static fn (array $word): string => (string) $word['text'], $words)));
                    }
                }

                if ($lineText === '') {
                    $lineText = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';
                }
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

        $lines = $this->injectSyntheticBasmallahAfterSurahHeaders($lines);
        $lines = $this->applyTargetedSurahHeaderCarryovers($lines);
        $lines = $this->stripRepeatedSurahPreludeFromContinuationPages($lines);

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
    private function injectSyntheticBasmallahAfterSurahHeaders(array $lines): array
    {
        if ($lines === []) {
            return $lines;
        }

        $result = [];
        $lineCount = count($lines);

        for ($lineIndex = 0; $lineIndex < $lineCount; $lineIndex++) {
            $line = $lines[$lineIndex];
            $result[] = $line;

            if ($line['line_type'] !== 'surah_name') {
                continue;
            }

            $surahNumber = (int) ($line['surah_number'] ?? 0);
            $nextLine = $lines[$lineIndex + 1] ?? null;

            if ($surahNumber === 1) {
                continue;
            }

            if (
                $surahNumber === 9 &&
                is_array($nextLine) &&
                $nextLine['line_type'] === 'basmallah' &&
                (int) ($nextLine['surah_number'] ?? $surahNumber) === 9
            ) {
                $lineIndex++;

                continue;
            }

            if ($surahNumber === 9) {
                continue;
            }

            if (is_array($nextLine) && $nextLine['line_type'] === 'basmallah') {
                continue;
            }

            $result[] = $this->buildSyntheticBasmallahLine($surahNumber > 0 ? $surahNumber : null);
        }

        return $this->normalizeLineNumbers($result);
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
    private function applyTargetedSurahHeaderCarryovers(array $lines): array
    {
        if ($lines === []) {
            return $lines;
        }

        $targetedSurahs = array_fill_keys(self::TARGETED_SURAH_HEADER_CARRYOVER_NUMBERS, true);
        $surahsWithAyahLines = [];

        foreach ($lines as $line) {
            if ($line['line_type'] !== 'ayah') {
                continue;
            }

            $surahNumber = $this->resolveLineSurahNumber($line);

            if ($surahNumber > 0) {
                $surahsWithAyahLines[$surahNumber] = true;
            }
        }

        $filteredLines = array_values(array_filter($lines, static function (array $line) use (
            $targetedSurahs,
            $surahsWithAyahLines
        ): bool {
            $surahNumber = (int) ($line['surah_number'] ?? 0);

            if (! isset($targetedSurahs[$surahNumber])) {
                return true;
            }

            if (! in_array($line['line_type'], ['surah_name', 'basmallah'], true)) {
                return true;
            }

            return isset($surahsWithAyahLines[$surahNumber]);
        }));

        $firstAyahSurahNumber = $this->firstAyahSurahNumberInLines($filteredLines);

        if ($firstAyahSurahNumber === null || ! isset($targetedSurahs[$firstAyahSurahNumber])) {
            return $this->normalizeLineNumbers($filteredLines);
        }

        if ($this->pageHasSurahHeader($filteredLines, $firstAyahSurahNumber)) {
            return $this->normalizeLineNumbers($filteredLines);
        }

        $trimmedLines = [];
        $passedLeadingPrelude = false;

        foreach ($filteredLines as $line) {
            if (
                ! $passedLeadingPrelude &&
                $line['line_type'] === 'basmallah' &&
                (int) ($line['surah_number'] ?? 0) === 0
            ) {
                continue;
            }

            if ($line['line_type'] === 'ayah') {
                $passedLeadingPrelude = true;
            }

            $trimmedLines[] = $line;
        }

        $prefixedLines = [
            $this->buildSyntheticSurahHeaderLine($firstAyahSurahNumber),
            $this->buildSyntheticBasmallahLine($firstAyahSurahNumber),
            ...$trimmedLines,
        ];

        return $this->normalizeLineNumbers($prefixedLines);
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
    private function stripRepeatedSurahPreludeFromContinuationPages(array $lines): array
    {
        if ($lines === []) {
            return $lines;
        }

        $firstAyahLineIndex = null;
        $firstAyahSurahNumber = 0;
        $firstAyahNumber = 0;

        foreach ($lines as $lineIndex => $line) {
            if ($line['line_type'] !== 'ayah') {
                continue;
            }

            $surahNumber = $this->resolveLineSurahNumber($line);
            $ayahNumber = $this->resolveLineAyahNumber($line);

            if ($surahNumber < 1 || $ayahNumber < 1) {
                continue;
            }

            $firstAyahLineIndex = $lineIndex;
            $firstAyahSurahNumber = $surahNumber;
            $firstAyahNumber = $ayahNumber;

            break;
        }

        if ($firstAyahLineIndex === null || $firstAyahSurahNumber < 1 || $firstAyahNumber <= 1) {
            return $this->normalizeLineNumbers($lines);
        }

        $trimmedLines = [];

        foreach ($lines as $lineIndex => $line) {
            $lineType = (string) $line['line_type'];

            if (
                $lineIndex < $firstAyahLineIndex &&
                in_array($lineType, ['surah_name', 'basmallah'], true)
            ) {
                $lineSurahNumber = $this->resolveLineSurahNumber($line);

                if ($lineType === 'surah_name' && $lineSurahNumber === $firstAyahSurahNumber) {
                    continue;
                }

                if (
                    $lineType === 'basmallah' &&
                    ($lineSurahNumber === 0 || $lineSurahNumber === $firstAyahSurahNumber)
                ) {
                    continue;
                }
            }

            $trimmedLines[] = $line;
        }

        return $this->normalizeLineNumbers($trimmedLines);
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
     */
    private function firstAyahSurahNumberInLines(array $lines): ?int
    {
        foreach ($lines as $line) {
            if ($line['line_type'] !== 'ayah') {
                continue;
            }

            $surahNumber = $this->resolveLineSurahNumber($line);

            if ($surahNumber > 0) {
                return $surahNumber;
            }
        }

        return null;
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
     */
    private function pageHasSurahHeader(array $lines, int $surahNumber): bool
    {
        foreach ($lines as $line) {
            if ($line['line_type'] !== 'surah_name') {
                continue;
            }

            if ((int) ($line['surah_number'] ?? 0) === $surahNumber) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{
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
     * } $line
     */
    private function resolveLineSurahNumber(array $line): int
    {
        $lineSurahNumber = (int) ($line['surah_number'] ?? 0);

        if ($lineSurahNumber > 0) {
            return $lineSurahNumber;
        }

        $segments = $line['segments'];

        if ($segments !== []) {
            $segmentSurahNumber = (int) ($segments[0]['surah_number'] ?? 0);

            if ($segmentSurahNumber > 0) {
                return $segmentSurahNumber;
            }
        }

        $words = $line['words'];

        if ($words !== []) {
            $wordSurahNumber = (int) ($words[0]['surah_number'] ?? 0);

            if ($wordSurahNumber > 0) {
                return $wordSurahNumber;
            }
        }

        return 0;
    }

    /**
     * @param  array{
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
     * } $line
     */
    private function resolveLineAyahNumber(array $line): int
    {
        $segments = $line['segments'];

        if ($segments !== []) {
            $segmentAyahNumber = (int) ($segments[0]['ayah_number'] ?? 0);

            if ($segmentAyahNumber > 0) {
                return $segmentAyahNumber;
            }
        }

        $words = $line['words'];

        if ($words !== []) {
            $wordAyahNumber = (int) ($words[0]['ayah_number'] ?? 0);

            if ($wordAyahNumber > 0) {
                return $wordAyahNumber;
            }
        }

        return 0;
    }

    /**
     * @return array{
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
     * }
     */
    private function buildSyntheticSurahHeaderLine(int $surahNumber): array
    {
        return [
            'line_number' => 0,
            'line_type' => 'surah_name',
            'is_centered' => true,
            'surah_number' => $surahNumber > 0 ? $surahNumber : null,
            'segments' => [],
            'words' => [],
            'text' => $this->formatSurahTitle($surahNumber),
        ];
    }

    /**
     * @return array{
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
     * }
     */
    private function buildSyntheticBasmallahLine(?int $surahNumber): array
    {
        $syntheticWords = $this->resolveSyntheticBasmallahWords();
        $normalizedSurahNumber = (int) ($surahNumber ?? 0);
        $words = array_map(
            static fn (array $word): array => [
                'verse_id' => 0,
                'word_index' => (int) $word['word_index'],
                'ayah_index' => 0,
                'surah_number' => $normalizedSurahNumber,
                'ayah_number' => 0,
                'text' => (string) $word['text'],
                'is_glyph' => (bool) $word['is_glyph'],
                'ends_ayah' => false,
            ],
            $syntheticWords,
        );

        return [
            'line_number' => 0,
            'line_type' => 'basmallah',
            'is_centered' => true,
            'surah_number' => $normalizedSurahNumber > 0 ? $normalizedSurahNumber : null,
            'segments' => [],
            'words' => $words,
            'text' => $this->syntheticBasmallahText($syntheticWords),
        ];
    }

    /**
     * @param  array<int, array{
     *     word_index: int,
     *     text: string,
     *     is_glyph: bool
     * }>  $words
     */
    private function syntheticBasmallahText(array $words): string
    {
        if ($words === []) {
            return 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';
        }

        $usesWordSpacing = false;

        foreach ($words as $word) {
            if (! ((bool) $word['is_glyph'])) {
                $usesWordSpacing = true;

                break;
            }
        }

        $joiner = $usesWordSpacing ? ' ' : '';

        return trim(implode($joiner, array_map(static fn (array $word): string => (string) $word['text'], $words)));
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
    private function normalizeLineNumbers(array $lines): array
    {
        return array_map(
            static fn (array $line, int $index): array => [
                ...$line,
                'line_number' => $index + 1,
            ],
            $lines,
            array_keys($lines),
        );
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

            $fontExtension = $fontFormat === 'truetype' ? 'ttf' : 'woff2';
            $publishedAssetRelativePath = 'qpc-v2/p'.$pageNumber.'.'.$fontExtension;

            $publishedAssetUrl = $this->resolvePublishedQuranVendorAssetUrl($publishedAssetRelativePath);
            if ($publishedAssetUrl !== null) {
                return [
                    'family' => 'QpcPage'.$pageNumber,
                    'url' => $publishedAssetUrl,
                    'format' => $fontFormat,
                ];
            }

            if ($this->ensurePublishedQuranVendorAsset($fontPath, $publishedAssetRelativePath)) {
                $materializedAssetUrl = $this->resolvePublishedQuranVendorAssetUrl($publishedAssetRelativePath);

                if ($materializedAssetUrl !== null) {
                    return [
                        'family' => 'QpcPage'.$pageNumber,
                        'url' => $materializedAssetUrl,
                        'format' => $fontFormat,
                    ];
                }
            }

            return [
                'family' => 'QpcPage'.$pageNumber,
                'url' => route('qpc-v2-font', ['page' => $pageNumber], false),
                'format' => $fontFormat,
            ];
        }

        return null;
    }

    /**
     * @return array{family: string, url: string, format: string}|null
     */
    private function resolveSurahHeaderFont(): ?array
    {
        $fontConfig = config('arabicable.quran_fonts.surah_headers', [
            'family' => 'SurahNameV4',
            'filename' => 'surah-name-v4.ttf',
            'format' => 'ttf',
        ]);

        if (! is_array($fontConfig)) {
            return null;
        }

        $filename = trim((string) ($fontConfig['filename'] ?? ''));
        $family = trim((string) ($fontConfig['family'] ?? ''));
        $format = trim((string) ($fontConfig['format'] ?? 'woff2'));
        $configuredSurahHeadersDir = trim((string) config(
            'arabicable.data_sources.quran_surah_headers_fonts_dir',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/surah-headers'),
        ));
        $configuredFontsDir = trim((string) config(
            'arabicable.data_sources.quran_fonts_dir',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts'),
        ));

        if ($filename === '' || $family === '') {
            return null;
        }

        $publishedAssetUrl = $this->resolvePublishedQuranVendorAssetUrl($filename);
        if ($publishedAssetUrl !== null) {
            return [
                'family' => $family,
                'url' => $publishedAssetUrl,
                'format' => in_array($format, ['ttf', 'truetype'], true) ? 'truetype' : 'woff2',
            ];
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

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '' || ! is_file($path)) {
                continue;
            }

            return [
                'family' => $family,
                'url' => route('quran-surah-header-font', [], false),
                'format' => in_array($format, ['ttf', 'truetype'], true) ? 'truetype' : 'woff2',
            ];
        }

        return null;
    }

    /**
     * @return array{family: string|null, url: string|null, format: string|null, text: string|null}|null
     */
    private function resolveBasmallahFont(): ?array
    {
        $fontConfig = config('arabicable.quran_fonts.basmalah', []);

        if (! is_array($fontConfig)) {
            return null;
        }

        $availableFonts = $fontConfig['available'] ?? [];
        $preferredKey = trim((string) ($fontConfig['preferred'] ?? ''));

        if (! is_array($availableFonts) || $availableFonts === []) {
            return null;
        }

        $fontOptions = [];

        if ($preferredKey !== '' && isset($availableFonts[$preferredKey]) && is_array($availableFonts[$preferredKey])) {
            $fontOptions[$preferredKey] = $availableFonts[$preferredKey];
        }

        foreach ($availableFonts as $key => $availableFont) {
            if ($key === $preferredKey || ! is_array($availableFont)) {
                continue;
            }

            $fontOptions[$key] = $availableFont;
        }

        foreach ($fontOptions as $fontKey => $fontOption) {
            $resolvedFont = $this->resolveConfiguredQuranFont(
                $fontOption,
                'quran-basmallah-font',
                ['fontKey' => $fontKey],
            );

            if ($resolvedFont !== null) {
                return $resolvedFont;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $fontConfig
     * @param  array<string, string>  $routeParameters
     * @return array{family: string|null, url: string|null, format: string|null, text: string|null}|null
     */
    private function resolveConfiguredQuranFont(
        array $fontConfig,
        string $routeName,
        array $routeParameters = [],
    ): ?array {
        $family = trim((string) ($fontConfig['family'] ?? ''));
        $filename = trim((string) ($fontConfig['filename'] ?? ''));
        $format = trim((string) ($fontConfig['format'] ?? 'woff2'));
        $text = trim((string) ($fontConfig['glyph'] ?? $fontConfig['text'] ?? ''));

        if ($family === '') {
            return null;
        }

        if ($filename === '') {
            return [
                'family' => $family,
                'url' => null,
                'format' => null,
                'text' => $text !== '' ? $text : null,
            ];
        }

        $publishedAssetUrl = $this->resolvePublishedQuranVendorAssetUrl($filename);
        if ($publishedAssetUrl !== null) {
            return [
                'family' => $family,
                'url' => $publishedAssetUrl,
                'format' => in_array($format, ['ttf', 'truetype'], true) ? 'truetype' : 'woff2',
                'text' => $text !== '' ? $text : null,
            ];
        }

        $configuredSurahHeadersDir = trim((string) config(
            'arabicable.data_sources.quran_surah_headers_fonts_dir',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts/surah-headers'),
        ));
        $configuredFontsDir = trim((string) config(
            'arabicable.data_sources.quran_fonts_dir',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/fonts'),
        ));

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

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '' || ! is_file($path)) {
                continue;
            }

            return [
                'family' => $family,
                'url' => route($routeName, $routeParameters, false),
                'format' => in_array($format, ['ttf', 'truetype'], true) ? 'truetype' : 'woff2',
                'text' => $text !== '' ? $text : null,
            ];
        }

        return null;
    }

    private function resolvePublishedQuranVendorAssetUrl(string $relativePath): ?string
    {
        $normalizedRelativePath = $this->normalizeQuranVendorAssetRelativePath($relativePath);

        if ($normalizedRelativePath === null) {
            return null;
        }

        $publicAssetPath = public_path('vendor/arabicable/'.$normalizedRelativePath);

        if (! is_file($publicAssetPath)) {
            return null;
        }

        if (is_platform('android')) {
            return '/vendor/arabicable/'.$normalizedRelativePath;
        }

        return '/vendor/arabicable/'.$normalizedRelativePath;
    }

    private function ensurePublishedQuranVendorAsset(string $sourcePath, string $relativePath): bool
    {
        if (! is_platform('native') || ! is_file($sourcePath)) {
            return false;
        }

        $normalizedRelativePath = $this->normalizeQuranVendorAssetRelativePath($relativePath);

        if ($normalizedRelativePath === null) {
            return false;
        }

        $publicAssetPath = public_path('vendor/arabicable/'.$normalizedRelativePath);

        if (is_file($publicAssetPath)) {
            return true;
        }

        $publicAssetDirectory = dirname($publicAssetPath);

        if (! is_dir($publicAssetDirectory) && ! @mkdir($publicAssetDirectory, 0755, true) && ! is_dir($publicAssetDirectory)) {
            return false;
        }

        return @copy($sourcePath, $publicAssetPath);
    }

    private function normalizeQuranVendorAssetRelativePath(string $relativePath): ?string
    {
        $normalizedRelativePath = trim($relativePath, " \t\n\r\0\x0B/");

        if ($normalizedRelativePath === '' || str_contains($normalizedRelativePath, '\\')) {
            return null;
        }

        $segments = explode('/', $normalizedRelativePath);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        return implode('/', $segments);
    }

    private function basmallahConfigFingerprint(): string
    {
        $fontConfig = config('arabicable.quran_fonts.basmalah', []);

        if (! is_array($fontConfig)) {
            return 'none';
        }

        try {
            return sha1(json_encode($fontConfig, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } catch (\JsonException) {
            return 'invalid';
        }
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

        return QpcWordsDatabase::resolveFirstValidPath($candidates);
    }

    /**
     * @return array<int, array{
     *     word_index: int,
     *     text: string,
     *     is_glyph: bool
     * }>
     */
    private function resolveSyntheticBasmallahWords(): array
    {
        static $cached = null;

        if (is_array($cached)) {
            return $cached;
        }

        if (Schema::hasTable('quran_words')) {
            $rows = DB::table('quran_words')
                ->select(['global_word_index', 'token_uthmani', 'token_searchable_typed'])
                ->where('surah_number', 1)
                ->where('ayah_number', 1)
                ->orderBy('global_word_index')
                ->get();

            if ($rows->count() > 0) {
                $cached = $rows
                    ->map(static fn (object $row): array => [
                        'word_index' => (int) ($row->global_word_index ?? 0),
                        'text' => trim((string) ($row->token_uthmani ?? '')),
                        'is_glyph' => false,
                    ])
                    ->filter(static fn (array $word): bool => $word['word_index'] > 0 && $word['text'] !== '')
                    ->values()
                    ->all();

                if ($cached === []) {
                    $cached = $rows
                        ->map(static fn (object $row): array => [
                            'word_index' => (int) ($row->global_word_index ?? 0),
                            'text' => trim((string) ($row->token_searchable_typed ?? '')),
                            'is_glyph' => false,
                        ])
                        ->filter(static fn (array $word): bool => $word['word_index'] > 0 && $word['text'] !== '')
                        ->values()
                        ->all();
                }

                if ($cached !== []) {
                    return $cached;
                }
            }
        }

        $databasePath = $this->resolveQpcDisplayWordsDatabasePath();

        if ($databasePath !== null) {
            $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
            $statement = $database->prepare('SELECT id, text FROM words WHERE surah = 1 AND ayah = 1 ORDER BY id');

            if ($statement instanceof \SQLite3Stmt) {
                $result = $statement->execute();

                if ($result instanceof \SQLite3Result) {
                    $words = [];

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

                        $words[] = [
                            'word_index' => $wordIndex,
                            'text' => $wordText,
                            'is_glyph' => true,
                        ];
                    }

                    $result->finalize();
                    $statement->close();
                    $database->close();

                    if ($words !== []) {
                        $cached = $words;

                        return $cached;
                    }
                } else {
                    $statement->close();
                    $database->close();
                }
            } else {
                $database->close();
            }
        }

        $cached = [];

        return $cached;
    }
}
