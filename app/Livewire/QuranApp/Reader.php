<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp;

use App\Services\Quran\QuranReaderDataService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Reader extends Component
{
    public int $pageNumber = 1;

    public int $activeAyahIndex = 0;

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

    public function render(): View
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $readerData = $readerDataService->resolvePage($this->pageNumber, $this->activeAyahIndex);

        if (! $readerData['ready']) {
            return view('livewire.quran-app.reader', $readerData);
        }

        $normalizedPage = $readerData['pageNumber'];

        if ($normalizedPage !== $this->pageNumber) {
            $this->pageNumber = $normalizedPage;
        }

        $effectiveAyahIndex = $readerData['activeAyahIndex'];

        if ($effectiveAyahIndex > 0 && $effectiveAyahIndex !== $this->activeAyahIndex) {
            $this->activeAyahIndex = $effectiveAyahIndex;
        }

        return view('livewire.quran-app.reader', $readerData);
    }
}
