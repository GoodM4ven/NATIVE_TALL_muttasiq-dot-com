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
                        'x-model.debounce.700ms' => 'search.query',
                        'x-on:input.debounce.700ms' => 'updateSearchResults()',
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
