<?php

declare(strict_types=1);

it('wires quran reader entry points from main menu to hash navigation and view mount', function () {
    $menuSource = file_get_contents(resource_path('views/components/partials/main-menu.blade.php'));
    $homeSource = file_get_contents(resource_path('views/home.blade.php'));
    $colorfulBackgroundSource = file_get_contents(resource_path('views/components/partials/colorful-background.blade.php'));
    $quranGateSource = file_get_contents(resource_path('views/components/partials/quran-app/gate.blade.php'));
    $quranIndexSource = file_get_contents(resource_path('views/components/partials/quran-app/index.blade.php'));
    $quranReaderPartialSource = file_get_contents(
        resource_path('views/components/partials/quran-app/reader.blade.php'),
    );
    $quranReaderViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));
    $quranSearchModalViewSource = file_get_contents(resource_path('views/livewire/quran-app/search-modal.blade.php'));
    $quranReaderScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader.js'),
    );
    $quranReaderClassSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $quranReaderDataServiceSource = file_get_contents(app_path('Services/Quran/QuranReaderDataService.php'));
    $routesSource = file_get_contents(base_path('routes/web.php'));
    $appJsSource = file_get_contents(resource_path('js/app.js'));
    $filamentComponentsCssSource = file_get_contents(resource_path('css/core/filament/components.css'));

    expect($menuSource)->not->toBeFalse()
        ->and($menuSource)->toContain(":caption=\"'الكتاب'\"")
        ->and($menuSource)->toContain(":onClickCallback=\"'() => (\$viewNav(`quran-app-gate`))'\"");

    expect($homeSource)->not->toBeFalse()
        ->and($homeSource)->toContain("'quran-app-gate': {")
        ->and($homeSource)->toContain("'quran-app-tilawa': {")
        ->and($homeSource)->toContain("'quran-app-hifth': {")
        ->and($homeSource)->toContain("'quran-app-tadabbur': {")
        ->and($homeSource)->toContain("'#quran-app-gate': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-tilawa': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-hifth': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-tadabbur': () => runHashAction(() => {")
        ->and($homeSource)->toContain('views[`quran-app-tilawa`].isOpen')
        ->and($homeSource)->toContain('views[`quran-app-hifth`].isOpen')
        ->and($homeSource)->toContain('views[`quran-app-tadabbur`].isOpen')
        ->and($homeSource)->toContain('$viewNav(`quran-app-gate`)')
        ->and($homeSource)->toContain('<x-partials.quran-app.index />');

    expect($colorfulBackgroundSource)->not->toBeFalse()
        ->and($colorfulBackgroundSource)->toContain('views[`quran-app-tilawa`].isOpen')
        ->and($colorfulBackgroundSource)->toContain('views[`quran-app-hifth`].isOpen')
        ->and($colorfulBackgroundSource)->toContain('views[`quran-app-tadabbur`].isOpen')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/tilawa-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/hifth-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/tadabbur-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-tilawa-layer')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-hifth-layer')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-tadabbur-layer');

    expect($quranIndexSource)->not->toBeFalse()
        ->and($quranIndexSource)->toContain('<x-partials.quran-app.gate />')
        ->and($quranIndexSource)->toContain('<x-partials.quran-app.reader />');

    expect($quranGateSource)->not->toBeFalse()
        ->and($quranGateSource)->toContain('x-data="quranAppGate"')
        ->and($quranGateSource)->toContain('images/background/quran/tilawa.webp')
        ->and($quranGateSource)->toContain('images/background/quran/hifth.webp')
        ->and($quranGateSource)->toContain('images/background/quran/tadabbur.webp')
        ->and($quranGateSource)->toContain('quran-app-sector__media--tilawa')
        ->and($quranGateSource)->toContain('quran-app-sector__media--hifth')
        ->and($quranGateSource)->toContain('quran-app-sector__media--tadabbur')
        ->and($quranGateSource)->toContain('quran-app-gate-focal-dim')
        ->and($quranGateSource)->toContain('quran-app-gate-pointer')
        ->and($quranGateSource)->toContain('quran-app-sector__lock-shell')
        ->and($quranGateSource)->toContain('quran-app-gate-orbit')
        ->and($quranGateSource)->toContain('x-on:pointermove.passive="handlePointerMove($event)"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'tilawa\')"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'hifth\')"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'tadabbur\')"')
        ->and($quranGateSource)->not->toContain('M0 0 L50 53')
        ->and($quranGateSource)->not->toContain('M100 0 L50 53')
        ->and($quranGateSource)->not->toContain('quran-app-gate-needle');

    expect($quranReaderPartialSource)->not->toBeFalse()
        ->and($quranReaderPartialSource)->toContain('<livewire:quran-app.reader />')
        ->and($quranReaderPartialSource)->toContain("views['quran-app-tilawa'].isOpen")
        ->and($quranReaderPartialSource)->toContain("views['quran-app-hifth'].isOpen")
        ->and($quranReaderPartialSource)->toContain("views['quran-app-tadabbur'].isOpen");

    expect($quranReaderViewSource)->not->toBeFalse()
        ->and($quranReaderViewSource)->toContain('quran-ayah-line-run-rect')
        ->and($quranReaderViewSource)->toContain('quran-ayah-line-run-centered')
        ->and($quranReaderViewSource)->toContain('top: 0;')
        ->and($quranReaderViewSource)->toContain('x-data="quranAppReader({')
        ->and($quranReaderViewSource)->toContain("searchModalId: @js('quran-reader-search-modal')")
        ->and($quranReaderViewSource)->toContain("x-on:x-modal-opened.window=\"handleModalLifecycleEvent('opened', \$event)\"")
        ->and($quranReaderViewSource)->toContain("x-on:close-modal.window=\"handleModalLifecycleEvent('closing', \$event)\"")
        ->and($quranReaderViewSource)->toContain("x-on:x-modal-closed.window=\"handleModalLifecycleEvent('closed', \$event)\"")
        ->and($quranReaderViewSource)->toContain('x-on:control-panel-updated.window="applyControlPanelSettings($event.detail?.controlPanel ?? {})"')
        ->and($quranReaderViewSource)->toContain("\$wire.mountAction('searchQuran');")
        ->and($quranReaderViewSource)->toContain('class="quran-page-slider outline-none"')
        ->and($quranReaderViewSource)->toContain('x-bind:disabled="isLastNavigationPage()"')
        ->and($quranReaderViewSource)->toContain("'quran-swipe-hint-chev-static': isFirstNavigationPage()")
        ->and($quranReaderViewSource)->toContain("'quran-swipe-hint-chev-static': isLastNavigationPage()")
        ->and($quranReaderViewSource)->toContain("\$wire.mountAction('jumpToPage')")
        ->and($quranReaderViewSource)->toContain('<x-filament-actions::modals />')
        ->and($quranReaderViewSource)->toContain('x-on:pointerdown.passive="onSwipeStart($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:touchstart.passive="onSwipeStart($event)"')
        ->and($quranReaderViewSource)->toContain('x-bind:data-fit-state="isFittingPage ? \'fitting\' : \'ready\'"')
        ->and($quranReaderViewSource)->toContain('x-for="line in mushafLines"')
        ->and($quranReaderViewSource)->not->toContain('x-on:click="nextPage()"')
        ->and($quranReaderViewSource)->not->toContain('x-on:click="previousPage()"')
        ->and($quranReaderViewSource)->not->toContain("x-on:click=\"\$viewNav('quran-app-gate')\"");

    expect($quranSearchModalViewSource)->not->toBeFalse()
        ->and($quranSearchModalViewSource)->toContain('quran-search-shell')
        ->and($quranSearchModalViewSource)->toContain('quran-search-results-shell')
        ->and($quranSearchModalViewSource)->toContain('quran-surah-grid')
        ->and($quranSearchModalViewSource)->toContain('x-ref="surahDirectoryGrid"')
        ->and($quranSearchModalViewSource)->toContain("'quran-surah-tile--active': isSurahDirectoryEntryActive(entry)")
        ->and($quranSearchModalViewSource)->toContain('x-ref="searchResultsList"')
        ->and($quranSearchModalViewSource)->toContain('goToSearchResult(result)')
        ->and($quranSearchModalViewSource)->toContain('goToSurahFromDirectory(entry)');

    expect($quranReaderScriptSource)->not->toBeFalse()
        ->and($quranReaderScriptSource)->toContain('const wordPressHoldDelayMs = 750;')
        ->and($quranReaderScriptSource)->toContain("search: 'quran-reader-search-v3'")
        ->and($quranReaderScriptSource)->toContain('_lastPageInputCommitPage: 0')
        ->and($quranReaderScriptSource)->toContain('_skipNextSearchModalCloseLayout: false')
        ->and($quranReaderScriptSource)->toContain('deriveSurahDirectoryFromItems(items = [])')
        ->and($quranReaderScriptSource)->toContain('resetNavigationQueueForPriorityJump()')
        ->and($quranReaderScriptSource)->toContain("pages: 'quran-reader-pages-v7'")
        ->and($quranReaderScriptSource)->toContain('requestSearchModalClose({ skipLayout = false } = {})')
        ->and($quranReaderScriptSource)->toContain('isAyahClusterActive(cluster)');

    expect($quranReaderClassSource)->not->toBeFalse()
        ->and($quranReaderClassSource)->toContain('implements HasActions, HasSchemas')
        ->and($quranReaderClassSource)->toContain('use InteractsWithActions;')
        ->and($quranReaderClassSource)->toContain('use InteractsWithSchemas;')
        ->and($quranReaderClassSource)->toContain('public function searchQuranAction(): Action')
        ->and($quranReaderClassSource)->toContain("TextInput::make('search')")
        ->and($quranReaderClassSource)->toContain('public function jumpToPageAction(): Action')
        ->and($quranReaderClassSource)->toContain("->modalContentFooter(fn (): View => view('livewire.quran-app.search-modal'))")
        ->and($quranReaderClassSource)->toContain('->extraModalWindowAttributes([')
        ->and($quranReaderClassSource)->toContain("'id' => 'quran-reader-search-modal'")
        ->and($quranReaderClassSource)->toContain("'x-on:input' => '\$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number(\$event.target.value || 1) || 1)), Math.max(1, Number(\$event.target.max) || 1)));'")
        ->and($quranReaderClassSource)->toContain("'x-on:blur' => '\$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number(\$event.target.value || 1) || 1)), Math.max(1, Number(\$event.target.max) || 1)));'")
        ->and($quranReaderClassSource)->toContain("view('livewire.quran-app.reader'")
        ->and($quranReaderClassSource)->toContain('QuranReaderDataService');

    expect($quranReaderDataServiceSource)->not->toBeFalse()
        ->and($quranReaderDataServiceSource)->toContain('p\'.$pageNumber.\'.woff2')
        ->and($quranReaderDataServiceSource)->toContain("'format' => 'woff2'")
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-page-v10')
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-surah-directory-v2')
        ->and($quranReaderDataServiceSource)->toContain('injectSyntheticBasmallahAfterSurahHeaders')
        ->and($quranReaderDataServiceSource)->toContain('applyTargetedSurahHeaderCarryovers')
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-search-index-v1')
        ->and($quranReaderDataServiceSource)->toContain('prepareSearchTokens(array $tokens): array')
        ->and($quranReaderDataServiceSource)->toContain('collapseVocativeSpacingInPhrase(string $text): string')
        ->and($quranReaderDataServiceSource)->toContain('stripVocativeParticlesFromPhrase(string $text): string')
        ->and($quranReaderDataServiceSource)->toContain("selectRaw('verse_id, MIN(ayah_index) AS ayah_index')")
        ->and($quranReaderDataServiceSource)->toContain("->groupBy('verse_id')");

    expect($routesSource)->not->toBeFalse()
        ->and($routesSource)->toContain('p\'.$page.\'.woff2')
        ->and($routesSource)->toContain("'content_type' => 'font/woff2'")
        ->and($routesSource)->toContain("'Content-Type' => \$contentType")
        ->and($routesSource)->toContain('/quran-surah-header-font')
        ->and($routesSource)->toContain('/quran-reader/pages/{page}.json')
        ->and($routesSource)->toContain('/quran-reader/search-index.json');

    expect($appJsSource)->not->toBeFalse()
        ->and($appJsSource)->toContain("import './support/alpine/data/quran-app-gate';")
        ->and($appJsSource)->toContain("import './packages/auto-animate';")
        ->and($appJsSource)->toContain("import './support/alpine/data/quran-app-reader';");

    expect($filamentComponentsCssSource)->not->toBeFalse()
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-search-modal')
        ->and($filamentComponentsCssSource)->toContain('.quran-search-shell')
        ->and($filamentComponentsCssSource)->toContain('.quran-page-counter-field')
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-page-counter-input')
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-search-input')
        ->and($filamentComponentsCssSource)->toContain('.quran-surah-grid-caption::before')
        ->and($filamentComponentsCssSource)->toContain('.fi-input-wrp-suffix .fi-input-wrp-label');
});

