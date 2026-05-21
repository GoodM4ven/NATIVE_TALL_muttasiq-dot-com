<?php

declare(strict_types=1);

use App\Services\Quran\QuranReaderDataService;
use Illuminate\Support\Facades\Schema;

test('quran reader search uses a reader-owned filament modal and custom chunked search partial', function () {
    $readerSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $layoutSource = file_get_contents(resource_path('views/components/app.blade.php'));
    $searchModalSource = file_get_contents(
        resource_path('views/components/partials/quran-app/search-modal.blade.php'),
    );

    expect($readerSource)->not->toBeFalse()
        ->and($readerSource)->toContain('public function searchQuranAction(): Action')
        ->and($readerSource)->toContain("'id' => 'quran-reader-search-modal'")
        ->and($readerSource)->toContain('<x-partials.quran-app.search-modal />');

    expect($layoutSource)->not->toBeFalse()
        ->and($layoutSource)->not->toContain("@livewire('livewire-ui-spotlight')");

    expect($searchModalSource)->not->toBeFalse()
        ->and($searchModalSource)->toContain('id="quran-reader-search-input"')
        ->and($searchModalSource)->toContain('wire:stream="quran-search-results-stream"')
        ->and($searchModalSource)->toContain('x-for="chunk in searchResultChunks()"')
        ->and($searchModalSource)->toContain('data-quran-surah-grid');
});

test('quran reader triggers the search modal through the reader action flow', function () {
    $surahQuickNavScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/reader-navigation-fit-surah-quick-nav-and-burst.js'),
    );
    $managerAndSearchActionsScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/manager-and-search-actions-ui-and-local-index.js'),
    );
    $readerBladeSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));

    expect($surahQuickNavScriptSource)->not->toBeFalse()
        ->and($surahQuickNavScriptSource)->toContain('void this.openSearchModal();');

    expect($managerAndSearchActionsScriptSource)->not->toBeFalse()
        ->and($managerAndSearchActionsScriptSource)->toContain(
            "return await this.openManagerModalAction('searchQuran', [this.searchModalId]);",
        )
        ->and($managerAndSearchActionsScriptSource)->not->toContain('toggle-spotlight');

    expect($readerBladeSource)->not->toBeFalse()
        ->and($readerBladeSource)->toContain("searchModalId: @js('quran-reader-search-modal')")
        ->and($readerBladeSource)->not->toContain('quran-spotlight-search-go-page');
});

test('quran search endpoint returns the rewritten ayah and surah stage names', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $items = $service->search('آل عمران', 20);
    $strategies = collect($items)->pluck('match_strategy')->filter()->values()->all();

    expect($strategies)->not->toBeEmpty()
        ->and(collect($strategies)->every(static fn (string $strategy): bool => in_array($strategy, [
            'surah_exact',
            'surah_close',
            'ayah_exact',
            'ayah_close',
            'ayah_sarf',
            'ayah_jathr',
        ], true)))->toBeTrue();
});
