<?php

declare(strict_types=1);

it('wires quran reader entry points from main menu to hash navigation and view mount', function () {
    $menuSource = file_get_contents(resource_path('views/components/partials/main-menu.blade.php'));
    $homeSource = file_get_contents(resource_path('views/home.blade.php'));
    $buttonsStackSource = file_get_contents(resource_path('views/components/buttons-stack.blade.php'));
    $colorfulBackgroundSource = file_get_contents(resource_path('views/components/partials/colorful-background.blade.php'));
    $quranGateSource = file_get_contents(resource_path('views/components/partials/quran-app/gate.blade.php'));
    $quranIndexSource = file_get_contents(resource_path('views/components/partials/quran-app/index.blade.php'));
    $quranReaderPartialSource = file_get_contents(
        resource_path('views/components/partials/quran-app/reader.blade.php'),
    );
    $quranReaderViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));
    $quranSearchModalViewSource = file_get_contents(resource_path('views/components/partials/quran-app/search-modal.blade.php'));
    $quranHistoryModalViewSource = file_get_contents(resource_path('views/components/partials/quran-app/history-modal.blade.php'));
    $quranBookmarksModalViewSource = file_get_contents(resource_path('views/components/partials/quran-app/bookmarks-modal.blade.php'));
    $quranReaderScriptSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader.js'),
    );
    $quranReaderClassSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $navigationHistoryActionSource = (string) \Illuminate\Support\Str::of($quranReaderClassSource)
        ->after('public function navigationHistoryAction(): Action')
        ->before('public function bookmarksManagerAction(): Action');
    $bookmarksManagerActionSource = (string) \Illuminate\Support\Str::of($quranReaderClassSource)
        ->after('public function bookmarksManagerAction(): Action')
        ->before('public function render(): View');
    $quranReaderDataServiceSource = file_get_contents(app_path('Services/Quran/QuranReaderDataService.php'));
    $settingModelSource = file_get_contents(app_path('Models/Setting.php'));
    $controlPanelSettingsTabSource = file_get_contents(app_path('Services/Traits/HasControlPanelSettingsTab.php'));
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

    expect($buttonsStackSource)->not->toBeFalse()
        ->and($buttonsStackSource)->toContain('syncQuranManagerModalState(isOpen)')
        ->and($buttonsStackSource)->toContain('x-on:quran-manager-modals-visibility.window')
        ->and($buttonsStackSource)->toContain('shouldHideStackItems()')
        ->and($buttonsStackSource)->toContain('applyStackItemVisibility(item)');

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
        ->and($quranGateSource)->toContain('quran-app-sector__chip-lock')
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
        ->and($quranReaderViewSource)->toContain("jumpPageModalId: @js('quran-reader-jump-page-modal')")
        ->and($quranReaderViewSource)->toContain("historyModalId: @js('quran-reader-history-modal')")
        ->and($quranReaderViewSource)->toContain("bookmarksModalId: @js('quran-reader-bookmarks-modal')")
        ->and($quranReaderViewSource)->toContain("x-on:x-modal-opened.window=\"handleModalLifecycleEvent('opened', \$event)\"")
        ->and($quranReaderViewSource)->toContain("x-on:close-modal.window=\"handleModalLifecycleEvent('closing', \$event)\"")
        ->and($quranReaderViewSource)->toContain("x-on:x-modal-closed.window=\"handleModalLifecycleEvent('closed', \$event)\"")
        ->and($quranReaderViewSource)->toContain('x-on:control-panel-updated.window="applyControlPanelSettings($event.detail?.controlPanel ?? {})"')
        ->and($quranReaderViewSource)->toContain("\$wire.mountAction('searchQuran');")
        ->and($quranReaderViewSource)->toContain("\$wire.mountAction('navigationHistory')")
        ->and($quranReaderViewSource)->toContain('transform: rotate(360deg);')
        ->and($quranReaderViewSource)->toContain('x-on:pointerdown="onBookmarkButtonPointerDown($event)"')
        ->and($quranReaderViewSource)->toContain(
            'x-on:click.prevent="if (!wirdModeActive) { onBookmarkButtonClick() }"',
        )
        ->and($quranReaderViewSource)->toContain(
            "x-bind:class=\"{ 'quran-bookmark-toggle-button--bookmarked': isCurrentPageBookmarked() }\"",
        )
        ->and($quranReaderViewSource)->toContain(
            "x-bind:aria-pressed=\"isCurrentPageBookmarked() ? 'true' : 'false'\"",
        )
        ->and($quranReaderViewSource)->toContain('data-quran-copy-popover')
        ->and($quranReaderViewSource)->toContain('x-show="copyFeedback.visible"')
        ->and($quranReaderViewSource)->toContain('class="quran-page-slider outline-none"')
        ->and($quranReaderViewSource)->toContain('data-quran-wird-toggle')
        ->and($quranReaderViewSource)->toContain('toggleWirdMode()')
        ->and($quranReaderViewSource)->toContain('x-bind:style="wirdProgressBarStyle()"')
        ->and($quranReaderViewSource)->toContain('wirdProgressPercentLabel()')
        ->and($quranReaderViewSource)->toContain('wirdProgressCounterLabel()')
        ->and($quranReaderViewSource)->toContain('x-bind:class="{ \'quran-soorah-trigger--disabled\': wirdModeActive }"')
        ->and($quranReaderViewSource)->toContain('x-cloak')
        ->and($quranReaderViewSource)->toContain("'quran-reader--wird-active': wirdModeActive")
        ->and($quranReaderViewSource)->toContain('x-bind:disabled="wirdModeActive"')
        ->and($quranReaderViewSource)->toContain('x-bind:disabled="!wirdModeActive && isLastNavigationPage()"')
        ->and($quranReaderViewSource)->toContain("'quran-swipe-hint-chev-static': isFirstNavigationPage()")
        ->and($quranReaderViewSource)->toContain("'quran-swipe-hint-chev-static': !wirdModeActive && isLastNavigationPage()")
        ->and($quranReaderViewSource)->toContain("\$wire.mountAction('jumpToPage')")
        ->and($quranReaderViewSource)->toContain('<x-filament-actions::modals />')
        ->and($quranReaderViewSource)->toContain('x-on:pointerdown.passive="onSwipeStart($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:pointermove.window.passive="onSwipeMove($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:touchstart.passive="onSwipeStart($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:touchmove.window.passive="onSwipeMove($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:keydown.left.window.prevent="onGlobalArrowNavigate(\'left\', $event)"')
        ->and($quranReaderViewSource)->toContain('x-on:keydown.right.window.prevent="onGlobalArrowNavigate(\'right\', $event)"')
        ->and($quranReaderViewSource)->toContain('x-on:pointerup.window.passive="onWordPointerUp($event)"')
        ->and($quranReaderViewSource)->toContain('data-quran-word-button')
        ->and($quranReaderViewSource)->toContain('x-bind:data-quran-surah-number=')
        ->and($quranReaderViewSource)->toContain('quran-segment-cluster-copied')
        ->and($quranReaderViewSource)->toContain('quran-segment-copied')
        ->and($quranReaderViewSource)->toContain(
            'x-bind:data-fit-state="typeof pageFitState === \'function\' ? pageFitState() : (isFittingPage ? \'fitting\' : \'ready\')"',
        )
        ->and($quranReaderViewSource)->toContain('x-for="line in mushafLines"')
        ->and($quranReaderViewSource)->toContain('x-bind:data-quran-line-number="Number(line?.line_number ?? 0)"')
        ->and($quranReaderViewSource)->toContain('x-bind:data-quran-line-type="String(line?.line_type ?? \'\')"')
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

    expect($quranHistoryModalViewSource)->not->toBeFalse()
        ->and($quranHistoryModalViewSource)->toContain('navigationHistory.length')
        ->and($quranHistoryModalViewSource)->toContain('goToHistoryEntry(entry)')
        ->and($quranHistoryModalViewSource)->toContain('historyEntrySurahName(entry)')
        ->and($quranHistoryModalViewSource)->toContain('setHistoryTagDraft(entry.id, $event.target.value)')
        ->and($quranHistoryModalViewSource)->toContain('commitHistoryTagDraft(entry.id)')
        ->and($quranHistoryModalViewSource)->toContain('clearNavigationHistory()')
        ->and($quranHistoryModalViewSource)->toContain('x-ref="historyRowsList"')
        ->and($quranHistoryModalViewSource)->toContain('historyRowEffectClass(entry)');

    expect($quranBookmarksModalViewSource)->not->toBeFalse()
        ->and($quranBookmarksModalViewSource)->toContain('bookmarks.length')
        ->and($quranBookmarksModalViewSource)->toContain('goToBookmark(bookmark)')
        ->and($quranBookmarksModalViewSource)->toContain('updateBookmarkNote(bookmark.id, $event.target.value)')
        ->and($quranBookmarksModalViewSource)->toContain('commitBookmarkTagDraft(bookmark.id)')
        ->and($quranBookmarksModalViewSource)->toContain('replaceBookmarkPage(bookmark.id)')
        ->and($quranBookmarksModalViewSource)->toContain('removeBookmark(bookmark.id)')
        ->and($quranBookmarksModalViewSource)->toContain('x-ref="bookmarksRowsList"')
        ->and($quranBookmarksModalViewSource)->toContain('bookmarkRowEffectClass(bookmark)');

    expect($quranReaderScriptSource)->not->toBeFalse()
        ->and($quranReaderScriptSource)->toContain('const wordPressHoldDelayMs = 750;')
        ->and($quranReaderScriptSource)->toContain('const wordPressDragThresholdPx = 14;')
        ->and($quranReaderScriptSource)->toContain('const bookmarkHoldDelayMs = 680;')
        ->and($quranReaderScriptSource)->toContain('const copiedHighlightVisibleDurationMs = 3000;')
        ->and($quranReaderScriptSource)->toContain('const wordClickSuppressionResetMs = 180;')
        ->and($quranReaderScriptSource)->toContain('const navigationBurstInputThresholdMs = 140;')
        ->and($quranReaderScriptSource)->toContain('const navigationBurstSettleDelayMs = 72;')
        ->and($quranReaderScriptSource)->toContain('const navigationHistoryLimit = 100;')
        ->and($quranReaderScriptSource)->toContain('const defaultWesternNumerals = Object.freeze([')
        ->and($quranReaderScriptSource)->toContain('const defaultArabicNumerals = Object.freeze([')
        ->and($quranReaderScriptSource)->toContain("const lastPageStorageKey = 'quran-reader-last-page-v1';")
        ->and($quranReaderScriptSource)->toContain("const navigationHistoryStorageKey = 'quran-reader-navigation-history-v1';")
        ->and($quranReaderScriptSource)->toContain("const bookmarksStorageKey = 'quran-reader-bookmarks-v1';")
        ->and($quranReaderScriptSource)->toContain("const wirdProgressStorageKey = 'quran-reader-wird-progress-v1';")
        ->and($quranReaderScriptSource)->toContain("wirdFrequencyMode: 'quran_wird_frequency_mode'")
        ->and($quranReaderScriptSource)->toContain("wirdKhatmatTarget: 'quran_wird_khatmat_target'")
        ->and($quranReaderScriptSource)->toContain('ensureWirdDailyRecord({ forceRebuild = false } = {})')
        ->and($quranReaderScriptSource)->toContain('async enterWirdMode()')
        ->and($quranReaderScriptSource)->toContain('async exitWirdMode({ restoreNormalPage = true, reason = \'manual\' } = {})')
        ->and($quranReaderScriptSource)->toContain('async stepWird(direction = \'next\', source = \'generic\')')
        ->and($quranReaderScriptSource)->toContain("search: 'quran-reader-search-v3'")
        ->and($quranReaderScriptSource)->toContain('_lastPageInputCommitPage: 0')
        ->and($quranReaderScriptSource)->toContain('_skipNextSearchModalCloseLayout: false')
        ->and($quranReaderScriptSource)->toContain('_navigationBurstFreezeUntil: 0')
        ->and($quranReaderScriptSource)->toContain('_activePageAbortController: null')
        ->and($quranReaderScriptSource)->toContain('[this.searchActionModalId]')
        ->and($quranReaderScriptSource)->toContain('document.querySelectorAll(`[data-fi-modal-id="${escapedId}"]`)')
        ->and($quranReaderScriptSource)->toContain('const matchedKnownId = modalId !== \'\' && knownIds.includes(modalId);')
        ->and($quranReaderScriptSource)->toContain('if (matchedKnownId) {')
        ->and($quranReaderScriptSource)->toContain('deriveSurahDirectoryFromItems(items = [])')
        ->and($quranReaderScriptSource)->toContain('registerNavigationBurst(source = \'generic\')')
        ->and($quranReaderScriptSource)->toContain('navigationBurstRemainingMsFor(source = \'generic\')')
        ->and($quranReaderScriptSource)->toContain('resolveNavigationCommitDelay(source = \'generic\', delayMs = navigationSettleDelayMs)')
        ->and($quranReaderScriptSource)->toContain(
            'shouldSuspendPageCounterMorph({ source = \'generic\' } = {})',
        )
        ->and($quranReaderScriptSource)->toContain('clampPage(value, maxPage = this.maxPage)')
        ->and($quranReaderScriptSource)->toContain('resetCurrentPageFitStyles()')
        ->and($quranReaderScriptSource)->toContain(
            'shouldDeferPostModalTargetFit(pageNumber = this.pageNumber, source = \'generic\')',
        )
        ->and($quranReaderScriptSource)->toContain('isNavigationBurstActive()')
        ->and($quranReaderScriptSource)->toContain('runFitPageToViewportLazily()')
        ->and($quranReaderScriptSource)->toContain(
            'const uniquePriorityPages = Array.from(new Set(normalizedPages));',
        )
        ->and($quranReaderScriptSource)->toContain('abortActivePageLoad()')
        ->and($quranReaderScriptSource)->toContain('beginActivePageLoadAbortController()')
        ->and($quranReaderScriptSource)->toContain("error?.name === 'AbortError'")
        ->and($quranReaderScriptSource)->toContain('resetNavigationQueueForPriorityJump()')
        ->and($quranReaderScriptSource)->toContain("pages: 'quran-reader-pages-v13'")
        ->and($quranReaderScriptSource)->toContain("fonts: 'quran-reader-fonts-v4'")
        ->and($quranReaderScriptSource)->toContain('requestSearchModalClose({ skipLayout = false } = {})')
        ->and($quranReaderScriptSource)->toContain('recordNavigationHistory({')
        ->and($quranReaderScriptSource)->toContain('toggleCurrentPageBookmark()')
        ->and($quranReaderScriptSource)->toContain('openBookmarksManager()')
        ->and($quranReaderScriptSource)->toContain('jumpPageModalId:')
        ->and($quranReaderScriptSource)->toContain('syncJumpPageModalInputValue({ shouldSelect = true } = {})')
        ->and($quranReaderScriptSource)->toContain('managerRowEffects: {')
        ->and($quranReaderScriptSource)->toContain('markManagerRowReplaced(collection, itemId)')
        ->and($quranReaderScriptSource)->toContain('dispatchManagerModalsVisibilityState()')
        ->and($quranReaderScriptSource)->toContain('ensureHistoryRowsAnimations()')
        ->and($quranReaderScriptSource)->toContain('ensureBookmarksRowsAnimations()')
        ->and($quranReaderScriptSource)->toContain('quran-manager-modals-visibility')
        ->and($quranReaderScriptSource)->toContain('goToHistoryEntry(entry)')
        ->and($quranReaderScriptSource)->toContain('goToBookmark(bookmark)')
        ->and($quranReaderScriptSource)->toContain('lineWordGapAdjustments: {}')
        ->and($quranReaderScriptSource)->toContain('rebalanceRectangularAyahLineWordSpacing()')
        ->and($quranReaderScriptSource)->toContain('--quran-word-gap-extra:')
        ->and($quranReaderScriptSource)->toContain('isAyahClusterActive(cluster)')
        ->and($quranReaderScriptSource)->toContain('copyWordSelection(word, activationAnchor = null)')
        ->and($quranReaderScriptSource)->toContain('copyAyahSelection(ayahIndex, activationAnchor = null)')
        ->and($quranReaderScriptSource)->toContain('copyDraggedSelection(activationAnchor = null)')
        ->and($quranReaderScriptSource)->toContain('composeDraggedSelectionText()')
        ->and($quranReaderScriptSource)->toContain('composeDraggedWordSelectionText()')
        ->and($quranReaderScriptSource)->toContain('composeDraggedAyahSelectionText()')
        ->and($quranReaderScriptSource)->toContain('ayahSplitterToken(ayahIndex, fallbackAyahNumber = 0)')
        ->and($quranReaderScriptSource)->toContain('copiedHighlights: {')
        ->and($quranReaderScriptSource)->toContain('applyCopiedHighlights({ words = [], ayahIndexes = [] } = {})')
        ->and($quranReaderScriptSource)->toContain('clearCopiedHighlights()')
        ->and($quranReaderScriptSource)->toContain('setWordClickSuppression(')
        ->and($quranReaderScriptSource)->toContain('isWordCopied(word)')
        ->and($quranReaderScriptSource)->toContain('isAyahClusterCopied(cluster)')
        ->and($quranReaderScriptSource)->toContain('writeClipboardText(text)')
        ->and($quranReaderScriptSource)->toContain("preserveHarakatOnCopy: 'does_quran_preserve_harakat_on_copy'")
        ->and($quranReaderScriptSource)->toContain("appendSurahAffixOnMultiCopy: 'does_quran_append_surah_affix_on_multi_copy'")
        ->and($quranReaderScriptSource)->toContain("appendSurahAffixAlwaysOnCopy: 'does_quran_append_surah_affix_always_on_copy'")
        ->and($quranReaderScriptSource)->toContain("useWesternNumerals: 'does_use_western_numerals'")
        ->and($quranReaderScriptSource)->toContain('doesPreserveHarakatOnCopy: true')
        ->and($quranReaderScriptSource)->toContain('doesAppendSurahAffixOnMultiCopy: true')
        ->and($quranReaderScriptSource)->toContain('doesAppendSurahAffixAlwaysOnCopy: false')
        ->and($quranReaderScriptSource)->toContain('doesUseWesternNumerals: true')
        ->and($quranReaderScriptSource)->toContain('resolveControlPanelSettingsWithUserOverrides(defaultSettings = {})')
        ->and($quranReaderScriptSource)->toContain('typeof window.getUserSettingsOverrides !== \'function\'')
        ->and($quranReaderScriptSource)->toContain('selectedDraggedSurahNumbers()')
        ->and($quranReaderScriptSource)->toContain('shouldAppendDraggedSurahAffix()')
        ->and($quranReaderScriptSource)->toContain('draggedSelectionSurahAffixes()')
        ->and($quranReaderScriptSource)->toContain('draggedSelectionSurahAffix()')
        ->and($quranReaderScriptSource)->toContain('return this.draggedSelectionSurahAffixes()[0] ?? null;')
        ->and($quranReaderScriptSource)->toContain('formatAyahTokenNumber(value)')
        ->and($quranReaderScriptSource)->toContain('return `(${this.formatAyahTokenNumber(ayahNumber)})`;')
        ->and($quranReaderScriptSource)->toContain('normalizeCopiedText(text)')
        ->and($quranReaderScriptSource)->toContain('copyFeedbackStyle()');

    expect($quranReaderClassSource)->not->toBeFalse()
        ->and($quranReaderClassSource)->toContain('implements HasActions, HasSchemas')
        ->and($quranReaderClassSource)->toContain('use InteractsWithActions;')
        ->and($quranReaderClassSource)->toContain('use InteractsWithSchemas;')
        ->and($quranReaderClassSource)->toContain('public function searchQuranAction(): Action')
        ->and($quranReaderClassSource)->toContain("TextInput::make('search')")
        ->and($quranReaderClassSource)->toContain('public function jumpToPageAction(): Action')
        ->and($quranReaderClassSource)->toContain('public function navigationHistoryAction(): Action')
        ->and($quranReaderClassSource)->toContain('public function bookmarksManagerAction(): Action')
        ->and($quranReaderClassSource)->toContain('->modalContentFooter(')
        ->and($quranReaderClassSource)->toContain("Blade::render('<x-partials.quran-app.search-modal />')")
        ->and($quranReaderClassSource)->toContain("Blade::render('<x-partials.quran-app.history-modal />')")
        ->and($quranReaderClassSource)->toContain("Blade::render('<x-partials.quran-app.bookmarks-modal />')")
        ->and($quranReaderClassSource)->toContain('->extraModalWindowAttributes([')
        ->and($quranReaderClassSource)->toContain("'id' => 'quran-reader-search-modal'")
        ->and($quranReaderClassSource)->toContain("'id' => self::HISTORY_MODAL_ID")
        ->and($quranReaderClassSource)->toContain("'id' => self::BOOKMARKS_MODAL_ID")
        ->and($quranReaderClassSource)->toContain('->modalAutofocus(true)')
        ->and($quranReaderClassSource)->toContain('->autofocus()')
        ->and($quranReaderClassSource)->toContain("'x-on:focus' => '\$event.target.select();'")
        ->and($quranReaderClassSource)->toContain("'x-on:input' => '\$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number(\$event.target.value || 1) || 1)), Math.max(1, Number(\$event.target.max) || 1)));'")
        ->and($quranReaderClassSource)->toContain("'x-on:blur' => '\$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number(\$event.target.value || 1) || 1)), Math.max(1, Number(\$event.target.max) || 1)));'")
        ->and($quranReaderClassSource)->toContain("view('livewire.quran-app.reader'")
        ->and($quranReaderClassSource)->toContain('Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY')
        ->and($quranReaderClassSource)->toContain('Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY')
        ->and($quranReaderClassSource)->toContain('Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY')
        ->and($quranReaderClassSource)->toContain('Setting::DOES_USE_WESTERN_NUMERALS')
        ->and($quranReaderClassSource)->toContain('Setting::QURAN_WIRD_FREQUENCY_MODE')
        ->and($quranReaderClassSource)->toContain('Setting::QURAN_WIRD_KHATMAT_TARGET')
        ->and($quranReaderClassSource)->toContain("'preserveHarakatOnCopy' =>")
        ->and($quranReaderClassSource)->toContain("'appendSurahAffixOnMultiCopy' =>")
        ->and($quranReaderClassSource)->toContain("'appendSurahAffixAlwaysOnCopy' =>")
        ->and($quranReaderClassSource)->toContain("'useWesternNumerals' =>")
        ->and($quranReaderClassSource)->toContain("'wirdFrequencyMode' =>")
        ->and($quranReaderClassSource)->toContain("'wirdKhatmatTarget' =>")
        ->and($quranReaderClassSource)->toContain("'numeralCharacters' => [")
        ->and($quranReaderClassSource)->toContain('use GoodMaven\Arabicable\Enums\ArabicSpecialCharacters;')
        ->and($quranReaderClassSource)->toContain('\\arabicable_special_characters(only: ArabicSpecialCharacters::IndianNumerals)')
        ->and($quranReaderClassSource)->toContain('QuranReaderDataService');

    expect($navigationHistoryActionSource)
        ->toContain('modalHeading(\'سجل التنقّل\')')
        ->not->toContain('->slideOver()');

    expect($bookmarksManagerActionSource)
        ->toContain('modalHeading(\'إدارة علامات الصفحات\')')
        ->toContain('->slideOver()');

    expect($quranReaderDataServiceSource)->not->toBeFalse()
        ->and($quranReaderDataServiceSource)->toContain('p\'.$pageNumber.\'.woff2')
        ->and($quranReaderDataServiceSource)->toContain("'format' => 'woff2'")
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-page-v19')
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-surah-directory-v2')
        ->and($quranReaderDataServiceSource)->toContain('injectSyntheticBasmallahAfterSurahHeaders')
        ->and($quranReaderDataServiceSource)->toContain('applyTargetedSurahHeaderCarryovers')
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-search-index-v1')
        ->and($quranReaderDataServiceSource)->toContain('use GoodMaven\Arabicable\Support\Quran\QuranSearchText;')
        ->and($quranReaderDataServiceSource)->toContain('prepareSearchTokens(array $tokens): array')
        ->and($quranReaderDataServiceSource)->toContain('return QuranSearchText::expandVariants($text);')
        ->and($quranReaderDataServiceSource)->toContain('return QuranSearchText::expandStrictExactPhraseVariants($text);')
        ->and($quranReaderDataServiceSource)->toContain("selectRaw('verse_id, MIN(ayah_index) AS ayah_index')")
        ->and($quranReaderDataServiceSource)->toContain("->groupBy('verse_id')");

    expect($settingModelSource)->not->toBeFalse()
        ->and($settingModelSource)->toContain("DOES_PRESERVE_HARAKAT_IN_DISPLAY = 'does_preserve_harakat_in_display'")
        ->and($settingModelSource)->toContain("DOES_QURAN_PRESERVE_HARAKAT_ON_COPY = 'does_quran_preserve_harakat_on_copy'")
        ->and($settingModelSource)->toContain("DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY = 'does_quran_append_surah_affix_on_multi_copy'")
        ->and($settingModelSource)->toContain("DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY = 'does_quran_append_surah_affix_always_on_copy'")
        ->and($settingModelSource)->toContain("QURAN_WIRD_FREQUENCY_MODE = 'quran_wird_frequency_mode'")
        ->and($settingModelSource)->toContain("QURAN_WIRD_KHATMAT_TARGET = 'quran_wird_khatmat_target'")
        ->and($settingModelSource)->toContain("DOES_USE_WESTERN_NUMERALS = 'does_use_western_numerals'")
        ->and($settingModelSource)->toContain("'default' => true")
        ->and($settingModelSource)->toContain('إظهار الحركات في النصوص العربية المعروضة')
        ->and($settingModelSource)->toContain('الحفاظ على الحركات عند نسخ نص الآيات')
        ->and($settingModelSource)->toContain('إضافة لاحقة السورة (~ [سورة ...]) عند النسخ المتعدد بين الآيات')
        ->and($settingModelSource)->toContain('إضافة لاحقة السورة (~ [سورة ...]) دائمًا عند النسخ بالسحب')
        ->and($settingModelSource)->toContain('وتيرة الوِرد: ختمات موزعة على الشهر أو هدف يومي مباشر')
        ->and($settingModelSource)->toContain('عدد الختمات المستهدفة للوِرد.')
        ->and($settingModelSource)->toContain('استخدام الأرقام العربية الغربية (123) بدل العربية الشرقية (١٢٣) في العرض');

    expect($controlPanelSettingsTabSource)->not->toBeFalse()
        ->and($controlPanelSettingsTabSource)->toContain('Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY')
        ->and($controlPanelSettingsTabSource)->toContain('Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY')
        ->and($controlPanelSettingsTabSource)->toContain('Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY')
        ->and($controlPanelSettingsTabSource)->toContain('Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY')
        ->and($controlPanelSettingsTabSource)->toContain('Setting::DOES_USE_WESTERN_NUMERALS')
        ->and($controlPanelSettingsTabSource)->toContain('Setting::QURAN_WIRD_FREQUENCY_MODE')
        ->and($controlPanelSettingsTabSource)->toContain('Setting::QURAN_WIRD_KHATMAT_TARGET')
        ->and($controlPanelSettingsTabSource)->toContain('Components\Radio::make(Setting::QURAN_WIRD_FREQUENCY_MODE)')
        ->and($controlPanelSettingsTabSource)->toContain('FusedGroup::make([');

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
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-history-modal')
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-bookmarks-modal')
        ->and($filamentComponentsCssSource)->toContain('.quran-manager-table')
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

    $legacySpellingResponse = $this->getJson(route('quran-reader-search-index', [
        'q' => 'والله يدعو إلى دار السلام',
    ], false));

    $legacySpellingResponse->assertSuccessful();

    $legacyItems = $legacySpellingResponse->json('items', []);

    expect($legacyItems)->toBeArray()->not->toBeEmpty()
        ->and(collect($legacyItems)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 10
                && (int) ($item['ayah_number'] ?? 0) === 25,
        ))->toBeTrue();
});

