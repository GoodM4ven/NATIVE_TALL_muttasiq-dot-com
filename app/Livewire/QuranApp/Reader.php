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
use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;
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
            ->modalAutofocus(true)
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
                        'x-on:focus' => '$event.target.select();',
                        'x-on:input' => '$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number($event.target.value || 1) || 1)), Math.max(1, Number($event.target.max) || 1)));',
                        'x-on:blur' => '$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number($event.target.value || 1) || 1)), Math.max(1, Number($event.target.max) || 1)));',
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
            ->modalHeading('سجل التنقّل')
            ->modalDescription('آخر الانتقالات بين الصفحات. يبقى الموسوم محفوظًا، زيادة على آخر 100 عنصر.')
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
            ->modalDescription('انقر للانتقال، عدّل الملاحظة وأدر الوسوم مباشرة، أو استبدل الصفحة المحفوظة بالصفحة الحالية.')
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

    public function supportUnlockAction(): Action
    {
        return Action::make('supportUnlock')
            ->modalHeading('دعم المشروع')
            ->modalDescription('قبل استخدام بعض الخصائص المميّزة في التطبيق، نحتاج منك تأكيد دعم تطوير المشروع.')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('قمت بالدعم')
            ->modalCancelAction(false)
            ->extraModalWindowAttributes([
                'id' => 'support-unlock-modal',
            ])
            ->modalContent(fn (): HtmlString => $this->supportUnlockModalContent())
            ->extraModalFooterActions(fn (Action $action): array => [
                $action
                    ->makeModalSubmitAction('supportUnlockWeeklyBypass', arguments: ['mode' => 'weekly'])
                    ->label('أشهد الله أني لا أستطيع دعمكم الآن')
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
                        ? 'تمت إتاحة الميّزات لأسبوع واحد'
                        : 'تمت إتاحة الميّزات بشكل دائم',
                    body: $mode === 'weekly'
                        ? 'رزقك الله...'
                        : 'أحسن الله إليك...',
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
            'enableVisualEnhancements' => (bool) ($normalizedSettings[Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS] ?? true),
            'targetWordsByDefault' => (bool) ($normalizedSettings[Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT] ?? false),
            'preserveHarakatOnCopy' => (bool) ($normalizedSettings[Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY] ?? true),
            'appendSurahAffixOnMultiCopy' => (bool) ($normalizedSettings[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY] ?? true),
            'appendSurahAffixAlwaysOnCopy' => (bool) ($normalizedSettings[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY] ?? false),
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

    private function supportUnlockModalContent(): HtmlString
    {
        return new HtmlString(
            '<div class="space-y-4 text-right text-sm! leading-7">'
            .'<p>تطوير المزايا المتقدمة، وإتاحة التطبيق على المخدّمات والمنصات بأجهزتها المختلفة، كل هذا يتطلب <strong>وقتًا وجهدًا وتكلفة مستمرة</strong>، بارك الله فيكم... ولذلك نودّ منكم على الأقلّ محاولة التبرع لتطوير تطبيق متسق باستخدام إحدى المنصات المتاحة لذلك، وجزاكم الله خيرا.</p>'
            .'<div class="rounded-xl border border-gray-200/70 bg-white/70 p-3 text-sm">'
            .'<p class="mb-2 font-semibold text-gray-900">روابط منصات الدعم:</p>'
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
