<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp;

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
                    ->live(onBlur: true)
                    ->suffix(fn (): string => '/ '.max(1, $this->maxPage))
                    ->extraFieldWrapperAttributes(['class' => 'quran-page-counter-field'])
                    ->extraInputAttributes([
                        'x-model.number' => 'pageInput',
                        'x-on:input' => 'onPageInputInput()',
                        'x-on:change.stop' => '$dispatch(\'quran-go-page\', { page: $event.target.value })',
                        'x-on:keydown.enter.prevent' => '$dispatch(\'quran-go-page\', { page: $event.target.value })',
                        'x-bind:max' => 'Math.max(1, maxPage)',
                        'class' => 'quran-page-counter-input tabular-nums',
                    ], merge: true),
            ]);
    }

    public function searchQuranAction(): Action
    {
        return Action::make('searchQuran')
            ->modalHeading('ابحث في القرآن الكريم')
            ->modalDescription('ابحث في الآيات أو انتقل مباشرة إلى السورة والموضع المناسب.')
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->extraModalWindowAttributes([
                'id' => 'quran-reader-search-modal',
            ])
            ->modalContent(fn (): View => view('livewire.quran-app.search-modal'));
    }

    public function render(): View
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $readerData = $readerDataService->resolvePage($this->pageNumber, $this->activeAyahIndex);
        $surahNames = $readerDataService->surahNames();
        $this->maxPage = max(1, $readerData['maxPage']);

        if (! $readerData['ready']) {
            return view('livewire.quran-app.reader', [
                ...$readerData,
                'surahNames' => $surahNames,
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
        ]);
    }
}