it('normalizes invisible directional chars in quran search queries while preserving exact phrase ranking', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $vocativeQueryWithZwnj = "ي\u{200C}بني أقم الصلاة";
    $vocativeResponse = $this->getJson(route('quran-reader-search-index', [
        'q' => $vocativeQueryWithZwnj,
    ], false));

    $vocativeResponse->assertSuccessful();

    $vocativeItems = $vocativeResponse->json('items', []);

    expect($vocativeItems)->toBeArray()->not->toBeEmpty()
        ->and(count($vocativeItems))->toBeGreaterThan(1)
        ->and(collect($vocativeItems)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 31
                && (int) ($item['ayah_number'] ?? 0) === 17
                && (string) ($item['match_strategy'] ?? '') === 'exact_phrase',
        ))->toBeTrue();

    $invocationQueryWithRlm = "وقال ربكم\u{200F} ادعوني أستجب لكم";
    $invocationResponse = $this->getJson(route('quran-reader-search-index', [
        'q' => $invocationQueryWithRlm,
    ], false));

    $invocationResponse->assertSuccessful();

    $invocationItems = $invocationResponse->json('items', []);

    expect($invocationItems)->toBeArray()->not->toBeEmpty()
        ->and((int) ($invocationItems[0]['surah_number'] ?? 0))->toBe(40)
        ->and((int) ($invocationItems[0]['ayah_number'] ?? 0))->toBe(60)
        ->and((string) ($invocationItems[0]['match_strategy'] ?? ''))->toBe('exact_phrase');
});

