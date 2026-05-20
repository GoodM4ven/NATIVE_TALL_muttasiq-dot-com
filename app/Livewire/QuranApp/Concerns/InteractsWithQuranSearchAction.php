<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp\Concerns;

use App\Services\Quran\QuranReaderDataService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

trait InteractsWithQuranSearchAction
{
    /**
     * @var array<string, string|array<string, string>>
     */
    protected array $quranSearchModalResultOptions = [];

    /**
     * @var array<string, string>
     */
    protected array $quranSearchModalFlatResultOptions = [];

    protected int $quranSearchModalRequestSerial = 0;

    public function searchQuranAction(): Action
    {
        return Action::make('searchQuran')
            ->modalHeading(arabic_text('البحث الشامل للقرآن الكريم'))
            ->modalDescription(arabic_text('ابحث بالنص أولًا، ثم اختر من النتائج أو من فهرس السور.'))
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->extraModalWindowAttributes([
                'id' => 'quran-reader-search-modal',
                'class' => 'quran-reader-search-modal-window',
            ])
            ->mountUsing(function (): void {
                $this->resetSearchModalState();
            })
            ->fillForm(fn (): array => [
                'search_result_key' => null,
                'surah_number' => null,
            ])
            ->modalCancelAction(fn ($action) => $action->action(function (): void {
                $this->cancelActiveSearchModalRequest();
                $this->resetSearchModalState();
            }))
            ->schema([
                Placeholder::make('search_stream_target')
                    ->hiddenLabel()
                    ->content(
                        new HtmlString(
                            '<div class="sr-only hidden" data-quran-search-stream-target wire:stream="quran-search-results-stream"></div>',
                        ),
                    )
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Select::make('search_result_key')
                    ->id('quran-reader-search-select')
                    ->label(arabic_text('بحث نصّي'))
                    ->placeholder(arabic_text('جزء من آية أو اسم سورة...'))
                    ->allowHtml()
                    ->native(false)
                    ->searchable()
                    ->searchDebounce(600)
                    ->searchPrompt(arabic_text('جزء من آية أو اسم سورة...'))
                    ->noSearchResultsMessage(arabic_text('لا توجد نتائج مطابقة.'))
                    ->optionsLimit(24)
                    ->options([])
                    ->getSearchResultsUsing(
                        fn (string $search): array => $this->searchModalSelectOptions($search),
                    )
                    ->getOptionLabelUsing(
                        fn (mixed $value): ?string => $this->searchModalSelectOptionLabel($value),
                    )
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $this->navigateFromSearchResultSelection($state);
                        $set('search_result_key', null);
                    })
                    ->helperText(arabic_text('نتائج البحث تظهر تدريجيًا حسب دقة المطابقة.'))
                    ->extraInputAttributes([
                        'data-quran-search-native' => 'true',
                    ])
                    ->extraFieldWrapperAttributes([
                        'class' => 'quran-search-field-wrapper quran-search-results-select-wrapper',
                    ])
                    ->columnSpanFull(),
                Placeholder::make('search_surah_separator')
                    ->hiddenLabel()
                    ->content(new HtmlString('<div class="quran-search-modal-section-separator" aria-hidden="true"></div>'))
                    ->extraAttributes(['class' => 'quran-search-modal-separator-wrapper'])
                    ->columnSpanFull(),
                Select::make('surah_number')
                    ->id('quran-reader-surah-select')
                    ->label(arabic_text('تنقّل مباشر إلى سورة'))
                    ->options($this->searchSurahOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder(arabic_text('اختر سورة'))
                    ->live()
                    ->afterStateUpdated(
                        function (Set $set, mixed $state, QuranReaderDataService $readerDataService): void {
                            $surahNumber = max(0, (int) $state);

                            if ($surahNumber < 1) {
                                return;
                            }

                            $target = $this->resolveSurahNavigationPayload(
                                $surahNumber,
                                $readerDataService,
                            );

                            $this->dispatchSearchNavigation($target);
                            $set('surah_number', null);
                            $this->unmountAction();
                        },
                    )
                    ->helperText(arabic_text('اختيار السورة يغلق النافذة وينتقل مباشرةً.'))
                    ->extraFieldWrapperAttributes(['class' => 'quran-surah-select-wrapper'])
                    ->columnSpanFull(),
            ]);
    }

    private function resetSearchModalState(): void
    {
        $this->cancelActiveSearchModalRequest();
        $this->quranSearchModalResultOptions = [];
        $this->quranSearchModalFlatResultOptions = [];
    }

    private function cancelActiveSearchModalRequest(): void
    {
        if ($this->quranSearchModalRequestSerial < 1) {
            return;
        }

        $this->markSearchStreamCancelled($this->quranSearchModalRequestSerial);
    }

    private function navigateFromSearchResultSelection(?string $searchResultKey): void
    {
        $payload = $this->decodeSearchNavigationPayload((string) $searchResultKey);

        if ($payload === null) {
            return;
        }

        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $target = $readerDataService->resolveSearchNavigationTarget(
            verseId: $payload['verse_id'] > 0 ? $payload['verse_id'] : null,
            fallbackPageNumber: $payload['page_number'],
            fallbackAyahIndex: $payload['highlight_ayah_index'],
            fallbackSurahNumber: $payload['surah_number'],
            fallbackAyahNumber: $payload['ayah_number'],
        );
        $target['query'] = $payload['query'] ?? null;

        $this->dispatchSearchNavigation($target, source: 'search-result');
        $this->unmountAction();
    }

    /**
     * @return array<int, string>
     */
    private function searchSurahOptions(): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $surahNames = $readerDataService->surahNames();
        $surahDirectory = $readerDataService->surahDirectory();
        $options = [];

        foreach ($surahDirectory as $entry) {
            $surahNumber = max(1, (int) $entry['surah_number']);
            $surahName = trim((string) ($surahNames[$surahNumber] ?? ''));
            $surahLabel = sprintf('(%d) - %s', $surahNumber, $surahName !== '' ? $surahName : '---');
            $options[$surahNumber] = arabic_text($surahLabel);
        }

        ksort($options);

        return $options;
    }

    private function dispatchSearchNavigation(array $target, string $source = 'search-modal'): void
    {
        $this->cancelActiveSearchModalRequest();

        $this->dispatch(
            'quran-go-page',
            page: $target['page_number'],
            source: $source,
            activeAyahIndex: $target['highlight_ayah_index'],
            searchHighlightAyahIndex: $target['highlight_ayah_index'],
            surahNumber: $target['surah_number'],
            ayahNumber: $target['ayah_number'],
            query: isset($target['query']) && is_string($target['query']) && trim($target['query']) !== ''
                ? trim($target['query'])
                : null,
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
     */
    private function setSearchModalResultStateFromMatches(string $query, array $matches): void
    {
        $options = [];
        $flatOptions = [];
        $groupedOptions = [];
        $seenValues = [];

        foreach ($matches as $match) {
            $target = $this->resolveSearchMatchNavigationPayload(
                $match,
                $query,
            );

            if ($target === null) {
                continue;
            }

            $resultValue = $this->encodeSearchNavigationPayload($target);

            if (isset($seenValues[$resultValue])) {
                continue;
            }

            $groupLabel = $this->searchResultGroupLabel((string) $match['match_strategy']);

            $groupedOptions[$groupLabel] ??= [];
            $groupedOptions[$groupLabel][$resultValue] = $this->searchResultOptionHtml($match);
            $flatOptions[$resultValue] = $groupedOptions[$groupLabel][$resultValue];
            $seenValues[$resultValue] = true;
        }

        foreach ($groupedOptions as $groupLabel => $groupOptions) {
            $options[$groupLabel] = $groupOptions;
        }

        $this->quranSearchModalResultOptions = $options;
        $this->quranSearchModalFlatResultOptions = $flatOptions;
    }

    /**
     * @return array<string, string>
     */
    private function searchModalSelectOptions(string $search): array
    {
        $query = trim($search);

        if ($query === '' || mb_strlen($query) < 2) {
            $this->quranSearchModalResultOptions = [];
            $this->quranSearchModalFlatResultOptions = [];

            return [];
        }

        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $matches = $readerDataService->search($query, 24);

        $this->setSearchModalResultStateFromMatches($query, $matches);

        return $this->quranSearchModalFlatResultOptions;
    }

    private function searchModalSelectOptionLabel(mixed $value): ?string
    {
        $resultKey = trim((string) $value);

        if ($resultKey === '') {
            return null;
        }

        if (isset($this->quranSearchModalFlatResultOptions[$resultKey])) {
            return $this->quranSearchModalFlatResultOptions[$resultKey];
        }

        $payload = $this->decodeSearchNavigationPayload($resultKey);

        if ($payload === null) {
            return null;
        }

        $surahNumber = max(1, (int) $payload['surah_number']);
        $ayahNumber = max(0, (int) $payload['ayah_number']);
        $pageNumber = max(1, (int) $payload['page_number']);

        return $ayahNumber > 0
            ? arabic_text(sprintf('سورة %d · آية %d', $surahNumber, $ayahNumber))
            : arabic_text(sprintf('سورة %d · صفحة %d', $surahNumber, $pageNumber));
    }

    /**
     * @return array{
     *     verse_id: int,
     *     page_number: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     highlight_ayah_index: int,
     *     query: string|null
     * }|null
     */
    private function resolveSearchMatchNavigationPayload(
        array $match,
        string $query,
    ): ?array {
        return [
            'verse_id' => max(0, (int) ($match['id'] ?? 0)),
            'page_number' => max(1, (int) ($match['page_number'] ?? 1)),
            'surah_number' => max(1, (int) ($match['surah_number'] ?? 1)),
            'ayah_number' => max(0, (int) ($match['ayah_number'] ?? 0)),
            'highlight_ayah_index' => max(
                0,
                (int) ($match['ayah_index'] ?? 0),
            ),
            'query' => $query !== '' ? $query : null,
        ];
    }

    /**
     * @return array{
     *     verse_id: int,
     *     page_number: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     highlight_ayah_index: int,
     *     query: string|null
     * }
     */
    private function resolveSurahNavigationPayload(
        int $surahNumber,
        QuranReaderDataService $readerDataService,
    ): array {
        $surahStartPage = collect($readerDataService->surahDirectory())
            ->first(
                static fn (array $entry): bool => max(0, (int) $entry['surah_number']) === $surahNumber,
            );

        return [
            'verse_id' => 0,
            'page_number' => max(1, (int) ($surahStartPage['page_number'] ?? 1)),
            'surah_number' => max(1, $surahNumber),
            'ayah_number' => 0,
            'highlight_ayah_index' => 0,
            'query' => null,
        ];
    }

    private function searchResultGroupLabel(string $strategy): string
    {
        return match ($strategy) {
            'surah_exact', 'exact_phrase', 'exact_tokens' => arabic_text('مطابقات تامة'),
            'surah_stem', 'stem_tokens' => arabic_text('مطابقات قريبة / صرفية'),
            'root_tokens' => arabic_text('مطابقات الجذر'),
            default => arabic_text('نتائج أخرى'),
        };
    }

    /**
     * @param  array{
     *     surah_number?: int,
     *     ayah_number?: int,
     *     page_number?: int,
     *     text_uthmani?: string,
     *     match_strategy?: string,
     *     match_tone?: string,
     *     match_label?: string
     * }  $match
     */
    private function searchResultOptionHtml(array $match): string
    {
        $tone = $this->safeHtmlAttr((string) ($match['match_tone'] ?? 'warning'));
        $label = e((string) ($match['match_label'] ?? arabic_text('نتيجة')));
        $surahNumber = max(1, (int) ($match['surah_number'] ?? 1));
        $ayahNumber = max(0, (int) ($match['ayah_number'] ?? 0));
        $matchStrategy = strtolower(trim((string) ($match['match_strategy'] ?? '')));
        $pageNumber = max(1, (int) ($match['page_number'] ?? 1));
        $meta = e(
            str_starts_with($matchStrategy, 'surah_')
                ? arabic_text(sprintf('سورة %d · صفحة %d', $surahNumber, $pageNumber))
                : arabic_text(sprintf('سورة %d · آية %d', $surahNumber, max(1, $ayahNumber))),
        );
        $text = e($this->searchResultOptionAyahText($match));

        return sprintf(
            '<div class="quran-search-option-card" data-match-tone="%s"><span class="quran-search-option-card__meta">%s</span><span class="quran-search-option-card__ayah font-quran">%s</span><span class="quran-search-option-card__badge" data-match-tone="%s">%s</span></div>',
            $tone,
            $meta,
            $text,
            $tone,
            $label,
        );
    }

    private function safeHtmlAttr(string $value): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', strtolower(trim($value))) ?: 'warning';
    }

    /**
     * @param  array{
     *     text_uthmani?: string,
     *     text_searchable_typed?: string
     * }  $match
     */
    private function searchResultOptionAyahText(array $match): string
    {
        $text = trim((string) ($match['text_uthmani'] ?? ''));

        if ($text === '') {
            $text = trim((string) ($match['text_searchable_typed'] ?? ''));
        }

        if ($text === '') {
            return '';
        }

        return preg_replace('/\s+/u', ' ', trim(
            preg_replace(
                '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u',
                '',
                $text,
            ) ?? $text,
        )) ?: $text;
    }

    /**
     * @param  array{
     *     verse_id: int,
     *     page_number: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     highlight_ayah_index: int,
     *     query: string|null
     * }  $target
     */
    private function encodeSearchNavigationPayload(array $target): string
    {
        $encoded = base64_encode(json_encode($target, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');

        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }

    /**
     * @return array{
     *     verse_id: int,
     *     page_number: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     highlight_ayah_index: int,
     *     query: string|null
     * }|null
     */
    private function decodeSearchNavigationPayload(string $encodedPayload): ?array
    {
        $normalized = trim($encodedPayload);

        if ($normalized === '') {
            return null;
        }

        $padded = strtr($normalized, '-_', '+/');
        $padding = strlen($padded) % 4;

        if ($padding > 0) {
            $padded .= str_repeat('=', 4 - $padding);
        }

        $decodedJson = base64_decode($padded, true);

        if (! is_string($decodedJson)) {
            return null;
        }

        $decoded = json_decode($decodedJson, true);

        if (! is_array($decoded)) {
            return null;
        }

        $pageNumber = max(1, (int) ($decoded['page_number'] ?? 0));
        $verseId = max(0, (int) ($decoded['verse_id'] ?? 0));
        $surahNumber = max(1, (int) ($decoded['surah_number'] ?? 0));
        $ayahNumber = max(0, (int) ($decoded['ayah_number'] ?? 0));
        $highlightAyahIndex = max(0, (int) ($decoded['highlight_ayah_index'] ?? 0));
        $query = trim((string) ($decoded['query'] ?? ''));

        return [
            'verse_id' => $verseId,
            'page_number' => $pageNumber,
            'surah_number' => $surahNumber,
            'ayah_number' => $ayahNumber,
            'highlight_ayah_index' => $highlightAyahIndex,
            'query' => $query !== '' ? $query : null,
        ];
    }
}
