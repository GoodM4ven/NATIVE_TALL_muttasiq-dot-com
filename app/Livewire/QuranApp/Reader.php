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
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Reader extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public int $pageNumber = 1;

    public int $activeAyahIndex = 0;

    public int $maxPage = 1;

    /**
     * @var array{page: int}
     */
    public array $pageJumpData = [
        'page' => 1,
    ];

    public function mount(): void
    {
        $this->getSchema('pageJumpForm')?->fill([
            'page' => $this->pageNumber,
        ]);
    }

    public function updatedPageNumber(): void
    {
        if ($this->pageNumber < 1) {
            $this->pageNumber = 1;
        }

        $this->pageJumpData['page'] = $this->pageNumber;
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

    public function pageJumpForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('pageJumpData')
            ->components([
                TextInput::make('page')
                    ->hiddenLabel()
                    ->type('number')
                    ->inputMode('numeric')
                    ->minValue(1)
                    ->maxValue(fn (): int => max(1, $this->maxPage))
                    ->suffix(fn (): string => max(1, $this->maxPage).' / ')
                    ->extraFieldWrapperAttributes([
                        'id' => 'quran-reader-page-counter-field',
                        'class' => 'quran-page-counter-field',
                    ])
                    ->extraInputAttributes([
                        'id' => 'quran-reader-page-counter-input',
                        'x-model.number' => 'pageInput',
                        'x-on:input' => 'onPageInputInput()',
                        'x-on:blur' => 'onPageInputBlur()',
                        'x-on:keydown.enter.prevent' => 'onPageInputCommit({ force: false, commitNow: true, source: "page-input-enter" })',
                        'x-bind:max' => 'Math.max(1, maxPage)',
                        'class' => 'quran-page-counter-input tabular-nums',
                    ], merge: true),
            ]);
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
                        'x-model.debounce.220ms' => 'search.query',
                        'x-on:input.debounce.220ms' => 'updateSearchResults()',
                        'x-on:keydown.enter.prevent' => 'confirmSearchSelection()',
                        'autocomplete' => 'off',
                        'class' => 'relative top-[0.25rem]',
                    ], merge: true),
            ])
            ->modalContentFooter(fn (): View => view('livewire.quran-app.search-modal'));
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
}