it('caches repeated quran search queries while preserving complete progress emission', function () {
    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    \Illuminate\Support\Facades\Cache::flush();

    $query = 'يا بني أقم الصلاة';
    $firstResults = $service->searchProgressively($query, 24);
    $progressEvents = [];
    $secondResults = $service->searchProgressively(
        $query,
        24,
        function (array $matches, string $stage, bool $isComplete) use (&$progressEvents): void {
            $progressEvents[] = [
                'stage' => $stage,
                'is_complete' => $isComplete,
                'count' => count($matches),
            ];
        },
    );

    expect($firstResults)->toBeArray()->not->toBeEmpty()
        ->and($secondResults)->toEqual($firstResults)
        ->and($progressEvents)->toHaveCount(1)
        ->and($progressEvents[0]['stage'])->toBe('complete')
        ->and($progressEvents[0]['is_complete'])->toBeTrue()
        ->and((int) $progressEvents[0]['count'])->toBe(count($firstResults));
});

it('injects visible basmallah lines under late-page surah headers', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);
    \Illuminate\Support\Facades\Cache::flush();
    config()->set('arabicable.quran_fonts.basmalah.preferred', 'quran-common-ligature');

    $page = $service->resolvePage(604);
    $mushafLines = collect($page['mushafLines'] ?? []);
    $basmallahLines = $mushafLines
        ->filter(static fn (array $line): bool => ($line['line_type'] ?? '') === 'basmallah')
        ->values();
    $firstAyahLine = $mushafLines
        ->first(static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah' && ($line['words'] ?? []) !== []);

    expect($basmallahLines)->toHaveCount(3)
        ->and($page['basmallahFontFamily'] ?? null)->toBe('QuranCommon')
        ->and($page['basmallahFontFormat'] ?? null)->toBe('woff2')
        ->and($page['basmallahText'] ?? null)->toBe("\u{FDFD}")
        ->and(filled($page['basmallahFontUrl'] ?? null))->toBeTrue()
        ->and($firstAyahLine)->toBeArray();

    $this->get((string) $page['basmallahFontUrl'])->assertSuccessful();

    config()->set('arabicable.quran_fonts.basmalah.preferred', 'madina-default');

    $madinaPage = $service->resolvePage(604);

    expect($madinaPage['basmallahFontFamily'] ?? null)->toBe('MadinaQuran')
        ->and($madinaPage['basmallahFontUrl'] ?? null)->toBeNull()
        ->and($madinaPage['basmallahText'] ?? null)->toContain('بِسْمِ');
});