it('registers qpc page font route contract used by quran reader pages', function () {
    expect(route('qpc-v2-font', ['page' => 1], false))->toBe('/qpc-v2-fonts/1.ttf');
    expect(route('quran-surah-header-font', [], false))->toBe('/quran-surah-header-font');
    expect(route('quran-reader-page-data', ['page' => 1], false))->toBe('/quran-reader/pages/1.json');
    expect(route('quran-reader-search-index', [], false))->toBe('/quran-reader/search-index.json');
});

it('returns matches for legacy orthography phrases in quran search endpoint', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $query = 'يا بني أقم الصلاة';
    $response = $this->getJson(route('quran-reader-search-index', ['q' => $query], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and(collect($items)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 31
                && (int) ($item['ayah_number'] ?? 0) === 17,
        ))->toBeTrue();
});

it('injects visible basmallah lines under late-page surah headers', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);
    $page = $service->resolvePage(604);
    $basmallahLines = collect($page['mushafLines'] ?? [])
        ->filter(static fn (array $line): bool => ($line['line_type'] ?? '') === 'basmallah')
        ->values();

    expect($basmallahLines)->toHaveCount(3)
        ->and($basmallahLines->every(static function (array $line): bool {
            $text = trim((string) ($line['text'] ?? ''));
            $words = is_array($line['words'] ?? null) ? $line['words'] : [];
            $allGlyphWords = $words !== [] && collect($words)->every(
                static fn (array $word): bool => (bool) ($word['is_glyph'] ?? false),
            );

            return $text !== '' && $allGlyphWords;
        }))->toBeTrue();
});
