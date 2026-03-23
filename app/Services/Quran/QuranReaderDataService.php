<?php

declare(strict_types=1);

namespace App\Services\Quran;

use GoodMaven\Arabicable\Facades\ArabicFilter;
use Illuminate\Database\Query\Builder;
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
                'surahHeaderFontFamily' => null,
                'surahHeaderFontUrl' => null,
                'surahHeaderFontFormat' => null,
                'useCenteredAyahLayout' => true,
            ];
        }

        $maxPage = $this->maxPage();
        $normalizedPage = $maxPage > 0 ? max(1, min($pageNumber, $maxPage)) : 1;
        $cacheKey = 'quran-reader-page-v3:'.$normalizedPage;

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
     *     search_snippet: string
     * }>
     */
    public function search(string $query, int $limit = 24): array
    {
        if (! $this->isReady()) {
            return [];
        }

        $normalizedQuery = trim($this->normalizeQuranSearchQuery($query));

        if ($normalizedQuery === '' || mb_strlen($normalizedQuery) < 2) {
            return [];
        }

        $resolvedLimit = max(1, min(60, $limit));
        $hasTypedWordColumn = Schema::hasTable('quran_words')
            && Schema::hasColumn('quran_words', 'token_searchable_typed');

        return $this->buildSearchMatches($normalizedQuery, $resolvedLimit, $hasTypedWordColumn);
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
        $resolved = Cache::remember('quran-reader-surah-directory-v1', now()->addDays(30), function (): array {
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
                $directory[] = [
                    'surah_number' => $surahNumber,
                    'page_number' => (int) ($firstPageBySurah[$surahNumber] ?? 1),
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
     *     search_snippet: string
     * }>
     */
    private function buildSearchMatches(string $searchQuery, int $limit, bool $hasTypedWordColumn): array
    {
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

        $this->appendVerseMatches($matches, $seenAyahIndexes, $exactPhraseVerseIds, $limit, $searchQuery);

        if (count($matches) >= $limit) {
            return $matches;
        }

        $exactTokenVerseIds = $this->collectVerseIdsByExactTokens($tokens, $limit);
        $this->appendVerseMatches($matches, $seenAyahIndexes, $exactTokenVerseIds, $limit, $searchQuery);

        if (count($matches) >= $limit) {
            return $matches;
        }

        $shouldUseExpandedRoots = count($tokens) <= 6;
        $shouldUseRootStage = count($tokens) <= 4;

        if ($shouldUseExpandedRoots) {
            $stemVerseIds = $this->collectVerseIdsByStemTokens($tokens, $limit);
            $this->appendVerseMatches($matches, $seenAyahIndexes, $stemVerseIds, $limit, $searchQuery);
        }

        if (count($matches) >= $limit) {
            return $matches;
        }

        if ($shouldUseExpandedRoots && $shouldUseRootStage) {
            $rootVerseIds = $this->collectVerseIdsByRootTokens($tokens, $limit);
            $this->appendVerseMatches($matches, $seenAyahIndexes, $rootVerseIds, $limit, $searchQuery);
        }

        if (count($matches) >= $limit || ! $hasTypedWordColumn) {
            return $matches;
        }

        $wordLikeVerseIds = $this->collectVerseIdsByWordLikeFallback($searchQuery, $limit);
        $this->appendVerseMatches($matches, $seenAyahIndexes, $wordLikeVerseIds, $limit, $searchQuery);

        return $matches;
    }

    /**
     * @return array<int, int>
     */
    private function collectVerseIdsByExactPhrase(string $searchQuery, int $limit): array
    {
        $queryVariants = $this->expandSearchTextVariants($searchQuery);

        if ($queryVariants === []) {
            return [];
        }

        return DB::table('quran_verses')
            ->where(function (Builder $whereBuilder) use ($queryVariants): void {
                foreach ($queryVariants as $variant) {
                    $this->addBoundedPhraseConditions($whereBuilder, 'text_searchable_typed', $variant);
                    $this->addBoundedPhraseConditions($whereBuilder, 'text_searchable', $variant);
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
        $candidateColumns = array_values(array_filter([
            $this->hasQuranWordColumn('token_searchable_typed') ? 'token_searchable_typed' : null,
            $this->hasQuranWordColumn('token_searchable') ? 'token_searchable' : null,
            $this->hasQuranWordColumn('token_lemma') ? 'token_lemma' : null,
        ]));

        if ($candidateColumns === []) {
            return [];
        }

        return $this->intersectVerseIdSets(
            array_map(function (string $token) use ($candidateColumns, $limit): array {
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
                    ->limit($limit * 18)
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
        if (! $this->hasQuranWordColumn('token_stem')) {
            return [];
        }

        $verseIdSets = [];

        foreach ($tokens as $token) {
            $stemCandidates = $this->resolveStemCandidatesForToken($token);

            if ($stemCandidates === []) {
                return [];
            }

            $verseIdSets[] = DB::table('quran_words')
                ->selectRaw('verse_id, MIN(ayah_index) AS ayah_index')
                ->whereIn('token_stem', $stemCandidates)
                ->groupBy('verse_id')
                ->orderBy('ayah_index')
                ->orderBy('verse_id')
                ->limit($limit * 18)
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
        if (! $this->hasQuranWordColumn('token_root')) {
            return [];
        }

        $verseIdSets = [];

        foreach ($tokens as $token) {
            $rootCandidates = $this->resolveRootCandidatesForToken($token);

            if ($rootCandidates === []) {
                return [];
            }

            $verseIdSets[] = DB::table('quran_words')
                ->selectRaw('verse_id, MIN(ayah_index) AS ayah_index')
                ->whereIn('token_root', $rootCandidates)
                ->groupBy('verse_id')
                ->orderBy('ayah_index')
                ->orderBy('verse_id')
                ->limit($limit * 16)
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
        $searchColumns = array_values(array_filter([
            $this->hasQuranWordColumn('token_searchable_typed') ? 'token_searchable_typed' : null,
            $this->hasQuranWordColumn('token_lemma') ? 'token_lemma' : null,
            $this->hasQuranWordColumn('token_searchable') ? 'token_searchable' : null,
        ]));

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
        $searchColumns = array_values(array_filter([
            $this->hasQuranWordColumn('token_searchable_typed') ? 'token_searchable_typed' : null,
            $this->hasQuranWordColumn('token_lemma') ? 'token_lemma' : null,
            $this->hasQuranWordColumn('token_searchable') ? 'token_searchable' : null,
        ]));

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
     *     search_snippet: string
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
    ): void {
        if ($verseIds === [] || count($matches) >= $limit) {
            return;
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
            ];

            if (count($matches) >= $limit) {
                return;
            }
        }
    }

    private function normalizeQuranSearchQuery(string $text): string
    {
        $prepared = strtr($text, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ٲ' => 'ا',
            'ٳ' => 'ا',
            'ٵ' => 'ا',
            'ؤ' => 'و',
            'ئ' => 'ي',
            'ی' => 'ي',
            'ى' => 'ي',
            'ے' => 'ي',
            'ۍ' => 'ي',
            'ې' => 'ي',
            'ۑ' => 'ي',
            'ک' => 'ك',
        ]);

        $prepared = preg_replace('/([\p{Arabic}])\x{0670}/u', '$1ا', $prepared) ?? $prepared;
        $prepared = preg_replace('/\x{0670}/u', 'ا', $prepared) ?? $prepared;

        $normalized = ArabicFilter::forSearch($prepared);

        return strtr($normalized, [
            'الرحمان' => 'الرحمن',
            'رحمان' => 'رحمن',
            'الصلوة' => 'الصلاة',
            'صلوة' => 'صلاة',
            'الزكوة' => 'الزكاة',
            'زكوة' => 'زكاة',
            'الحيوة' => 'الحياة',
            'حيوة' => 'حياة',
            'الربوا' => 'الربا',
            'ربوا' => 'ربا',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function expandSearchTextVariants(string $text): array
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return [];
        }

        $withoutConjunctions = $this->stripLeadingConjunctionsFromPhrase($trimmed);
        $collapsedVocative = $this->collapseVocativeSpacingInPhrase($trimmed);
        $withoutVocative = $this->stripVocativeParticlesFromPhrase($trimmed);
        $legacyOrthography = $this->normalizeLegacyOrthographyForSearch($trimmed);
        $legacyOrthographyWithoutConjunctions = $this->normalizeLegacyOrthographyForSearch(
            $withoutConjunctions,
        );
        $legacyOrthographyCollapsedVocative = $this->normalizeLegacyOrthographyForSearch(
            $collapsedVocative,
        );
        $legacyOrthographyWithoutVocative = $this->normalizeLegacyOrthographyForSearch(
            $withoutVocative,
        );
        $variants = [
            $trimmed,
            strtr($trimmed, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک']),
            strtr($trimmed, ['ی' => 'ي', 'ى' => 'ي', 'ک' => 'ك']),
            strtr($trimmed, ['الرحمن' => 'الرحمان', 'رحمن' => 'رحمان']),
            strtr($trimmed, ['الرحمان' => 'الرحمن', 'رحمان' => 'رحمن']),
            $withoutConjunctions,
            $collapsedVocative,
            $this->collapseVocativeSpacingInPhrase($withoutConjunctions),
            $withoutVocative,
            $this->stripVocativeParticlesFromPhrase($withoutConjunctions),
            $legacyOrthography,
            $legacyOrthographyWithoutConjunctions,
            $legacyOrthographyCollapsedVocative,
            $legacyOrthographyWithoutVocative,
            $this->normalizeQuestionVerbSpellingsInPhrase($trimmed),
            $this->normalizeQuestionVerbSpellingsInPhrase($withoutConjunctions),
        ];

        $normalized = [];

        foreach ($variants as $variant) {
            $value = trim((string) $variant);

            if ($value === '') {
                continue;
            }

            $normalized[$value] = true;
        }

        return array_keys($normalized);
    }

    private function normalizeQuestionVerbSpellingsInPhrase(string $text): string
    {
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];

        if ($tokens === []) {
            return '';
        }

        $normalized = [];

        foreach ($tokens as $token) {
            $normalized[] = $this->normalizeQuestionVerbToken($token);
        }

        return trim(implode(' ', $normalized));
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function prepareSearchTokens(array $tokens): array
    {
        $normalized = [];

        foreach ($tokens as $token) {
            $value = trim($token);

            if ($value === '') {
                continue;
            }

            if ($value === 'يا') {
                continue;
            }

            if (mb_strlen($value) < 2) {
                continue;
            }

            $normalized[$value] = true;
        }

        if ($normalized !== []) {
            return array_keys($normalized);
        }

        $fallback = [];

        foreach ($tokens as $token) {
            $value = trim($token);

            if ($value === '') {
                continue;
            }

            $fallback[$value] = true;
        }

        return array_keys($fallback);
    }

    private function normalizeQuestionVerbToken(string $token): string
    {
        $trimmed = trim($token);

        if ($trimmed === '') {
            return '';
        }

        $patterns = [
            '/^فاسال/u' => 'فسل',
            '/^فسال/u' => 'فسل',
            '/^واسال/u' => 'وسل',
            '/^وسال/u' => 'وسل',
            '/^اسال/u' => 'سل',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $trimmed) !== 1) {
                continue;
            }

            return preg_replace($pattern, $replacement, $trimmed) ?? $trimmed;
        }

        return $trimmed;
    }

    private function addBoundedPhraseConditions(Builder $builder, string $column, string $variant): void
    {
        $builder
            ->orWhere($column, $variant)
            ->orWhere($column, 'like', $variant.' %')
            ->orWhere($column, 'like', '% '.$variant)
            ->orWhere($column, 'like', '% '.$variant.' %');
    }

    private function addTokenPrefixConditions(Builder $builder, string $column, string $variant): void
    {
        $builder
            ->orWhere($column, $variant)
            ->orWhere($column, 'like', $variant.'%');
    }

    private function stripLeadingConjunctionsFromPhrase(string $text): string
    {
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];
        $normalized = [];

        foreach ($tokens as $token) {
            $normalized[] = $this->stripLeadingConjunction($token);
        }

        return trim(implode(' ', $normalized));
    }

    private function collapseVocativeSpacingInPhrase(string $text): string
    {
        return trim((string) (preg_replace('/(^|\s)يا\s+([\p{Arabic}]+)/u', '$1يا$2', trim($text)) ?? $text));
    }

    private function stripVocativeParticlesFromPhrase(string $text): string
    {
        return trim((string) (preg_replace('/(^|\s)يا\s+/u', '$1', trim($text)) ?? $text));
    }

    private function stripLeadingConjunction(string $token): string
    {
        $trimmed = trim($token);

        if (mb_strlen($trimmed) < 3) {
            return $trimmed;
        }

        if (preg_match('/^[وف][\p{Arabic}]/u', $trimmed) !== 1) {
            return $trimmed;
        }

        return mb_substr($trimmed, 1);
    }

    private function normalizeLegacyOrthographyForSearch(string $text): string
    {
        return strtr(trim($text), [
            'الصلاة' => 'الصلواة',
            'صلاة' => 'صلواة',
            'الزكاة' => 'الزكواة',
            'زكاة' => 'زكواة',
            'الحياة' => 'الحيوة',
            'حياة' => 'حيوة',
        ]);
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

        $syntheticWords = $this->resolveSyntheticBasmallahWords();
        $result = [];
        $lineCount = count($lines);

        for ($lineIndex = 0; $lineIndex < $lineCount; $lineIndex++) {
            $line = $lines[$lineIndex];
            $result[] = $line;

            if ($line['line_type'] !== 'surah_name') {
                continue;
            }

            $surahNumber = (int) ($line['surah_number'] ?? 0);

            if ($surahNumber === 1) {
                continue;
            }

            $nextLine = $lines[$lineIndex + 1] ?? null;

            if (is_array($nextLine) && $nextLine['line_type'] === 'basmallah') {
                continue;
            }

            $result[] = [
                'line_number' => (int) $line['line_number'] + 1,
                'line_type' => 'basmallah',
                'is_centered' => true,
                'surah_number' => $surahNumber > 0 ? $surahNumber : null,
                'segments' => [],
                'words' => array_map(
                    static fn (array $word): array => [
                        'verse_id' => 0,
                        'word_index' => (int) $word['word_index'],
                        'ayah_index' => 0,
                        'surah_number' => $surahNumber,
                        'ayah_number' => 0,
                        'text' => (string) $word['text'],
                        'is_glyph' => (bool) $word['is_glyph'],
                        'ends_ayah' => false,
                    ],
                    $syntheticWords,
                ),
                'text' => $syntheticWords !== []
                    ? trim(implode('', array_map(static fn (array $word): string => (string) $word['text'], $syntheticWords)))
                    : 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ',
            ];
        }

        return array_map(
            static fn (array $line, int $index): array => [
                ...$line,
                'line_number' => $index + 1,
            ],
            $result,
            array_keys($result),
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

        $databasePath = $this->resolveQpcDisplayWordsDatabasePath();

        if ($databasePath === null) {
            $cached = [];

            return $cached;
        }

        $database = new \SQLite3($databasePath, SQLITE3_OPEN_READONLY);
        $statement = $database->prepare('SELECT id, text FROM words WHERE surah = 1 AND ayah = 1 ORDER BY id');

        if (! $statement instanceof \SQLite3Stmt) {
            $database->close();
            $cached = [];

            return $cached;
        }

        $result = $statement->execute();

        if (! $result instanceof \SQLite3Result) {
            $statement->close();
            $database->close();
            $cached = [];

            return $cached;
        }

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

        $cached = $words;

        return $cached;
    }
}