it('does not repeat surah preludes on continuation pages', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);

    foreach ([99, 100] as $pageNumber) {
        $page = $service->resolvePage($pageNumber);
        $lines = collect($page['mushafLines'] ?? []);
        $firstAyahLine = $lines->first(static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah');
        $firstWord = collect($firstAyahLine['words'] ?? [])->first();

        expect($firstAyahLine)->toBeArray()
            ->and((int) ($firstWord['ayah_number'] ?? 0))->toBeGreaterThan(1)
            ->and(($lines->first()['line_type'] ?? null))->toBe('ayah')
            ->and($lines->take(2)->pluck('line_type')->all())->not->toContain('surah_name')
            ->and($lines->take(2)->pluck('line_type')->all())->not->toContain('basmallah');
    }
});

it('uses canonical verse text for ayah copy payload and excludes neighboring ayah tokens', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);
    \Illuminate\Support\Facades\Cache::flush();

    /** @var object|null $firstAyah */
    $firstAyah = \Illuminate\Support\Facades\DB::table('quran_verses')
        ->select(['ayah_index', 'text_searchable_typed', 'text_uthmani'])
        ->where('surah_number', 1)
        ->where('ayah_number', 1)
        ->first();

    /** @var object|null $secondAyah */
    $secondAyah = \Illuminate\Support\Facades\DB::table('quran_verses')
        ->select(['text_searchable_typed', 'text_uthmani'])
        ->where('surah_number', 1)
        ->where('ayah_number', 2)
        ->first();

    if (! is_object($firstAyah) || ! is_object($secondAyah)) {
        $this->markTestSkipped('Required Al-Fatiha verses are unavailable.');
    }

    $normalize = static fn (?string $text): string => (string) preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $text),
    );
    $normalizeForClipboard = static fn (?string $uthmani, ?string $typed): string => $normalize(
        \GoodMaven\Arabicable\Support\Quran\QuranWordCopyText::normalizeToken($uthmani, $typed) ?? '',
    );

    $page = $service->resolvePage(1, (int) $firstAyah->ayah_index);
    $lines = collect($page['mushafLines'] ?? []);
    $firstAyahIndex = (int) $firstAyah->ayah_index;
    $expectedFirstAyahText = $normalizeForClipboard(
        (string) ($firstAyah->text_uthmani ?? ''),
        (string) ($firstAyah->text_searchable_typed ?? ''),
    );
    $secondAyahText = $normalizeForClipboard(
        (string) ($secondAyah->text_uthmani ?? ''),
        (string) ($secondAyah->text_searchable_typed ?? ''),
    );
    $secondAyahFirstToken = collect(preg_split('/\s+/u', $secondAyahText) ?: [])
        ->filter(static fn ($token): bool => is_string($token) && trim($token) !== '')
        ->map(static fn (string $token): string => trim($token))
        ->first();

    $ayahCopyTexts = $lines
        ->flatMap(function (array $line): array {
            $segmentTexts = collect($line['segments'] ?? [])
                ->map(static fn (array $segment): array => [
                    'ayah_index' => (int) ($segment['ayah_index'] ?? 0),
                    'ayah_copy_text' => (string) ($segment['ayah_copy_text'] ?? ''),
                ])
                ->all();

            $wordTexts = collect($line['words'] ?? [])
                ->map(static fn (array $word): array => [
                    'ayah_index' => (int) ($word['ayah_index'] ?? 0),
                    'ayah_copy_text' => (string) ($word['ayah_copy_text'] ?? ''),
                ])
                ->all();

            return [...$segmentTexts, ...$wordTexts];
        })
        ->filter(static fn (array $entry): bool => (int) ($entry['ayah_index'] ?? 0) === $firstAyahIndex)
        ->map(static fn (array $entry): string => $normalize((string) ($entry['ayah_copy_text'] ?? '')))
        ->filter(static fn (string $text): bool => $text !== '')
        ->unique()
        ->values();

    expect($expectedFirstAyahText)->not->toBe('')
        ->and($secondAyahFirstToken)->toBeString()->not->toBe('')
        ->and($ayahCopyTexts)->not->toBeEmpty()
        ->and($ayahCopyTexts)->toContain($expectedFirstAyahText)
        ->and($ayahCopyTexts->join(' '))->not->toContain($secondAyahFirstToken);
});

