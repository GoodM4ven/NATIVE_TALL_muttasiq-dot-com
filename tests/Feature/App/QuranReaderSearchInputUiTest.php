<?php

declare(strict_types=1);

test('quran search input clears immediately when native search clear button is used', function () {
    $readerSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $readerSearchActionTraitSource = file_get_contents(
        app_path('Livewire/QuranApp/Concerns/InteractsWithQuranSearchAction.php'),
    );

    expect($readerSource)->not->toBeFalse()
        ->and($readerSource)->toContain('use InteractsWithQuranSearchAction;')
        ->and($readerSource)->not->toContain("Blade::render('<x-partials.quran-app.search-modal />')");

    expect($readerSearchActionTraitSource)->not->toBeFalse()
        ->and($readerSearchActionTraitSource)->toContain("Action::make('searchQuran')")
        ->and($readerSearchActionTraitSource)->toContain("Select::make('surah_number')")
        ->and($readerSearchActionTraitSource)->toContain("TextInput::make('query')")
        ->and($readerSearchActionTraitSource)->toContain("'id' => 'quran-reader-search-modal'")
        ->and($readerSearchActionTraitSource)->toContain("source: 'search-modal'");
});

test('quran modal navigation keeps immersive captions and same-page refit paths ready', function () {
    $surahQuickNavScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/reader-navigation-fit-surah-quick-nav-and-burst.js'),
    );
    $managerAndSearchActionsScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/manager-and-search-actions-ui-and-local-index.js'),
    );
    $searchLifecycleScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/search-and-modals-lifecycle-and-state.js'),
    );

    expect($surahQuickNavScriptSource)->not->toBeFalse()
        ->and($surahQuickNavScriptSource)->toContain('void this.openSearchModal();')
        ->and($surahQuickNavScriptSource)->not->toContain('this.warmSearchIndex();');

    expect($managerAndSearchActionsScriptSource)->not->toBeFalse()
        ->and($managerAndSearchActionsScriptSource)->toContain('async openSearchModal()')
        ->and($managerAndSearchActionsScriptSource)->toContain("openManagerModalAction('searchQuran'");

    expect($searchLifecycleScriptSource)->not->toBeFalse()
        ->and($searchLifecycleScriptSource)->toContain("this.traceSearchModalLifecycle('opened');")
        ->and($searchLifecycleScriptSource)->toContain("this.traceSearchModalLifecycle('closed');")
        ->and($searchLifecycleScriptSource)->not->toContain(
            'this._searchModalOpenInFlight = Promise.resolve(this.handleSearchModalOpened())',
        );
});
