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
use Illuminate\Support\Facades\Schema;

class QuranReaderDataService
{
    private const SEARCH_INTERSECTION_CANDIDATE_LIMIT = 7000;

    private const READY_CACHE_KEY = 'quran-reader-ready-v2';

    private const MAX_PAGE_CACHE_KEY = 'quran-reader-max-page-v2';

    private const SEARCH_RESULTS_CACHE_PREFIX = 'quran-reader-search-results-v1';

    private const DISPLAYED_PAGE_CACHE_PREFIX = 'quran-reader-display-page-v1';

    /**
     * @var array<int, string>
     */
    private const SEARCH_PROGRESS_STAGE_ORDER = [
        'exact_phrase',
        'exact_tokens',
        'stem_tokens',
        'root_tokens',
        'word_prefix',
    ];

    /**
     * @phpstan-impure
     */
    public function isReady(): bool
    {
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
            'quran-reader-page-v19:%d:%s',
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
    public function searchProgressively(string $query, int $limit = 24, ?callable $onProgress = null): array
    {
        if (! $this->isReady()) {
            return [];
        }

        $normalizedQuery = trim($this->normalizeQuranSearchQuery($query));

        if ($normalizedQuery === '' || mb_strlen($normalizedQuery) < 2) {
            return [];
        }

        $resolvedLimit = max(1, min(60, $limit));
        $hasTypedWordColumn = $this->hasQuranWordColumn('token_searchable_typed');
        $cacheKey = sprintf(
            '%s:%d:%d:%s',
            self::SEARCH_RESULTS_CACHE_PREFIX,
            $resolvedLimit,
            $hasTypedWordColumn ? 1 : 0,
            sha1($normalizedQuery),
        );

        $cachedMatches = Cache::memo()->get($cacheKey);

        if (is_array($cachedMatches)) {
            $this->emitProgressFromResolvedMatches($onProgress, $cachedMatches);

            return $cachedMatches;
        }

        $resolvedMatches = $this->buildSearchMatches(
            $normalizedQuery,
            $resolvedLimit,
            $hasTypedWordColumn,
            $onProgress,
        );

        Cache::memo()->put($cacheKey, $resolvedMatches, now()->addHours(12));

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
        $exactPhraseVerseIds = $this->collectVerseIdsByExactPhrase($searchQuery, $limit);

        $this->appendVerseMatches(
            $matches,
            $seenAyahIndexes,
            $exactPhraseVerseIds,
            $limit,
            $searchQuery,
            'exact_phrase',
        );
        $this->emitSearchProgress($onProgress, $matches, 'exact_phrase');

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        $exactTokenVerseIds = $this->collectVerseIdsByExactTokens($tokens, $limit);
        $this->appendVerseMatches(
            $matches,
            $seenAyahIndexes,
            $exactTokenVerseIds,
            $limit,
            $searchQuery,
            'exact_tokens',
        );
        $this->emitSearchProgress($onProgress, $matches, 'exact_tokens');

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        $shouldUseExpandedRoots = count($tokens) <= 6;
        $shouldUseRootStage = count($tokens) <= 4;

        if ($shouldUseExpandedRoots) {
            $stemVerseIds = $this->collectVerseIdsByStemTokens($tokens, $limit);
            $this->appendVerseMatches(
                $matches,
                $seenAyahIndexes,
                $stemVerseIds,
                $limit,
                $searchQuery,
                'stem_tokens',
            );
            $this->emitSearchProgress($onProgress, $matches, 'stem_tokens');
        }

        if (count($matches) >= $limit) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        if ($shouldUseExpandedRoots && $shouldUseRootStage) {
            $rootVerseIds = $this->collectVerseIdsByRootTokens($tokens, $limit);
            $this->appendVerseMatches(
                $matches,
                $seenAyahIndexes,
                $rootVerseIds,
                $limit,
                $searchQuery,
                'root_tokens',
            );
            $this->emitSearchProgress($onProgress, $matches, 'root_tokens');
        }

        if (count($matches) >= $limit || ! $hasTypedWordColumn) {
            $this->emitSearchProgress($onProgress, $matches, 'complete', true);

            return $matches;
        }

        $wordLikeVerseIds = $this->collectVerseIdsByWordLikeFallback($searchQuery, $limit);
        $this->appendVerseMatches(
            $matches,
            $seenAyahIndexes,
            $wordLikeVerseIds,
            $limit,
            $searchQuery,
            'word_prefix',
        );
        $this->emitSearchProgress($onProgress, $matches, 'word_prefix');
        $this->emitSearchProgress($onProgress, $matches, 'complete', true);

        return $matches;
    }

    /**
     * @return array<int, int>
     */
    private function collectVerseIdsByExactPhrase(string $searchQuery, int $limit): array
    {
        $queryVariants = $this->expandStrictExactPhraseVariants($searchQuery);

        if ($queryVariants === []) {
            return [];
        }

        return DB::table('quran_verses')
            ->where(function (Builder $whereBuilder) use ($queryVariants): void {
                foreach ($queryVariants as $variant) {
                    $whereBuilder
                        ->orWhere('text_searchable_typed', $variant)
                        ->orWhere('text_searchable_typed', 'like', $variant.' %')
                        ->orWhere('text_searchable_typed', 'like', '% '.$variant)
                        ->orWhere('text_searchable_typed', 'like', '% '.$variant.' %')
                        ->orWhere('text_searchable', $variant)
                        ->orWhere('text_searchable', 'like', $variant.' %')
                        ->orWhere('text_searchable', 'like', '% '.$variant)
                        ->orWhere('text_searchable', 'like', '% '.$variant.' %');
                }
            })
            ->orderBy('ayah_index')
            ->limit($limit * 6)
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
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
     * @param  array<int, string>  $tokens
     * @return array<int, int>
     */
    private function collectVerseIdsByExactTokens(array $tokens, int $limit): array
    {
        $candidateColumns = $this->resolveTokenSearchColumns();

        if ($candidateColumns === []) {
            return [];
        }

        return $this->intersectVerseIdSets(
            array_map(function (string $token) use ($candidateColumns): array {
                $tokenVariants = $this->expandSearchTextVariants($token);

                if ($tokenVariants === []) {
                    return [];
                }

                return DB::table('quran_words')
                    ->selectRaw('verse_id, MIN(ayah_index) AS ayah_index')
                    ->where(function (Builder $builder) use ($candidateColumns, $tokenVariants): void {
                        foreach ($candidateColumns as $column) {
                            $builder->orWhereIn($column, $tokenVariants);
                        }
                    })
                    ->groupBy('verse_id')
                    ->orderBy('ayah_index')
                    ->orderBy('verse_id')
                    ->limit(self::SEARCH_INTERSECTION_CANDIDATE_LIMIT)
                    ->pluck('verse_id')
                    ->map(static fn (mixed $value): int => (int) $value)
                    ->all();
            }, $tokens),
            $limit,
        );
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, int>
     */
    private function collectVerseIdsByStemTokens(array $tokens, int $limit): array
    {
        $hasStemColumn = $this->hasQuranWordColumn('token_stem');
        $searchColumns = $this->resolveTokenSearchColumns();

        if (! $hasStemColumn && $searchColumns === []) {
            return [];
        }

        $verseIdSets = [];

        foreach ($tokens as $token) {
            $stemCandidates = $this->resolveStemCandidatesForToken($token);
            $tokenVariants = $this->expandSearchTextVariants($token);

            if ($stemCandidates === [] && $tokenVariants === []) {
                return [];
            }

            $verseIdSets[] = DB::table('quran_words')
                ->selectRaw('verse_id, MIN(ayah_index) AS ayah_index')
                ->where(function (Builder $builder) use (
                    $hasStemColumn,
                    $searchColumns,
                    $stemCandidates,
                    $tokenVariants
                ): void {
                    if ($hasStemColumn && $stemCandidates !== []) {
                        $builder->orWhereIn('token_stem', $stemCandidates);
                    }

                    if ($tokenVariants !== []) {
                        foreach ($searchColumns as $column) {
                            $builder->orWhereIn($column, $tokenVariants);
                        }
                    }
                })
                ->groupBy('verse_id')
                ->orderBy('ayah_index')
                ->orderBy('verse_id')
                ->limit(self::SEARCH_INTERSECTION_CANDIDATE_LIMIT)
                ->pluck('verse_id')
                ->map(static fn (mixed $value): int => (int) $value)
                ->all();
        }

        return $this->intersectVerseIdSets($verseIdSets, $limit);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, int>
     */
    private function collectVerseIdsByRootTokens(array $tokens, int $limit): array
    {
        $hasRootColumn = $this->hasQuranWordColumn('token_root');
        $searchColumns = $this->resolveTokenSearchColumns();

        if (! $hasRootColumn && $searchColumns === []) {
            return [];
        }

        $verseIdSets = [];

        foreach ($tokens as $token) {
            $rootCandidates = $this->resolveRootCandidatesForToken($token);
            $tokenVariants = $this->expandSearchTextVariants($token);

            if ($rootCandidates === [] && $tokenVariants === []) {
                return [];
            }

            $verseIdSets[] = DB::table('quran_words')
                ->selectRaw('verse_id, MIN(ayah_index) AS ayah_index')
                ->where(function (Builder $builder) use (
                    $hasRootColumn,
                    $searchColumns,
                    $rootCandidates,
                    $tokenVariants
                ): void {
                    if ($hasRootColumn && $rootCandidates !== []) {
                        $builder->orWhereIn('token_root', $rootCandidates);
                    }

                    if ($tokenVariants !== []) {
                        foreach ($searchColumns as $column) {
                            $builder->orWhereIn($column, $tokenVariants);
                        }
                    }
                })
                ->groupBy('verse_id')
                ->orderBy('ayah_index')
                ->orderBy('verse_id')
                ->limit(self::SEARCH_INTERSECTION_CANDIDATE_LIMIT)
                ->pluck('verse_id')
                ->map(static fn (mixed $value): int => (int) $value)
                ->all();
        }

        return $this->intersectVerseIdSets($verseIdSets, $limit);
    }

    /**
     * @param  array<int, array<int, int>>  $verseIdSets
     * @return array<int, int>
     */
    private function intersectVerseIdSets(array $verseIdSets, int $limit): array
    {
        if ($verseIdSets === []) {
            return [];
        }

        $intersection = null;

        foreach ($verseIdSets as $set) {
            $normalizedSet = array_values(array_unique(array_map(
                static fn (int $value): int => (int) $value,
                $set,
            )));

            if ($normalizedSet === []) {
                return [];
            }

            if ($intersection === null) {
                $intersection = $normalizedSet;

                continue;
            }

            $intersection = array_values(array_intersect($intersection, $normalizedSet));

            if ($intersection === []) {
                return [];
            }
        }

        return DB::table('quran_verses')
            ->whereIn('id', $intersection)
            ->orderBy('ayah_index')
            ->limit($limit * 6)
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolveStemCandidatesForToken(string $token): array
    {
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
    ): void {
        if ($verseIds === [] || count($matches) >= $limit) {
            return;
        }

        $matchMeta = $this->resolveSearchMatchMeta($matchStrategy);
        $rows = DB::table('quran_verses')
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
            ->orderBy('ayah_index')
            ->get();

        foreach ($rows as $row) {
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

            $matches[] = [
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

            if (count($matches) >= $limit) {
                return;
            }
        }
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

            $accumulatedMatches = [...$accumulatedMatches, ...$stageMatches];
            $this->emitSearchProgress($onProgress, $accumulatedMatches, $stage);
            $didEmitProgress = true;
        }

        $fallbackMatches = $this->collectStageMatches($matches, '', $seenVerseIds);

        if ($fallbackMatches !== []) {
            $accumulatedMatches = [...$accumulatedMatches, ...$fallbackMatches];
            $this->emitSearchProgress($onProgress, $accumulatedMatches, 'fallback');
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
            'exact_phrase' => [
                'tone' => 'success',
                'shade' => 500,
                'label' => 'مطابقة تامة',
                'rank' => 1,
            ],
            'exact_tokens' => [
                'tone' => 'warning',
                'shade' => 500,
                'label' => 'مطابقة كلمات',
                'rank' => 2,
            ],
            'stem_tokens' => [
                'tone' => 'info',
                'shade' => 500,
                'label' => 'مطابقة صرفية',
                'rank' => 3,
            ],
            'root_tokens' => [
                'tone' => 'danger',
                'shade' => 500,
                'label' => 'مطابقة جذرية',
                'rank' => 4,
            ],
            'word_prefix' => [
                'tone' => 'danger',
                'shade' => 500,
                'label' => 'مطابقة تقريبية',
                'rank' => 5,
            ],
            default => [
                'tone' => 'warning',
                'shade' => 500,
                'label' => 'مطابقة',
                'rank' => 6,
            ],
        };
    }

    private function normalizeQuranSearchQuery(string $text): string
    {
        return QuranSearchText::normalizeQuery($text);
    }

    /**
     * @return array<int, string>
     */
    private function expandSearchTextVariants(string $text): array
    {
        return QuranSearchText::expandVariants($text);
    }

    /**
     * @return array<int, string>
     */
    private function expandStrictExactPhraseVariants(string $text): array
    {
        return QuranSearchText::expandStrictExactPhraseVariants($text);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function prepareSearchTokens(array $tokens): array
    {
        return QuranSearchText::prepareTokens($tokens);
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
        $cachedPage = (int) Cache::memo()->remember(
            $cacheKey,
            now()->addDays(30),
            fn (): int => $this->resolveMushafPageFromQpcWordsUncached($surahNumber, $ayahNumber) ?? 0,
        );

        return $cachedPage > 0 ? $cachedPage : null;
    }

    private function resolveMushafPageFromQpcWordsUncached(int $surahNumber, int $ayahNumber): ?int
    {
        $databasePath = $this->resolveQpcDisplayWordsDatabasePath();

        if ($databasePath === null) {
            return null;
        }

        $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $statement = $database->prepare(
            'SELECT MIN(id) AS first_word_index FROM words WHERE surah = :surah AND ayah = :ayah',
        );

        if (! $statement instanceof \SQLite3Stmt) {
            $database->close();

            return null;
        }

        $statement->bindValue(':surah', $surahNumber, SQLITE3_INTEGER);
        $statement->bindValue(':ayah', $ayahNumber, SQLITE3_INTEGER);
        $result = $statement->execute();

        if (! $result instanceof \SQLite3Result) {
            $statement->close();
            $database->close();

            return null;
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);
        $firstWordIndex = is_array($row) ? (int) ($row['first_word_index'] ?? 0) : 0;

        $result->finalize();
        $statement->close();
        $database->close();

        if ($firstWordIndex < 1) {
            return null;
        }

        $pageNumber = DB::table('quran_mushaf_lines')
            ->whereNotNull('first_word_index')
            ->whereNotNull('last_word_index')
            ->where('first_word_index', '<=', $firstWordIndex)
            ->where('last_word_index', '>=', $firstWordIndex)
            ->orderBy('page_number')
            ->value('page_number');

        return is_numeric($pageNumber) ? (int) $pageNumber : null;
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

            return [
                'family' => 'QpcPage'.$pageNumber,
                'url' => route('qpc-v2-font', ['page' => $pageNumber]),
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
                'url' => route('quran-surah-header-font'),
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
                'url' => route($routeName, $routeParameters),
                'format' => in_array($format, ['ttf', 'truetype'], true) ? 'truetype' : 'woff2',
                'text' => $text !== '' ? $text : null,
            ];
        }

        return null;
    }

    private function resolvePublishedQuranVendorAssetUrl(string $filename): ?string
    {
        $normalizedFilename = trim($filename);

        if (
            $normalizedFilename === ''
            || str_contains($normalizedFilename, '/')
            || str_contains($normalizedFilename, '\\')
        ) {
            return null;
        }

        $publicAssetPath = public_path('vendor/arabicable/'.$normalizedFilename);

        if (! is_file($publicAssetPath)) {
            return null;
        }

        return url('/vendor/arabicable/'.$normalizedFilename);
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