it('builds meaningful late-page copy payloads for ayahs and words', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_verses') || ! \Illuminate\Support\Facades\Schema::hasTable('quran_words')) {
        $this->markTestSkipped('Quran verses or words table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);
    \Illuminate\Support\Facades\Cache::flush();

    $page = $service->resolvePage(604);
    $lines = collect($page['mushafLines'] ?? []);

    /** @var object|null $targetAyah */
    $targetAyah = \Illuminate\Support\Facades\DB::table('quran_verses')
        ->select(['ayah_index', 'text_uthmani', 'text_searchable_typed'])
        ->where('surah_number', 112)
        ->where('ayah_number', 1)
        ->first();

    if (! is_object($targetAyah)) {
        $this->markTestSkipped('Required late-page ayah is unavailable.');
    }

    $normalize = static fn (?string $text): string => (string) preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $text),
    );

    $expectedAyahText = $normalize(
        \GoodMaven\Arabicable\Support\Quran\QuranWordCopyText::normalizeToken(
            (string) ($targetAyah->text_uthmani ?? ''),
            (string) ($targetAyah->text_searchable_typed ?? ''),
        ) ?? '',
    );
    $targetAyahIndex = (int) ($targetAyah->ayah_index ?? 0);

    $payloadEntries = $lines->flatMap(function (array $line): array {
        $wordEntries = collect($line['words'] ?? [])
            ->map(static fn (array $word): array => [
                'ayah_index' => (int) ($word['ayah_index'] ?? 0),
                'copy_text' => (string) ($word['copy_text'] ?? ''),
                'ayah_copy_text' => (string) ($word['ayah_copy_text'] ?? ''),
            ])
            ->all();

        $segmentEntries = collect($line['segments'] ?? [])
            ->map(static fn (array $segment): array => [
                'ayah_index' => (int) ($segment['ayah_index'] ?? 0),
                'copy_text' => (string) ($segment['copy_text'] ?? ''),
                'ayah_copy_text' => (string) ($segment['ayah_copy_text'] ?? ''),
            ])
            ->all();

        return [...$wordEntries, ...$segmentEntries];
    });

    $ayahCopyTexts = $payloadEntries
        ->filter(static fn (array $entry): bool => (int) ($entry['ayah_index'] ?? 0) === $targetAyahIndex)
        ->map(static fn (array $entry): string => $normalize((string) ($entry['ayah_copy_text'] ?? '')))
        ->filter(static fn (string $text): bool => $text !== '')
        ->unique()
        ->values();

    $expectedWordTokens = collect(\Illuminate\Support\Facades\DB::table('quran_words')
        ->select(['token_uthmani', 'token_searchable_typed'])
        ->where('surah_number', 112)
        ->where('ayah_number', 1)
        ->orderBy('word_position')
        ->get())
        ->map(static fn (object $word): string => $normalize(
            \GoodMaven\Arabicable\Support\Quran\QuranWordCopyText::normalizeToken(
                (string) ($word->token_uthmani ?? ''),
                (string) ($word->token_searchable_typed ?? ''),
            ) ?? '',
        ))
        ->filter(static fn (string $token): bool => $token !== '')
        ->values();

    $actualWordTokens = $payloadEntries
        ->filter(static fn (array $entry): bool => (int) ($entry['ayah_index'] ?? 0) === $targetAyahIndex)
        ->map(static fn (array $entry): string => $normalize((string) ($entry['copy_text'] ?? '')))
        ->filter(static fn (string $token): bool => $token !== '')
        ->filter(static fn (string $token): bool => ! preg_match('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $token))
        ->unique()
        ->values();

    expect($expectedAyahText)->not->toBe('')
        ->and($ayahCopyTexts)->not->toBeEmpty()
        ->and($ayahCopyTexts)->toContain($expectedAyahText)
        ->and($expectedWordTokens)->not->toBeEmpty()
        ->and($actualWordTokens->join(' '))->not->toMatch('/[\x{06D6}-\x{06ED}\x{0640}]/u');

    foreach ($expectedWordTokens as $expectedToken) {
        expect($actualWordTokens)->toContain((string) $expectedToken);
    }
});

