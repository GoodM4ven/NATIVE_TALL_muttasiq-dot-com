<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp;

use App\Models\Setting;
use App\Services\Native\NativeQuranPreparationService;
use App\Services\Quran\QuranReaderDataService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Async;
use Livewire\Attributes\Json;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class Reader extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    private const SEARCH_STREAM_TARGET = 'quran-search-results-stream';

    private const SEARCH_STREAM_PADDING_BYTES = 65536;

    private const SEARCH_STREAM_FRAME_DELIMITER = "\n<<<QURAN_SEARCH_STREAM_FRAME>>>\n";

    private const SEARCH_CANCEL_CACHE_PREFIX = 'quran-reader-search-cancel-v1';

    private const HISTORY_MODAL_ID = 'quran-reader-history-modal';

    private const BOOKMARKS_MODAL_ID = 'quran-reader-bookmarks-modal';

    public int $pageNumber = 1;

    public int $activeAyahIndex = 0;

    public int $maxPage = 1;

    public function updatedPageNumber(): void
    {
        if ($this->pageNumber < 1) {
            $this->pageNumber = 1;
        }
    }

    public function goToPage(int $pageNumber): void
    {
        $this->pageNumber = max(1, $pageNumber);
    }

    public function nextPage(): void
    {
        $this->pageNumber++;
    }

    public function previousPage(): void
    {
        $this->pageNumber = max(1, $this->pageNumber - 1);
    }

    public function selectAyah(int $ayahIndex): void
    {
        if ($ayahIndex < 1) {
            return;
        }

        $this->activeAyahIndex = $ayahIndex;
    }

    /**
     * @return array{
     *     ready: bool,
     *     prepared: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed'|null,
     *     payload: array{
     *         ready: bool,
     *         pageNumber: int,
     *         maxPage: int,
     *         activeAyahIndex: int,
     *         mushafLines: array<int, array{
     *             line_number: int,
     *             line_type: string,
     *             is_centered: bool,
     *             surah_number: int|null,
     *             segments: array<int, array{
     *                 verse_id: int,
     *                 ayah_index: int,
     *                 surah_number: int,
     *                 ayah_number: int,
     *                 text: string,
     *                 ends_ayah: bool
     *             }>,
     *             words: array<int, array{
     *                 verse_id: int,
     *                 word_index: int,
     *                 ayah_index: int,
     *                 surah_number: int,
     *                 ayah_number: int,
     *                 text: string,
     *                 is_glyph: bool,
     *                 ends_ayah: bool
     *             }>,
     *             text: string
     *         }>,
     *         qpcPageFontFamily: string|null,
     *         qpcPageFontUrl: string|null,
     *         qpcPageFontFormat: string|null,
     *         basmallahFontFamily: string|null,
     *         basmallahFontUrl: string|null,
     *         basmallahFontFormat: string|null,
     *         basmallahText: string|null,
     *         surahHeaderFontFamily: string|null,
     *         surahHeaderFontUrl: string|null,
     *         surahHeaderFontFormat: string|null,
     *         useCenteredAyahLayout: bool
     *     }|null,
     *     message: string|null
     * }
     */
    public function prepareQuranData(
        NativeQuranPreparationService $preparationService,
        QuranReaderDataService $readerDataService,
    ): array {
        $status = $preparationService->queueIfNeeded($readerDataService);

        if ($status['ready']) {
            return $this->readyPreparationResponse($readerDataService);
        }

        return $this->pendingPreparationResponse($status);
    }

    /**
     * @return array{
     *     ready: bool,
     *     prepared: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed'|null,
     *     payload: array{
     *         ready: bool,
     *         pageNumber: int,
     *         maxPage: int,
     *         activeAyahIndex: int,
     *         mushafLines: array<int, array{
     *             line_number: int,
     *             line_type: string,
     *             is_centered: bool,
     *             surah_number: int|null,
     *             segments: array<int, array{
     *                 verse_id: int,
     *                 ayah_index: int,
     *                 surah_number: int,
     *                 ayah_number: int,
     *                 text: string,
     *                 ends_ayah: bool
     *             }>,
     *             words: array<int, array{
     *                 verse_id: int,
     *                 word_index: int,
     *                 ayah_index: int,
     *                 surah_number: int,
     *                 ayah_number: int,
     *                 text: string,
     *                 is_glyph: bool,
     *                 ends_ayah: bool
     *             }>,
     *             text: string
     *         }>,
     *         qpcPageFontFamily: string|null,
     *         qpcPageFontUrl: string|null,
     *         qpcPageFontFormat: string|null,
     *         basmallahFontFamily: string|null,
     *         basmallahFontUrl: string|null,
     *         basmallahFontFormat: string|null,
     *         basmallahText: string|null,
     *         surahHeaderFontFamily: string|null,
     *         surahHeaderFontUrl: string|null,
     *         surahHeaderFontFormat: string|null,
     *         useCenteredAyahLayout: bool
     *     }|null,
     *     message: string|null
     * }
     */
    public function quranPreparationStatus(
        NativeQuranPreparationService $preparationService,
        QuranReaderDataService $readerDataService,
    ): array {
        $status = $preparationService->currentStatus($readerDataService);

        if ($status['ready']) {
            return $this->readyPreparationResponse($readerDataService);
        }

        return $this->pendingPreparationResponse($status);
    }

    /**
     * @return array{
     *     ready: true,
     *     prepared: false,
     *     state: 'ready',
     *     payload: array{
     *         ready: bool,
     *         pageNumber: int,
     *         maxPage: int,
     *         activeAyahIndex: int,
     *         mushafLines: array<int, array{
     *             line_number: int,
     *             line_type: string,
     *             is_centered: bool,
     *             surah_number: int|null,
     *             segments: array<int, array{
     *                 verse_id: int,
     *                 ayah_index: int,
     *                 surah_number: int,
     *                 ayah_number: int,
     *                 text: string,
     *                 ends_ayah: bool
     *             }>,
     *             words: array<int, array{
     *                 verse_id: int,
     *                 word_index: int,
     *                 ayah_index: int,
     *                 surah_number: int,
     *                 ayah_number: int,
     *                 text: string,
     *                 is_glyph: bool,
     *                 ends_ayah: bool
     *             }>,
     *             text: string
     *         }>,
     *         qpcPageFontFamily: string|null,
     *         qpcPageFontUrl: string|null,
     *         qpcPageFontFormat: string|null,
     *         basmallahFontFamily: string|null,
     *         basmallahFontUrl: string|null,
     *         basmallahFontFormat: string|null,
     *         basmallahText: string|null,
     *         surahHeaderFontFamily: string|null,
     *         surahHeaderFontUrl: string|null,
     *         surahHeaderFontFormat: string|null,
     *         useCenteredAyahLayout: bool
     *     },
     *     message: null
     * }
     */
    private function readyPreparationResponse(QuranReaderDataService $readerDataService): array
    {
        return [
            'ready' => true,
            'prepared' => false,
            'payload' => $readerDataService->resolvePage($this->pageNumber, $this->activeAyahIndex),
            'state' => 'ready',
            'message' => null,
            'progressPercent' => 100,
            'downloadedBytes' => null,
            'totalBytes' => null,
        ];
    }

    /**
     * @param  array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }  $status
     * @return array{
     *     ready: false,
     *     prepared: false,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     payload: null,
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null
     * }
     */
    private function pendingPreparationResponse(array $status): array
    {
        return [
            'ready' => false,
            'prepared' => false,
            'payload' => null,
            'state' => $status['state'],
            'message' => $status['message'],
            'progressPercent' => $status['progressPercent'] ?? null,
            'downloadedBytes' => $status['downloadedBytes'] ?? null,
            'totalBytes' => $status['totalBytes'] ?? null,
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
    public function streamSearch(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        $this->prepareSearchStreamingOutput();

        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $normalizedRequestSerial = max(0, $requestSerial);
        $resolvedLimit = max(6, min(24, $limit));
        $this->markSearchStreamStarted($normalizedRequestSerial);

        $this->streamSearchPayload(
            [],
            [],
            $normalizedRequestSerial,
            'start',
            false,
        );

        $didStreamChunks = false;
        $emittedMatchKeys = [];
        $shouldCancel = fn (): bool => $this->shouldCancelSearchStream($normalizedRequestSerial);

        try {
            $results = $readerDataService->searchProgressively(
                $query,
                $resolvedLimit,
                function (array $matches, string $stage, bool $isComplete) use (
                    $normalizedRequestSerial,
                    $shouldCancel,
                    &$didStreamChunks,
                    &$emittedMatchKeys,
                ): void {
                    if ($shouldCancel()) {
                        return;
                    }

                    $didStreamChunks = true;

                    $normalizedMatches = array_values($matches);

                    if (! $isComplete) {
                        $newStageMatches = [];

                        foreach ($normalizedMatches as $match) {
                            $matchKey = $this->searchStreamMatchKey($match);

                            if ($matchKey === null || isset($emittedMatchKeys[$matchKey])) {
                                continue;
                            }

                            $emittedMatchKeys[$matchKey] = true;
                            $newStageMatches[] = $match;
                        }

                        if ($newStageMatches === []) {
                            return;
                        }

                        foreach ($newStageMatches as $stageMatch) {
                            $this->streamSearchPayload(
                                [],
                                [$stageMatch],
                                $normalizedRequestSerial,
                                $stage,
                                false,
                            );
                        }

                        return;
                    }

                    $this->streamSearchPayload(
                        $normalizedMatches,
                        [],
                        $normalizedRequestSerial,
                        $stage,
                        true,
                    );
                },
                $shouldCancel,
            );

            if (! $didStreamChunks && ! $shouldCancel()) {
                $this->streamSearchPayload(
                    $results,
                    [],
                    $normalizedRequestSerial,
                    'complete',
                    true,
                );
            }

            return $shouldCancel() ? [] : $results;
        } finally {
            $this->clearSearchStreamState($normalizedRequestSerial);
        }
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
    #[Json]
    #[Async]
    public function searchSurahExact(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        return $readerDataService->searchByStages(
            $query,
            ['surah_exact'],
            max(1, min(24, $limit)),
        );
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
    #[Json]
    #[Async]
    public function searchSurahClose(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        return $readerDataService->searchByStages(
            $query,
            ['surah_close'],
            max(1, min(24, $limit)),
        );
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
    #[Json]
    #[Async]
    public function searchAyahExact(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        return $readerDataService->searchByStages(
            $query,
            ['ayah_exact'],
            max(1, min(24, $limit)),
        );
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
    #[Json]
    #[Async]
    public function searchAyahClose(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        return $readerDataService->searchByStages(
            $query,
            ['ayah_close'],
            max(1, min(24, $limit)),
        );
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
    #[Json]
    #[Async]
    public function searchSurahSarf(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        return $readerDataService->searchByStages(
            $query,
            ['surah_sarf'],
            max(1, min(24, $limit)),
        );
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
    #[Json]
    #[Async]
    public function searchAyahSarf(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        return $readerDataService->searchByStages(
            $query,
            ['ayah_sarf'],
            max(1, min(24, $limit)),
        );
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
    #[Json]
    #[Async]
    public function searchAyahJathr(string $query, int $requestSerial = 0, int $limit = 24): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        return $readerDataService->searchByStages(
            $query,
            ['ayah_jathr'],
            max(1, min(24, $limit)),
        );
    }

    #[Renderless]
    public function cancelSearch(int $requestSerial = 0): void
    {
        $this->markSearchStreamCancelled(max(0, $requestSerial));
    }

    /**
     * @return array{cancelled_serial: int}
     */
    private function searchStreamState(): array
    {
        $state = Cache::get($this->searchStreamCacheKey());

        if (! is_array($state)) {
            return [
                'cancelled_serial' => 0,
            ];
        }

        return [
            'cancelled_serial' => max(0, (int) ($state['cancelled_serial'] ?? 0)),
        ];
    }

    private function markSearchStreamStarted(int $requestSerial): void
    {
        $this->persistSearchStreamState([
            'cancelled_serial' => max(0, $requestSerial - 1),
        ]);
    }

    private function markSearchStreamCancelled(int $requestSerial): void
    {
        $state = $this->searchStreamState();
        $state['cancelled_serial'] = max($state['cancelled_serial'], $requestSerial);
        $this->persistSearchStreamState($state);
    }

    private function shouldCancelSearchStream(int $requestSerial): bool
    {
        return $this->searchStreamState()['cancelled_serial'] >= $requestSerial;
    }

    private function clearSearchStreamState(int $requestSerial): void
    {
        $state = $this->searchStreamState();

        if ($state['cancelled_serial'] <= $requestSerial) {
            Cache::forget($this->searchStreamCacheKey());
        }
    }

    /**
     * @param  array{cancelled_serial: int}  $state
     */
    private function persistSearchStreamState(array $state): void
    {
        Cache::put($this->searchStreamCacheKey(), $state, now()->addMinutes(30));
    }

    private function searchStreamCacheKey(): string
    {
        $sessionId = (string) session()->getId();

        return self::SEARCH_CANCEL_CACHE_PREFIX.':'.$sessionId.':'.static::class;
    }

    /**
     * @param  array{
     *     id?: int,
     *     surah_number?: int,
     *     ayah_number?: int,
     *     ayah_index?: int,
     *     page_number?: int,
     *     match_rank?: int,
     *     match_strategy?: string
     * }  $match
     */
    private function searchStreamMatchKey(array $match): ?string
    {
        $id = max(0, (int) ($match['id'] ?? 0));

        if ($id > 0) {
            return sprintf('id:%d', $id);
        }

        $surahNumber = max(0, (int) ($match['surah_number'] ?? 0));
        $ayahNumber = max(0, (int) ($match['ayah_number'] ?? 0));
        $ayahIndex = max(0, (int) ($match['ayah_index'] ?? 0));
        $pageNumber = max(0, (int) ($match['page_number'] ?? 0));

        if ($surahNumber < 1 || $pageNumber < 1) {
            return null;
        }

        if ($ayahIndex > 0) {
            return sprintf('ayah-index:%d:%d:%d:%d', $surahNumber, $ayahNumber, $pageNumber, $ayahIndex);
        }

        if ($ayahNumber > 0) {
            return sprintf('ayah:%d:%d:%d', $surahNumber, $ayahNumber, $pageNumber);
        }

        return sprintf(
            'surah:%d:%d',
            $surahNumber,
            $pageNumber,
        );
    }

    public function searchQuranAction(): Action
    {
        return Action::make('searchQuran')
            ->modalHeading(arabic_text('البحث الشامل في القرآن الكريم'))
            ->modalDescription(
                arabic_text('تظهر النتائج على دفعات: سور مطابقة أو قريبة أو صرفية، ثم آيات مطابقة أو قريبة أو صرفية أو جذرية.'),
            )
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->extraModalWindowAttributes([
                'id' => 'quran-reader-search-modal',
                'class' => 'muttasiq-modal-window quran-reader-search-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay',
            ])
            ->modalContent(
                fn (): HtmlString => new HtmlString(Blade::render('<x-partials.quran-app.search-modal />')),
            )
            ->action(static fn (): null => null);
    }

    public function jumpToPageAction(): Action
    {
        return Action::make('jumpToPage')
            ->modalHeading(arabic_text('الانتقال إلى صفحة'))
            ->modalDescription(arabic_text('أدخل رقم الصفحة المراد الانتقال إليها.'))
            ->modalAutofocus(true)
            ->modalWidth(Width::Small)
            ->modalSubmitActionLabel(arabic_text('انتقال'))
            ->extraModalWindowAttributes([
                'id' => 'quran-reader-jump-page-modal',
                'class' => 'muttasiq-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay',
            ])
            ->fillForm(fn (): array => [
                'page' => max(1, $this->pageNumber),
            ])
            ->modalFooterActionsAlignment(Alignment::Center)
            ->schema([
                TextInput::make('page')
                    ->label(arabic_text('الصفحة'))
                    ->type('number')
                    ->inputMode('numeric')
                    ->autofocus()
                    ->extraFieldWrapperAttributes([
                        'id' => 'quran-reader-page-counter-field',
                        'class' => 'quran-page-counter-field',
                    ])
                    ->extraInputAttributes([
                        'id' => 'quran-reader-page-counter-input',
                        'min' => '1',
                        'max' => (string) max(1, $this->maxPage),
                        'step' => '1',
                        'x-init' => '$nextTick(() => { $el.focus(); $el.select(); });',
                        'x-on:pointerdown.prevent' => '$event.target.focus(); $event.target.select();',
                        'x-on:click.prevent' => '$event.target.select();',
                        'x-on:focus' => '$event.target.select();',
                        'x-on:input' => '$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number($event.target.value || 1) || 1)), Math.max(1, Number($event.target.max) || 1)));',
                        'x-on:blur' => '$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number($event.target.value || 1) || 1)), Math.max(1, Number($event.target.max) || 1))); window.setTimeout(() => { if (!document.body.contains($event.target) || $event.target.offsetParent === null) { return; } $event.target.focus(); $event.target.select(); }, 0);',
                    ], merge: true)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $targetPage = max(1, min(max(1, $this->maxPage), (int) ($data['page'] ?? 1)));

                $this->dispatch('quran-go-page', page: $targetPage, source: 'page-jump');
            });
    }

    public function navigationHistoryAction(): Action
    {
        return Action::make('navigationHistory')
            ->modalHeading(arabic_text('سجلّ الانتقالات'))
            ->modalDescription(arabic_text('آخر مئة انتقال بين الصفحات، بالإضافة لحفظ المميّز (بالوسوم والملاحظات).'))
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->extraModalWindowAttributes([
                'id' => self::HISTORY_MODAL_ID,
                'dir' => 'ltr',
                'class' => 'muttasiq-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay',
            ])
            ->modalContent(
                fn (): HtmlString => new HtmlString(Blade::render('<x-partials.quran-app.history-modal />')),
            )
            ->action(static fn (): null => null);
    }

    public function bookmarksManagerAction(): Action
    {
        return Action::make('bookmarksManager')
            ->modalHeading(arabic_text('إدارة علامات الصفحات'))
            ->modalDescription(arabic_text('جدول للبحث والتصفية (بالوسوم والملاحظات) والترتيب، من أجل الانتقال السريع.'))
            ->modalAutofocus(false)
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->extraModalWindowAttributes([
                'id' => self::BOOKMARKS_MODAL_ID,
                'class' => 'muttasiq-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay',
            ])
            ->modalContent(
                fn (): HtmlString => new HtmlString(Blade::render('<x-partials.quran-app.bookmarks-modal />')),
            )
            ->action(static fn (): null => null);
    }

    #[Renderless]
    public function prewarmManagerModals(): void
    {
        foreach (['search-modal', 'history-modal', 'bookmarks-modal'] as $partial) {
            Blade::render("<x-partials.quran-app.{$partial} />");
        }
    }

    public function supportUnlockAction(): Action
    {
        return Action::make('supportUnlock')
            ->modalHeading(arabic_text('دعم المشروع'))
            ->modalDescription(arabic_text('قبل استخدام بعض الخصائص المميّزة في التطبيق، نحتاج منك تأكيد دعم تطوير المشروع.'))
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel(arabic_text('قمت بالدعم'))
            ->modalCancelAction(false)
            ->extraModalWindowAttributes([
                'id' => 'support-unlock-modal',
                'class' => 'muttasiq-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay',
            ])
            ->modalContent(fn (): HtmlString => $this->supportUnlockModalContent())
            ->extraModalFooterActions(fn (Action $action): array => [
                $action
                    ->makeModalSubmitAction('supportUnlockWeeklyBypass', arguments: ['mode' => 'weekly'])
                    ->label(arabic_text('أشهد الله أني لا أستطيع دعمكم الآن'))
                    ->color('gray'),
            ])
            ->action(function (array $data, array $arguments): void {
                $mode = ($arguments['mode'] ?? null) === 'weekly'
                    ? 'weekly'
                    : 'permanent';

                $this->dispatch('support-unlock-updated', mode: $mode);

                notify(
                    iconName: $mode === 'weekly'
                        ? 'heroicon-o-clock'
                        : 'heroicon-o-lock-open',
                    title: $mode === 'weekly'
                        ? arabic_text('تمت إتاحة الميّزات لأسبوع واحد')
                        : arabic_text('تمت إتاحة الميّزات بشكل دائم'),
                    body: $mode === 'weekly'
                        ? arabic_text('رزقك الله...')
                        : arabic_text('أحسن الله إليك...'),
                );
            });
    }

    public function render(): View
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $readerData = $readerDataService->resolvePage($this->pageNumber, $this->activeAyahIndex);
        $surahNames = $readerDataService->surahNames();
        $surahDirectory = $readerDataService->surahDirectory();
        $storedSettings = Setting::query()
            ->whereIn('name', array_keys(Setting::defaults()))
            ->pluck('value', 'name')
            ->all();
        $normalizedSettings = Setting::normalizeSettings(
            array_replace(Setting::defaults(), $storedSettings),
        );
        $westernNumerals = array_values(
            array_map(
                static fn (mixed $character): string => (string) $character,
                \arabicable_special_characters(only: ArabicSpecialCharacters::IndianNumerals),
            ),
        );
        $arabicNumerals = array_values(
            array_map(
                static fn (mixed $character): string => (string) $character,
                \arabicable_special_characters(only: ArabicSpecialCharacters::ArabicNumerals),
            ),
        );
        $quranReaderSettings = [
            'enableVisualEnhancements' => (bool) ($normalizedSettings[Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS] ?? false),
            'targetWordsByDefault' => (bool) ($normalizedSettings[Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT] ?? false),
            'preserveHarakatOnCopy' => (bool) ($normalizedSettings[Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY] ?? true),
            'appendSurahAffixOnMultiCopy' => (bool) ($normalizedSettings[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY] ?? true),
            'appendSurahAffixAlwaysOnCopy' => (bool) ($normalizedSettings[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY] ?? false),
            'showImmersiveMobileEdgeCaptions' => (bool) ($normalizedSettings[Setting::DOES_QURAN_SHOW_IMMERSIVE_MOBILE_EDGE_CAPTIONS] ?? true),
            'useVolumeButtonsNavigation' => (bool) ($normalizedSettings[Setting::DOES_QURAN_USE_VOLUME_BUTTONS_NAVIGATION] ?? false),
            'useWesternNumerals' => (bool) ($normalizedSettings[Setting::DOES_USE_WESTERN_NUMERALS] ?? true),
            'wirdFrequencyMode' => (int) ($normalizedSettings[Setting::QURAN_WIRD_FREQUENCY_MODE] ?? Setting::QURAN_WIRD_FREQUENCY_MONTHLY),
            'wirdKhatmatTarget' => (int) ($normalizedSettings[Setting::QURAN_WIRD_KHATMAT_TARGET] ?? 1),
            'numeralCharacters' => [
                'western' => count($westernNumerals) === 10 ? $westernNumerals : ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                'arabic' => count($arabicNumerals) === 10 ? $arabicNumerals : ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ],
        ];
        $this->maxPage = max(1, $readerData['maxPage']);

        if (! $readerData['ready']) {
            return view('livewire.quran-app.reader', [
                ...$readerData,
                'surahNames' => $surahNames,
                'surahDirectory' => $surahDirectory,
                'quranReaderSettings' => $quranReaderSettings,
            ]);
        }

        $normalizedPage = $readerData['pageNumber'];

        if ($normalizedPage !== $this->pageNumber) {
            $this->pageNumber = $normalizedPage;
        }

        $effectiveAyahIndex = $readerData['activeAyahIndex'];

        if ($effectiveAyahIndex > 0 && $effectiveAyahIndex !== $this->activeAyahIndex) {
            $this->activeAyahIndex = $effectiveAyahIndex;
        }

        return view('livewire.quran-app.reader', [
            ...$readerData,
            'surahNames' => $surahNames,
            'surahDirectory' => $surahDirectory,
            'quranReaderSettings' => $quranReaderSettings,
        ]);
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
    private function streamSearchPayload(
        array $matches,
        array $stageMatches,
        int $requestSerial,
        string $stage,
        bool $isComplete,
    ): void {
        $encodedPayload = json_encode([
            'request_serial' => $requestSerial,
            'stage' => $stage,
            'is_loading' => ! $isComplete,
            'items' => array_values($matches),
            'stage_items' => array_values($stageMatches),
            // Some server stacks buffer tiny stream frames until several KB accumulate.
            'pad' => str_repeat(' ', self::SEARCH_STREAM_PADDING_BYTES),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($encodedPayload)) {
            return;
        }

        $this->stream(
            content: e($encodedPayload).self::SEARCH_STREAM_FRAME_DELIMITER,
            replace: false,
            to: self::SEARCH_STREAM_TARGET,
        );
        $this->flushSearchStreamingOutput();
    }

    private function prepareSearchStreamingOutput(): void
    {
        if (! headers_sent()) {
            header('X-Accel-Buffering: no');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }

        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
    }

    private function flushSearchStreamingOutput(): void
    {
        if (
            function_exists('ob_get_level') &&
            function_exists('ob_flush') &&
            ob_get_level() > 0
        ) {
            @ob_flush();
        }

        if (function_exists('flush')) {
            @flush();
        }
    }

    private function supportUnlockModalContent(): HtmlString
    {
        $introBeforeStrong = arabic_text(
            'تطوير المزايا المتقدمة، وإتاحة التطبيق على المخدّمات والمنصات بأجهزتها المختلفة، كل هذا يتطلب ',
        );
        $introStrong = arabic_text('وقتًا وجهدًا وتكلفة مستمرة');
        $introAfterStrong = arabic_text(
            '، بارك الله فيكم... ولذلك نودّ منكم على الأقلّ محاولة التبرع لتطوير تطبيق متسق باستخدام إحدى المنصات المتاحة لذلك، وجزاكم الله خيرا.',
        );
        $supportLinksCaption = arabic_text('روابط منصات الدعم:');

        return new HtmlString(
            '<div class="space-y-4 text-right text-sm! leading-7">'
                .'<p>'
                .e($introBeforeStrong)
                .'<strong>'.e($introStrong).'</strong>'
                .e($introAfterStrong)
                .'</p>'
                .'<div class="rounded-xl border border-gray-200/70 bg-white/70 p-3 text-sm">'
                .'<p class="mb-2 font-semibold text-gray-900">'.e($supportLinksCaption).'</p>'
                .'<div class="flex flex-wrap items-center justify-end gap-2">'
                .$this->supportUnlockLinkMarkup('Buy Me a Coffee', 'https://buymeacoffee.com/goodm4ven')
                .$this->supportUnlockLinkMarkup('Patreon', 'https://patreon.com/GoodM4ven')
                .$this->supportUnlockLinkMarkup('GitHub Sponsors', 'https://github.com/sponsors/GoodM4ven')
                .'</div>'
                .'</div>'
                .'</div>',
        );
    }

    private function supportUnlockLinkMarkup(string $label, string $url): string
    {
        $openLinkNativeAware = htmlspecialchars(open_link_native_aware($url), ENT_QUOTES, 'UTF-8');
        $safeLabel = e($label);

        return '<button type="button" class="rounded-lg border border-primary-300/70 bg-primary-50/70 px-3 py-1.5 text-xs font-medium text-primary-800 transition hover:bg-primary-100/80"'
            .' x-on:click.prevent="'.$openLinkNativeAware.'"'
            .' x-on:keydown.enter.prevent="'.$openLinkNativeAware.'"'
            .' x-on:keydown.space.prevent="'.$openLinkNativeAware.'">'
            .$safeLabel
            .'</button>';
    }
}
