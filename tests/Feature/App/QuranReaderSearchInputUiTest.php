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
            'surah_sarf',
            'ayah_exact',
            'ayah_close',
            'ayah_sarf',
            'ayah_jathr',
        ], true)))->toBeTrue();
});

test('quran search prefers streamed pipeline on web while keeping a native-safe stage worker fallback', function () {
    $readerSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $searchWorkerScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/manager-and-search-actions-warm-and-navigate.js'),
    );
    $searchStreamScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/search-and-modals-stream-and-results.js'),
    );
    $searchLifecycleScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/search-and-modals-lifecycle-and-state.js'),
    );

    expect($readerSource)->not->toBeFalse()
        ->and($readerSource)->toContain('#[Json]')
        ->and($readerSource)->toContain('public function searchSurahExact(')
        ->and($readerSource)->toContain('public function searchSurahClose(')
        ->and($readerSource)->toContain('public function searchSurahSarf(')
        ->and($readerSource)->toContain('public function searchAyahExact(')
        ->and($readerSource)->toContain('public function searchAyahClose(')
        ->and($readerSource)->toContain('public function searchAyahSarf(')
        ->and($readerSource)->toContain('public function searchAyahJathr(');

    expect($searchWorkerScriptSource)->not->toBeFalse()
        ->and($searchWorkerScriptSource)->toContain('$wire.streamSearch(')
        ->and($searchWorkerScriptSource)->toContain('const shouldUseStreamSearch =')
        ->and($searchWorkerScriptSource)->toContain('this.shouldUseStreamSearchPipeline()')
        ->and($searchWorkerScriptSource)->toContain('if (shouldUseStreamSearch)')
        ->and($searchWorkerScriptSource)->toContain('runWorkerFallbackSearch')
        ->and($searchWorkerScriptSource)->toContain('if (this.nativeRuntime) {')
        ->and($searchWorkerScriptSource)->toContain('for (const runWorker of workers)')
        ->and($searchWorkerScriptSource)->toContain('await Promise.all(')
        ->and($searchWorkerScriptSource)->toContain('$wire.searchSurahExact(')
        ->and($searchWorkerScriptSource)->toContain('$wire.searchSurahClose(')
        ->and($searchWorkerScriptSource)->toContain('$wire.searchSurahSarf(')
        ->and($searchWorkerScriptSource)->toContain('$wire.searchAyahExact(')
        ->and($searchWorkerScriptSource)->toContain('$wire.searchAyahClose(')
        ->and($searchWorkerScriptSource)->toContain('$wire.searchAyahSarf(')
        ->and($searchWorkerScriptSource)->toContain('$wire.searchAyahJathr(');

    expect($searchStreamScriptSource)->not->toBeFalse()
        ->and($searchStreamScriptSource)->toContain('shouldUseStreamSearchPipeline()')
        ->and($searchStreamScriptSource)->toContain('this.$store?.bp?.isTouch')
        ->and($searchStreamScriptSource)->toContain('if (!this.shouldUseStreamSearchPipeline()) {');

    expect($searchLifecycleScriptSource)->not->toBeFalse()
        ->and($searchLifecycleScriptSource)->toContain(
            "(normalizedKind === 'opening' || normalizedKind === 'opened') &&",
        )
        ->and($searchLifecycleScriptSource)->toContain('this.searchDestinationCueActive &&')
        ->and($searchLifecycleScriptSource)->toContain('!this._searchNavigationInFlight')
        ->and($searchLifecycleScriptSource)->toContain('this.deactivateSearchDestinationCue();');
});