it('does not render basmallah below surah nine header', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);
    \Illuminate\Support\Facades\Cache::flush();

    $pageNumbers = \Illuminate\Support\Facades\DB::table('quran_mushaf_lines')
        ->distinct()
        ->orderBy('page_number')
        ->pluck('page_number')
        ->map(static fn ($value): int => max(0, (int) $value))
        ->filter(static fn (int $pageNumber): bool => $pageNumber > 0)
        ->values()
        ->all();

    $surahNineBasmallahEntries = [];

    foreach ($pageNumbers as $pageNumber) {
        $page = $service->resolvePage((int) $pageNumber);
        $lines = array_values(array_filter($page['mushafLines'] ?? [], 'is_array'));

        foreach ($lines as $lineIndex => $line) {
            if (
                ($line['line_type'] ?? null) === 'basmallah' &&
                (int) ($line['surah_number'] ?? 0) === 9
            ) {
                $surahNineBasmallahEntries[] = [
                    'page' => (int) $pageNumber,
                    'line' => (int) ($line['line_number'] ?? $lineIndex + 1),
                ];
            }

            if (
                ($line['line_type'] ?? null) !== 'surah_name' ||
                (int) ($line['surah_number'] ?? 0) !== 9
            ) {
                continue;
            }

            $nextLine = $lines[$lineIndex + 1] ?? null;

            if (is_array($nextLine) && ($nextLine['line_type'] ?? null) === 'basmallah') {
                $surahNineBasmallahEntries[] = [
                    'page' => (int) $pageNumber,
                    'line' => (int) ($nextLine['line_number'] ?? $lineIndex + 2),
                ];
            }
        }
    }

    expect(array_slice($surahNineBasmallahEntries, 0, 10))->toBeEmpty();
});

