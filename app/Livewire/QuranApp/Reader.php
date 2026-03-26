<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp;

use App\Models\Setting;
use App\Services\Quran\QuranReaderDataService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class Reader extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    private const SEARCH_STREAM_TARGET = 'quran-search-results-stream';

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
    public function streamSearch(string $query, int $requestSerial = 0): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $normalizedRequestSerial = max(0, $requestSerial);
        $didStreamChunks = false;
        $results = $readerDataService->searchProgressively(
            $query,
            24,
            function (array $matches, string $stage, bool $isComplete) use (
                $normalizedRequestSerial,
                &$didStreamChunks
            ): void {
                $didStreamChunks = true;
                $this->streamSearchPayload(
                    $matches,
                    $normalizedRequestSerial,
                    $stage,
                    $isComplete,
                );
            },
        );

        if (! $didStreamChunks) {
            $this->streamSearchPayload(
                $results,
                $normalizedRequestSerial,
                'complete',
                true,
            );
        }

        return $results;
    }

    public function searchQuranAction(): Action
    {
        return Action::make('searchQuran')
            ->modalHeading('البحث الشامل للقرآن الكريم')
            ->modalDescription('ابحث عن الآيات وانتقل مباشرة إلى السورة والموضع المقصود...')
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->extraModalWindowAttributes([
                'id' => 'quran-reader-search-modal',
            ])
            ->schema([
                TextInput::make('search')
                    ->hiddenLabel()
                    ->type('search')
                    ->placeholder('يا بنيّ أقم الصلاة، وأمر بالمعروف، وانه عن المنكر...')
                    ->extraFieldWrapperAttributes([
                        'class' => 'quran-search-field-wrapper',
                    ])
                    ->extraInputAttributes([
                        'id' => 'quran-reader-search-input',
                        'x-ref' => 'searchModalInput',
                        'x-model.debounce.1000ms' => 'search.query',
                        'x-on:input.debounce.1000ms' => 'updateSearchResults()',
                        'x-on:keydown.enter.prevent' => 'confirmSearchSelection()',
                        'autocomplete' => 'off',
                        'class' => 'relative top-[0.25rem]',
                    ], merge: true),
            ])
            ->modalContentFooter(
                fn (): HtmlString => new HtmlString(Blade::render('<x-partials.quran-app.search-modal />')),
            );
    }

    public function jumpToPageAction(): Action
    {
        return Action::make('jumpToPage')
            ->modalHeading('الانتقال إلى صفحة')
            ->modalDescription('أدخل رقم الصفحة المراد الانتقال إليها.')
            ->modalAutofocus(false)
            ->modalWidth(Width::Small)
            ->modalSubmitActionLabel('انتقال')
            ->extraModalWindowAttributes([
                'id' => 'quran-reader-jump-page-modal',
            ])
            ->fillForm(fn (): array => [
                'page' => max(1, $this->pageNumber),
            ])
            ->modalFooterActionsAlignment(Alignment::Center)
            ->schema([
                TextInput::make('page')
                    ->label('الصفحة')
                    ->type('number')
                    ->inputMode('numeric')
                    ->extraFieldWrapperAttributes([
                        'id' => 'quran-reader-page-counter-field',
                        'class' => 'quran-page-counter-field',
                    ])
                    ->extraInputAttributes([
                        'id' => 'quran-reader-page-counter-input',
                        'min' => '1',
                        'max' => (string) max(1, $this->maxPage),
                        'step' => '1',
                        'x-on:input' => '$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number($event.target.value || 1) || 1)), Math.max(1, Number($event.target.max) || 1)));',
                        'x-on:blur' => '$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number($event.target.value || 1) || 1)), Math.max(1, Number($event.target.max) || 1)));',
                    ], merge: true)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $targetPage = max(1, min(max(1, $this->maxPage), (int) ($data['page'] ?? 1)));

                $this->dispatch('quran-go-page', page: $targetPage);
            });
    }

    public function navigationHistoryAction(): Action
    {
        return Action::make('navigationHistory')
            ->modalHeading('سجل التنقّل')
            ->modalDescription('آخر انتقالات البحث والتنقّل السريع بين السور. يبقى المعلّم فقط خارج حدّ آخر 100 عنصر.')
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->extraModalWindowAttributes([
                'id' => self::HISTORY_MODAL_ID,
                'dir' => 'ltr',
            ])
            ->modalContent(
                fn (): HtmlString => new HtmlString(Blade::render('<x-partials.quran-app.history-modal />')),
            )
            ->action(static fn (): null => null);
    }

    public function bookmarksManagerAction(): Action
    {
        return Action::make('bookmarksManager')
            ->modalHeading('إدارة علامات الصفحات')
            ->modalDescription('انقر للانتقال، عدّل العنوان مباشرة، أو استبدل الصفحة المحفوظة بالصفحة الحالية.')
            ->modalAutofocus(false)
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->extraModalWindowAttributes([
                'id' => self::BOOKMARKS_MODAL_ID,
            ])
            ->modalContent(
                fn (): HtmlString => new HtmlString(Blade::render('<x-partials.quran-app.bookmarks-modal />')),
            )
            ->action(static fn (): null => null);
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
        $quranReaderSettings = [
            'enableVisualEnhancements' => (bool) ($normalizedSettings[Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS] ?? true),
            'targetWordsByDefault' => (bool) ($normalizedSettings[Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT] ?? false),
            'preserveHarakatOnCopy' => (bool) ($normalizedSettings[Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY] ?? true),
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
     */
    private function streamSearchPayload(
        array $matches,
        int $requestSerial,
        string $stage,
        bool $isComplete,
    ): void {
        $encodedPayload = json_encode([
            'request_serial' => $requestSerial,
            'stage' => $stage,
            'is_loading' => ! $isComplete,
            'items' => array_values($matches),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($encodedPayload)) {
            return;
        }

        $this->stream(
            content: e($encodedPayload),
            replace: true,
            to: self::SEARCH_STREAM_TARGET,
        );
    }
}