it('keeps qpc late-page surah metadata aligned for headers and copy payload', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);
    \Illuminate\Support\Facades\Cache::flush();

    $page499 = $service->resolvePage(499);
    $firstAyahLineOn499 = collect($page499['mushafLines'] ?? [])->first(
        static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah',
    );
    $firstWordOn499 = collect(is_array($firstAyahLineOn499) ? ($firstAyahLineOn499['words'] ?? []) : [])->first();

    expect($firstWordOn499)->toBeArray()
        ->and((int) ($firstWordOn499['surah_number'] ?? 0))->toBe(45)
        ->and((int) ($firstWordOn499['ayah_number'] ?? 0))->toBe(1);

    $page187 = $service->resolvePage(187);
    $linesOn187 = array_values(array_filter($page187['mushafLines'] ?? [], 'is_array'));

    $surahNineHeaderIndex = null;

    foreach ($linesOn187 as $lineIndex => $line) {
        if (
            ($line['line_type'] ?? null) === 'surah_name' &&
            (int) ($line['surah_number'] ?? 0) === 9
        ) {
            $surahNineHeaderIndex = $lineIndex;

            break;
        }
    }

    expect($surahNineHeaderIndex)->not->toBeNull();

    $lineAfterSurahNineHeader = $linesOn187[(int) $surahNineHeaderIndex + 1] ?? null;

    expect($lineAfterSurahNineHeader)->toBeArray()
        ->and((string) ($lineAfterSurahNineHeader['line_type'] ?? ''))->not->toBe('basmallah');

    $firstAyahAfterSurahNineHeader = collect(array_slice($linesOn187, (int) $surahNineHeaderIndex + 1))->first(
        static fn (array $line): bool => ($line['line_type'] ?? '') === 'ayah',
    );
    $firstWordAfterSurahNineHeader = collect(
        is_array($firstAyahAfterSurahNineHeader) ? ($firstAyahAfterSurahNineHeader['words'] ?? []) : [],
    )->first();

    expect($firstWordAfterSurahNineHeader)->toBeArray()
        ->and((int) ($firstWordAfterSurahNineHeader['surah_number'] ?? 0))->toBe(9)
        ->and((int) ($firstWordAfterSurahNineHeader['ayah_number'] ?? 0))->toBe(1);
});

it('builds canonical copy payloads for every ayah in the quran dataset', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var \App\Services\Quran\QuranReaderDataService $service */
    $service = app(\App\Services\Quran\QuranReaderDataService::class);
    \Illuminate\Support\Facades\Cache::flush();

    $normalize = static fn (?string $text): string => (string) preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $text),
    );

    $expectedAyahTextByIndex = [];

    foreach (\Illuminate\Support\Facades\DB::table('quran_verses')
        ->select(['ayah_index', 'text_uthmani', 'text_searchable_typed'])
        ->orderBy('ayah_index')
        ->get() as $verseRow) {
        $ayahIndex = (int) ($verseRow->ayah_index ?? 0);

        if ($ayahIndex < 1) {
            continue;
        }

        $normalizedAyahText = $normalize(
            \GoodMaven\Arabicable\Support\Quran\QuranWordCopyText::normalizeToken(
                (string) ($verseRow->text_uthmani ?? ''),
                (string) ($verseRow->text_searchable_typed ?? ''),
            ) ?? '',
        );

        if ($normalizedAyahText === '') {
            continue;
        }

        $expectedAyahTextByIndex[$ayahIndex] = $normalizedAyahText;
    }

    $pageNumbers = \Illuminate\Support\Facades\DB::table('quran_mushaf_lines')
        ->distinct()
        ->orderBy('page_number')
        ->pluck('page_number')
        ->map(static fn ($value): int => max(0, (int) $value))
        ->filter(static fn (int $pageNumber): bool => $pageNumber > 0)
        ->values()
        ->all();

    if ($expectedAyahTextByIndex === [] || $pageNumbers === []) {
        $this->markTestSkipped('Canonical Quran ayah rows are unavailable.');
    }

    $presentationFormPattern = '/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
    $actualAyahTextsByIndex = [];
    $invalidWordCopyEntries = [];
    $invalidAyahCopyEntries = [];
    $invalidAyahMetadataEntries = [];

    foreach ($pageNumbers as $pageNumber) {
        $page = $service->resolvePage((int) $pageNumber);
        $lines = collect($page['mushafLines'] ?? []);
        $payloadEntries = $lines->flatMap(static function (array $line): array {
            $segmentEntries = collect($line['segments'] ?? [])
                ->map(static fn (array $segment): array => [
                    'ayah_index' => (int) ($segment['ayah_index'] ?? 0),
                    'surah_number' => (int) ($segment['surah_number'] ?? 0),
                    'ayah_number' => (int) ($segment['ayah_number'] ?? 0),
                    'copy_text' => (string) ($segment['copy_text'] ?? ''),
                    'ayah_copy_text' => (string) ($segment['ayah_copy_text'] ?? ''),
                ])
                ->all();

            $wordEntries = collect($line['words'] ?? [])
                ->map(static fn (array $word): array => [
                    'ayah_index' => (int) ($word['ayah_index'] ?? 0),
                    'surah_number' => (int) ($word['surah_number'] ?? 0),
                    'ayah_number' => (int) ($word['ayah_number'] ?? 0),
                    'copy_text' => (string) ($word['copy_text'] ?? ''),
                    'ayah_copy_text' => (string) ($word['ayah_copy_text'] ?? ''),
                ])
                ->all();

            return [...$segmentEntries, ...$wordEntries];
        });

        foreach ($payloadEntries as $entry) {
            $ayahIndex = (int) ($entry['ayah_index'] ?? 0);

            if ($ayahIndex < 1) {
                continue;
            }

            $copyText = $normalize((string) ($entry['copy_text'] ?? ''));
            $ayahCopyText = $normalize((string) ($entry['ayah_copy_text'] ?? ''));
            $surahNumber = (int) ($entry['surah_number'] ?? 0);
            $ayahNumber = (int) ($entry['ayah_number'] ?? 0);

            if ($surahNumber < 1 || $ayahNumber < 1) {
                $invalidAyahMetadataEntries[] = [
                    'page' => (int) $pageNumber,
                    'ayah_index' => $ayahIndex,
                    'surah_number' => $surahNumber,
                    'ayah_number' => $ayahNumber,
                ];
            }

            if ($copyText !== '' && preg_match($presentationFormPattern, $copyText)) {
                $invalidWordCopyEntries[] = [
                    'page' => (int) $pageNumber,
                    'ayah_index' => $ayahIndex,
                    'copy_text' => $copyText,
                ];
            }

            if ($ayahCopyText !== '' && preg_match($presentationFormPattern, $ayahCopyText)) {
                $invalidAyahCopyEntries[] = [
                    'page' => (int) $pageNumber,
                    'ayah_index' => $ayahIndex,
                    'ayah_copy_text' => $ayahCopyText,
                ];
            }

            if ($ayahCopyText !== '') {
                $actualAyahTextsByIndex[$ayahIndex] ??= [];
                $actualAyahTextsByIndex[$ayahIndex][$ayahCopyText] = true;
            }
        }
    }

    $expectedAyahIndexes = array_keys($expectedAyahTextByIndex);
    $actualAyahIndexes = array_keys($actualAyahTextsByIndex);
    sort($expectedAyahIndexes);
    sort($actualAyahIndexes);

    $missingAyahIndexes = array_values(array_diff($expectedAyahIndexes, $actualAyahIndexes));
    $unexpectedAyahIndexes = array_values(array_diff($actualAyahIndexes, $expectedAyahIndexes));
    $mismatchedAyahs = [];

    foreach ($expectedAyahTextByIndex as $ayahIndex => $expectedAyahText) {
        $actualAyahTexts = array_keys($actualAyahTextsByIndex[$ayahIndex] ?? []);

        if ($actualAyahTexts === []) {
            continue;
        }

        foreach ($actualAyahTexts as $actualAyahText) {
            if ($actualAyahText === $expectedAyahText) {
                continue;
            }

            $mismatchedAyahs[] = [
                'ayah_index' => (int) $ayahIndex,
                'expected' => $expectedAyahText,
                'actual' => $actualAyahText,
            ];

            break;
        }
    }

    expect(array_slice($invalidWordCopyEntries, 0, 10))->toBeEmpty()
        ->and(array_slice($invalidAyahCopyEntries, 0, 10))->toBeEmpty()
        ->and(array_slice($invalidAyahMetadataEntries, 0, 10))->toBeEmpty()
        ->and(array_slice($missingAyahIndexes, 0, 20))->toBeEmpty()
        ->and(array_slice($unexpectedAyahIndexes, 0, 20))->toBeEmpty()
        ->and(array_slice($mismatchedAyahs, 0, 10))->toBeEmpty();
});
