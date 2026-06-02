<?php

declare(strict_types=1);
use App\Services\Quran\QuranReaderDataService;
use GoodMaven\Arabicable\Support\Quran\QuranWordCopyText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('wires quran reader entry points from main menu to hash navigation and view mount', function () {
    $menuSource = file_get_contents(resource_path('views/components/partials/main-menu.blade.php'));
    $homeSource = file_get_contents(resource_path('views/home.blade.php'));
    $buttonsStackSource = file_get_contents(resource_path('views/components/buttons-stack.blade.php'));
    $colorfulBackgroundSource = file_get_contents(resource_path('views/components/partials/colorful-background.blade.php'));
    $quranGateSource = file_get_contents(resource_path('views/components/partials/quran-app/gate.blade.php'));
    $quranGateScriptSource = file_get_contents(resource_path('js/support/alpine/data/quran-app-gate.js'));
    $quranIndexSource = file_get_contents(resource_path('views/components/partials/quran-app/index.blade.php'));
    $quranReaderPartialSource = file_get_contents(
        resource_path('views/components/partials/quran-app/reader.blade.php'),
    );
    $quranReaderViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));
    $quranHistoryModalViewSource = file_get_contents(resource_path('views/components/partials/quran-app/history-modal.blade.php'));
    $quranBookmarksModalViewSource = file_get_contents(resource_path('views/components/partials/quran-app/bookmarks-modal.blade.php'));
    $quranSearchModalViewSource = file_get_contents(
        resource_path('views/components/partials/quran-app/search-modal.blade.php'),
    );
    $appLayoutSource = file_get_contents(resource_path('views/components/app.blade.php'));
    $quranReaderScriptSource = '';
    $quranReaderScriptPaths = glob(resource_path('js/support/alpine/data/quran-app-reader/*.js')) ?: [];

    foreach ($quranReaderScriptPaths as $quranReaderScriptPath) {
        $quranReaderScriptContents = file_get_contents($quranReaderScriptPath);

        if ($quranReaderScriptContents === false) {
            continue;
        }

        $quranReaderScriptSource .= "\n".$quranReaderScriptContents;
    }
    $quranReaderFitControlsSource = file_get_contents(
        resource_path(
            'js/support/alpine/data/quran-app-reader/reader-navigation-fit-idle-warmup-and-scale-controls.js',
        ),
    );
    $quranReaderFitSolverSource = file_get_contents(
        resource_path(
            'js/support/alpine/data/quran-app-reader/reader-navigation-fit-reveal-guards-and-solver.js',
        ),
    );
    $quranReaderLineLayoutSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/line-layout-render-core.js'),
    );
    $quranReaderClassSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $navigationHistoryActionSource = (string) Str::of($quranReaderClassSource)
        ->after('public function navigationHistoryAction(): Action')
        ->before('public function bookmarksManagerAction(): Action');
    $bookmarksManagerActionSource = (string) Str::of($quranReaderClassSource)
        ->after('public function bookmarksManagerAction(): Action')
        ->before('public function render(): View');
    $quranReaderDataServiceSource = file_get_contents(app_path('Services/Quran/QuranReaderDataService.php'));
    $settingModelSource = file_get_contents(app_path('Models/Setting.php'));
    $controlPanelSettingsTabSource = file_get_contents(app_path('Services/Traits/HasControlPanelSettingsTab.php'));
    $historyManagerTableSource = file_get_contents(app_path('Livewire/QuranApp/HistoryManagerTable.php'));
    $bookmarksManagerTableSource = file_get_contents(app_path('Livewire/QuranApp/BookmarksManagerTable.php'));
    $mainMenuComponentSource = file_get_contents(resource_path('views/components/main-menu/index.blade.php'));
    $mainMenuItemSource = file_get_contents(resource_path('views/components/main-menu/item.blade.php'));
    $mainMenuScriptSource = file_get_contents(resource_path('js/support/alpine/data/main-menu.js'));
    $routesSource = file_get_contents(base_path('routes/web.php'));
    $appJsSource = file_get_contents(resource_path('js/app.js'));
    $filamentComponentsCssSource = file_get_contents(resource_path('css/core/filament/components.css'));

    expect($menuSource)->not->toBeFalse()
        ->and($menuSource)->toContain(":caption=\"arabic_text('الكتاب')\"")
        ->and($menuSource)->toContain(":onClickCallback=\"'() => openQuranEntry()'\"")
        ->and($menuSource)->toContain("x-bind:data-main-menu-exiting=\"views['main-menu'].isOpen ? 'false' : 'true'\"");

    expect($mainMenuComponentSource)->not->toBeFalse()
        ->and($mainMenuComponentSource)->toContain("[data-main-menu-exiting='true'] [data-main-menu-item]")
        ->and($mainMenuComponentSource)->toContain('transition-delay: calc(var(--main-menu-item-index) * 24ms);')
        ->and($mainMenuComponentSource)->toContain('x-for="row in sortedInsightsRows()"')
        ->and($mainMenuComponentSource)->toContain('handleInsightsRowClick(row.key, $event)');

    expect($mainMenuScriptSource)->not->toBeFalse()
        ->and($mainMenuScriptSource)->toContain('resolveInsightsRowPriority(entry)')
        ->and($mainMenuScriptSource)->toContain('sortedInsightsRows()')
        ->and($mainMenuScriptSource)->toContain("insightsRowOrder: ['sabah', 'wird', 'masaa']")
        ->and($mainMenuScriptSource)->toContain('insightsMostlyDoneThreshold: 70');

    expect($mainMenuItemSource)->not->toBeFalse()
        ->and($mainMenuItemSource)->toContain('transform: translateX(175%) skewX(45deg);')
        ->and($mainMenuItemSource)->toContain('transform: translateX(-175%) skewX(45deg);');

    expect($homeSource)->not->toBeFalse()
        ->and($homeSource)->toContain("'quran-app-gate': {")
        ->and($homeSource)->toContain("'quran-app-tilawa': {")
        ->and($homeSource)->toContain("'quran-app-hifth': {")
        ->and($homeSource)->toContain("'quran-app-tadabbur': {")
        ->and($homeSource)->toContain('openQuranEntry()')
        ->and($homeSource)->toContain('quran-bootstrap-request')
        ->and($homeSource)->toContain('quranBootstrap')
        ->and($homeSource)->toContain("'#quran-app-gate': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-tilawa': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-hifth': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-tadabbur': () => runHashAction(() => {")
        ->and($homeSource)->toContain('views[`quran-app-tilawa`].isOpen')
        ->and($homeSource)->toContain('views[`quran-app-hifth`].isOpen')
        ->and($homeSource)->toContain('views[`quran-app-tadabbur`].isOpen')
        ->and($homeSource)->toContain('quran-reader-go-gate')
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
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/morning/tilawa-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/night/tilawa-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/morning/hifth-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/night/hifth-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/morning/tadabbur-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('images/background/quran/night/tadabbur-blurred.webp')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-tilawa-light-layer')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-tilawa-dark-layer')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-hifth-light-layer')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-hifth-dark-layer')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-tadabbur-light-layer')
        ->and($colorfulBackgroundSource)->toContain('quran-bg-tadabbur-dark-layer');

    expect($quranIndexSource)->not->toBeFalse()
        ->and($quranIndexSource)->toContain('data-quran-app-shell')
        ->and($quranIndexSource)->toContain('<x-partials.quran-app.gate />')
        ->and($quranIndexSource)->toContain('<x-partials.quran-app.reader />');

    expect($quranGateSource)->not->toBeFalse()
        ->and($quranGateSource)->toContain('x-data="quranAppGate"')
        ->and($quranGateSource)->toContain('images/background/quran/morning/tilawa.webp')
        ->and($quranGateSource)->toContain('images/background/quran/night/tilawa.webp')
        ->and($quranGateSource)->toContain('images/background/quran/morning/hifth.webp')
        ->and($quranGateSource)->toContain('images/background/quran/night/hifth.webp')
        ->and($quranGateSource)->toContain('images/background/quran/morning/tadabbur.webp')
        ->and($quranGateSource)->toContain('images/background/quran/night/tadabbur.webp')
        ->and($quranGateSource)->toContain('quran-app-sector__media--tilawa')
        ->and($quranGateSource)->toContain('quran-app-sector__media--hifth')
        ->and($quranGateSource)->toContain('quran-app-sector__media--tadabbur')
        ->and($quranGateSource)->toContain('quran-app-gate-focal-dim')
        ->and($quranGateSource)->toContain('quran-app-gate-pointer')
        ->and($quranGateSource)->toContain('quran-app-sector__chip-lock')
        ->and($quranGateSource)->toContain('quran-app-gate-orbit')
        ->and($quranGateSource)->toContain('x-on:pointermove="handlePointerMove($event)"')
        ->and($quranGateSource)->toContain('x-on:touchmove.prevent="handleTouchMove($event)"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'tilawa\', $event)"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'hifth\', $event)"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'tadabbur\', $event)"')
        ->and($quranGateSource)->not->toContain('M0 0 L50 53')
        ->and($quranGateSource)->not->toContain('M100 0 L50 53')
        ->and($quranGateSource)->not->toContain('quran-app-gate-needle');

    expect($quranGateScriptSource)->not->toBeFalse()
        ->and($quranGateScriptSource)->toContain('pinMode(mode)')
        ->and($quranGateScriptSource)->toContain('requiresArmedActivation()')
        ->and($quranGateScriptSource)->toContain('if (this.armedMode !== mode)')
        ->and($quranGateScriptSource)->toContain('this.armMode(mode);');

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
        ->and($quranReaderViewSource)->toContain('x-on:quran-bootstrap-request.window="prepareQuranFromMainMenu($event.detail ?? {})"')
        ->and($quranReaderViewSource)->toContain('x-on:switch-view.window="$nextTick(() => syncNativeVolumeNavigation())"')
        ->and($quranReaderViewSource)->toContain("x-on:x-modal-opened.window=\"handleModalLifecycleEvent('opened', \$event)\"")
        ->and($quranReaderViewSource)->toContain("x-on:close-modal.window=\"handleModalLifecycleEvent('closing', \$event)\"")
        ->and($quranReaderViewSource)->toContain("x-on:x-modal-closed.window=\"handleModalLifecycleEvent('closed', \$event)\"")
        ->and($quranReaderViewSource)->toContain('x-on:control-panel-updated.window="applyControlPanelSettings($event.detail?.controlPanel ?? {})"')
        ->and($quranReaderViewSource)->toContain('x-on:click="void openHistoryModal()"')
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
        ->and($quranReaderViewSource)->toContain('class="quran-page-slider ')
        ->and($quranReaderViewSource)->toContain('outline-none')
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
        ->and($quranReaderViewSource)->toContain('x-on:click="void openJumpPageModal()"')
        ->and($quranReaderViewSource)->toContain('<x-filament-actions::modals />')
        ->and($quranReaderViewSource)->toContain('x-ref="readerPanel"')
        ->and($quranReaderViewSource)->toContain('x-on:pointerdown.passive="onSwipeStart($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:pointermove.window.passive="onSwipeMove($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:touchstart.passive="onSwipeStart($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:touchmove.window.passive="onSwipeMove($event)"')
        ->and($quranReaderViewSource)->toContain('x-on:keydown.left.window="onGlobalArrowNavigate(\'left\', $event)"')
        ->and($quranReaderViewSource)->toContain('x-on:keydown.right.window="onGlobalArrowNavigate(\'right\', $event)"')
        ->and($quranReaderViewSource)->toContain('x-on:pointerup.window.passive="onWordPointerUp($event)"')
        ->and($quranReaderViewSource)->toContain('data-quran-word-button')
        ->and($quranReaderViewSource)->toContain('x-bind:data-quran-surah-number=')
        ->and($quranReaderViewSource)->toContain('quran-segment-cluster-copied')
        ->and($quranReaderViewSource)->toContain('quran-segment-copied')
        ->and($quranReaderViewSource)->toMatch(
            '/x-bind:data-fit-state="typeof pageFitState === \\\'function\\\' \\? pageFitState\\(\\) : \\(isFittingPage \\?\\s*\\\'fitting\\\'\\s*:\\s*\\\'ready\\\'\\)"/s',
        )
        ->and($quranReaderViewSource)->toContain('x-for="line in mushafLines"')
        ->and($quranReaderViewSource)->toContain(
            'x-bind:data-quran-line-number="Number(lineEntry?.line_number ?? 0)"',
        )
        ->and($quranReaderViewSource)->toContain(
            'x-bind:data-quran-line-type="String(lineEntry?.line_type ?? \'\')"',
        )
        ->and($quranReaderViewSource)->toContain('x-ref="prevChevronButton"')
        ->and($quranReaderViewSource)->toContain('x-ref="nextChevronButton"')
        ->and($quranReaderViewSource)->not->toContain('x-on:click="nextPage()"')
        ->and($quranReaderViewSource)->not->toContain('x-on:click="previousPage()"')
        ->and($quranReaderViewSource)->not->toContain("x-on:click=\"\$viewNav('quran-app-gate')\"");

    expect($quranReaderFitControlsSource)->not->toBeFalse()
        ->and($quranReaderFitControlsSource)->toContain('--quran-page-postfit-surah-gap-tune')
        ->and($quranReaderFitControlsSource)->toContain(
            '--quran-page-postfit-surah-gap-tune-effective',
        )
        ->and($quranReaderFitControlsSource)->toContain(
            'var(--quran-page-postfit-surah-gap-tune, 1)',
        );

    expect($quranReaderFitSolverSource)->not->toBeFalse()
        ->and($quranReaderFitSolverSource)->toContain('--quran-page-postfit-surah-gap-tune')
        ->and($quranReaderFitSolverSource)->toContain(
            '--quran-page-postfit-surah-gap-tune-effective',
        )
        ->and($quranReaderFitSolverSource)->toContain('postFitSurahGapTune')
        ->and($quranReaderFitSolverSource)->toContain(
            'postFitSurahGapTuneEffective: readRootVar',
        );

    expect($quranReaderLineLayoutSource)->not->toBeFalse()
        ->and($quranReaderLineLayoutSource)->toContain(
            'var(--quran-page-postfit-surah-gap-tune-effective, var(--quran-page-postfit-surah-gap-tune, 1))',
        )
        ->and($quranReaderLineLayoutSource)->toContain('surahGapTuneValue');

    expect($quranReaderScriptSource)->not->toBeFalse()
        ->and($quranReaderScriptSource)->toContain('registerNativeInputListeners()')
        ->and($quranReaderScriptSource)->toContain('unregisterNativeInputListeners()')
        ->and($quranReaderScriptSource)->toContain('activeQuranReaderView()')
        ->and($quranReaderScriptSource)->toContain('shouldPersistActivationIndexes()')
        ->and($quranReaderScriptSource)->toContain('clearActivationIndexes()')
        ->and($quranReaderScriptSource)->toContain("if (to !== 'quran-app-tadabbur') {")
        ->and($quranReaderScriptSource)->toContain(
            "return this.activeQuranReaderView() === 'quran-app-tadabbur';",
        )
        ->and($quranReaderScriptSource)->toContain('prepareQuranFromMainMenu(detail = {})')
        ->and($quranReaderScriptSource)->toContain('setAndroidVolumeNavigationEnabled(enabled)')
        ->and($quranReaderScriptSource)->toContain("'quran-native-volume-button'")
        ->and($quranReaderScriptSource)->toContain('searchResultIsLeaving(result)')
        ->and($quranReaderScriptSource)->toContain('setSearchResults(nextResults, { immediate = false } = {})')
        ->and($quranReaderScriptSource)->toContain('queueSearchLeaveCleanup()')
        ->and($quranReaderScriptSource)->toContain("useVolumeButtonsNavigation: 'does_quran_use_volume_buttons_navigation'")
        ->and($quranReaderScriptSource)->toContain('async goToPageFromChevron(')
        ->and($quranReaderScriptSource)->toContain('await this.goToPageFromChevron(requestedPage, {')
        ->and($quranReaderScriptSource)->toContain('await this.goToPageFromChevron(targetPage, {')
        ->and($quranReaderScriptSource)->toContain('activeAyahIndex: requestedActiveAyahIndex,')
        ->and($quranReaderScriptSource)->toContain('await this.goToPageFromChevron(pageNumber, {')
        ->and($quranReaderScriptSource)->toContain('activeAyahIndex: 0,')
        ->and($quranReaderScriptSource)->toContain("window.addEventListener('keydown', this._onWindowKeydown, true)")
        ->and($quranReaderScriptSource)->toContain("readerPanel.addEventListener('pointerdown', this._onPanelPointerDown, {")
        ->and($quranReaderScriptSource)->toContain("window.addEventListener('touchmove', this._onWindowTouchMove, {")
        ->and($quranReaderScriptSource)->toContain('teardownSearchResultAnimations()')
        ->and($quranReaderScriptSource)->toContain('window.autoAnimate(searchResultsContainer, {')
        ->and($quranReaderScriptSource)->toContain('disrespectUserMotionPreference: true,');

    expect($quranReaderClassSource)->not->toBeFalse()
        ->and($quranReaderClassSource)->toContain('private const SEARCH_STREAM_TARGET = \'quran-search-results-stream\';')
        ->and($quranReaderClassSource)->toContain('private const SEARCH_STREAM_PADDING_BYTES = 65536;')
        ->and($quranReaderClassSource)->toContain('private const SEARCH_STREAM_FRAME_DELIMITER =')
        ->and($quranReaderClassSource)->toContain('content: e($encodedPayload).self::SEARCH_STREAM_FRAME_DELIMITER,');

    expect($quranSearchModalViewSource)->not->toBeFalse()
        ->and($quranSearchModalViewSource)->toContain('quran-search-field-wrapper')
        ->and($quranSearchModalViewSource)->toContain('id="quran-reader-search-input"')
        ->and($quranSearchModalViewSource)->toContain('wire:stream="quran-search-results-stream"')
        ->and($quranSearchModalViewSource)->toContain('x-for="chunk in searchResultChunks()"');

    expect($appLayoutSource)->not->toBeFalse()
        ->and($appLayoutSource)->not->toContain("@livewire('livewire-ui-spotlight')");

    expect($historyManagerTableSource)->not->toBeFalse()
        ->and($historyManagerTableSource)->toContain('public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void')
        ->and($historyManagerTableSource)->toContain("'lang' => 'en'")
        ->and($historyManagerTableSource)->toContain('private function normalizeTagsInput(mixed $value): array');

    expect($bookmarksManagerTableSource)->not->toBeFalse()
        ->and($bookmarksManagerTableSource)->toContain('public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void')
        ->and($bookmarksManagerTableSource)->toContain("'lang' => 'en'")
        ->and($bookmarksManagerTableSource)->toContain('private function normalizeTagsInput(mixed $value): array');

    expect($quranHistoryModalViewSource)->not->toBeFalse()
        ->and($quranHistoryModalViewSource)->toContain('quran-manager-shell')
        ->and($quranHistoryModalViewSource)->toContain('data-no-swipe')
        ->and($quranHistoryModalViewSource)->toContain('livewire:quran-app.history-manager-table');

    expect($quranBookmarksModalViewSource)->not->toBeFalse()
        ->and($quranBookmarksModalViewSource)->toContain('quran-manager-shell')
        ->and($quranBookmarksModalViewSource)->toContain('data-no-swipe')
        ->and($quranBookmarksModalViewSource)->toContain('livewire:quran-app.bookmarks-manager-table');

    expect($quranReaderScriptSource)->not->toBeFalse()
        ->and($quranReaderScriptSource)->toContain('const wordPressHoldDelayMs =')
        ->and($quranReaderScriptSource)->toContain('const navigationHistoryLimit =')
        ->and($quranReaderScriptSource)->toContain("const navigationHistoryStorageKey = 'quran-reader-navigation-history-v1';")
        ->and($quranReaderScriptSource)->toContain('postModalFitRevealSettleDelayMs')
        ->and($quranReaderScriptSource)->toContain('ensureWirdDailyRecord(')
        ->and($quranReaderScriptSource)->toContain('async enterWirdMode()')
        ->and($quranReaderScriptSource)->toContain('async exitWirdMode(')
        ->and($quranReaderScriptSource)->toContain('async stepWird(direction = \'next\', source = \'generic\')')
        ->and($quranReaderScriptSource)->toContain('requestSearchModalClose({ skipLayout = false } = {})')
        ->and($quranReaderScriptSource)->toContain('recordNavigationHistory({')
        ->and($quranReaderScriptSource)->toContain('fitCacheStorageKey')
        ->and($quranReaderScriptSource)->toContain('calibrateGlobalFitLayoutFromReferencePage')
        ->and($quranReaderScriptSource)->toContain('syncFitCacheBreakpoint({ persist =')
        ->and($quranReaderScriptSource)->toContain('toggleCurrentPageBookmark()')
        ->and($quranReaderScriptSource)->toContain('openBookmarksManager()')
        ->and($quranReaderScriptSource)->toContain('jumpPageModalId:')
        ->and($quranReaderScriptSource)->toContain('quran-manager-modals-visibility')
        ->and($quranReaderScriptSource)->toContain('goToHistoryEntry(entry)')
        ->and($quranReaderScriptSource)->toContain('goToBookmark(bookmark)')
        ->and($quranReaderScriptSource)->toContain('copyWordSelection(word, activationAnchor = null)')
        ->and($quranReaderScriptSource)->toContain('copyAyahSelection(ayahIndex, activationAnchor = null)')
        ->and($quranReaderScriptSource)->toContain('copyDraggedSelection(activationAnchor = null)')
        ->and($quranReaderScriptSource)->toContain('copiedHighlights: {')
        ->and($quranReaderScriptSource)->toContain("preserveHarakatOnCopy: 'does_quran_preserve_harakat_on_copy'")
        ->and($quranReaderScriptSource)->toContain("appendSurahAffixOnMultiCopy: 'does_quran_append_surah_affix_on_multi_copy'")
        ->and($quranReaderScriptSource)->toContain("appendSurahAffixAlwaysOnCopy: 'does_quran_append_surah_affix_always_on_copy'")
        ->and($quranReaderScriptSource)->toContain("useWesternNumerals: 'does_use_western_numerals'")
        ->and($quranReaderScriptSource)->toContain('resolveControlPanelSettingsWithUserOverrides(defaultSettings = {})')
        ->and($quranReaderScriptSource)->toContain('selectedDraggedSurahNumbers()')
        ->and($quranReaderScriptSource)->toContain('draggedSelectionSurahAffixes()')
        ->and($quranReaderScriptSource)->toContain('normalizeCopiedText(text)')
        ->and($quranReaderScriptSource)->toContain('copyFeedbackStyle()');

    expect($quranReaderClassSource)->not->toBeFalse()
        ->and($quranReaderClassSource)->toContain('implements HasActions, HasSchemas')
        ->and($quranReaderClassSource)->toContain('use InteractsWithActions;')
        ->and($quranReaderClassSource)->toContain('use InteractsWithSchemas;')
        ->and($quranReaderClassSource)->toContain('public function searchQuranAction(): Action')
        ->and($quranReaderClassSource)->not->toContain('InteractsWithQuranSearchAction')
        ->and($quranReaderClassSource)->toContain('public function jumpToPageAction(): Action')
        ->and($quranReaderClassSource)->toContain('public function navigationHistoryAction(): Action')
        ->and($quranReaderClassSource)->toContain('public function bookmarksManagerAction(): Action')
        ->and($quranReaderClassSource)->toContain("Blade::render('<x-partials.quran-app.history-modal />')")
        ->and($quranReaderClassSource)->toContain("Blade::render('<x-partials.quran-app.bookmarks-modal />')")
        ->and($quranReaderClassSource)->toContain('->extraModalWindowAttributes([')
        ->and($quranReaderClassSource)->toContain("'id' => self::HISTORY_MODAL_ID")
        ->and($quranReaderClassSource)->toContain("'id' => self::BOOKMARKS_MODAL_ID")
        ->and($quranReaderClassSource)->toContain('->modalAutofocus(true)')
        ->and($quranReaderClassSource)->toContain('->autofocus()')
        ->and($quranReaderClassSource)->toContain("'x-on:focus' => '\$event.target.select();'")
        ->and($quranReaderClassSource)->toContain("'x-on:input' => '\$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number(\$event.target.value || 1) || 1)), Math.max(1, Number(\$event.target.max) || 1)));'")
        ->and($quranReaderClassSource)->toContain("'x-on:blur' => '\$event.target.value = String(Math.min(Math.max(1, Math.trunc(Number(\$event.target.value || 1) || 1)), Math.max(1, Number(\$event.target.max) || 1))); window.setTimeout(() => { if (!document.body.contains(\$event.target) || \$event.target.offsetParent === null) { return; } \$event.target.focus(); \$event.target.select(); }, 0);'")
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
        ->toContain('modalHeading(arabic_text(\'سجلّ الانتقالات\'))')
        ->not->toContain('->slideOver()');

    expect($bookmarksManagerActionSource)
        ->toContain('modalHeading(arabic_text(\'إدارة علامات الصفحات\'))')
        ->toContain('->slideOver()');

    expect($quranReaderDataServiceSource)->not->toBeFalse()
        ->and($quranReaderDataServiceSource)->toContain('p\'.$pageNumber.\'.woff2')
        ->and($quranReaderDataServiceSource)->toContain("'format' => 'woff2'")
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-page-v19')
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-surah-directory-v2')
        ->and($quranReaderDataServiceSource)->toContain('injectSyntheticBasmallahAfterSurahHeaders')
        ->and($quranReaderDataServiceSource)->toContain('applyTargetedSurahHeaderCarryovers')
        ->and($quranReaderDataServiceSource)->toContain('quran-reader-search-index-v4')
        ->and($quranReaderDataServiceSource)->toContain('use GoodMaven\Arabicable\Support\Quran\QuranSearchText;')
        ->and($quranReaderDataServiceSource)->toContain('prepareSearchTokens(array $tokens): array')
        ->and($quranReaderDataServiceSource)->toContain('return QuranSearchText::prepareTokens($tokens);')
        ->and($quranReaderDataServiceSource)->toContain('return QuranSearchText::expandStrictExactPhraseVariants($text);')
        ->and($quranReaderDataServiceSource)->toContain('QuranSearchText::expandVariants($text)')
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
        ->and($settingModelSource)->toContain('الحفاظ على الحركات والزخارف عند نسخ نص الآيات')
        ->and($settingModelSource)->toContain('إضافة اسم السورة عند النسخ المتعدد بين الآيات')
        ->and($settingModelSource)->toContain('إضافة اسم السورة دائمًا عند النسخ')
        ->and($settingModelSource)->toContain('إعداد الوِرد اليومي.')
        ->and($settingModelSource)->toContain('هدف عدد الختمات المستهدفة للوِرد.')
        ->and($settingModelSource)->toContain('__WESTERN_NUMERALS_SAMPLE__')
        ->and($settingModelSource)->toContain('__ARABIC_NUMERALS_SAMPLE__');

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
        ->and($appJsSource)->toContain("import './support/alpine/data/quran-app-reader/index';");

    expect($filamentComponentsCssSource)->not->toBeFalse()
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-search-modal')
        ->and($filamentComponentsCssSource)->toContain('.quran-search-shell')
        ->and($filamentComponentsCssSource)->toContain('.quran-page-counter-field')
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-page-counter-input')
        ->and($filamentComponentsCssSource)->toContain('.quran-search-input')
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-history-modal')
        ->and($filamentComponentsCssSource)->toContain('#quran-reader-bookmarks-modal')
        ->and($filamentComponentsCssSource)->toContain('.quran-manager-table')
        ->and($filamentComponentsCssSource)->toContain('.quran-surah-grid-caption::before')
        ->and($filamentComponentsCssSource)->toContain('.fi-input-wrp-suffix .fi-input-wrp-label')
        ->and($filamentComponentsCssSource)->toContain('justify-items: center;')
        ->and($filamentComponentsCssSource)->toContain('width: min(100%, 46rem);')
        ->and($filamentComponentsCssSource)->toContain('transform-origin: center center;');
});

it('registers qpc page font route contract used by quran reader pages', function () {
    expect(route('qpc-v2-font', ['page' => 1], false))->toBe('/qpc-v2-fonts/1.ttf');
    expect(route('quran-surah-header-font', [], false))->toBe('/quran-surah-header-font');
    expect(route('quran-reader-page-data', ['page' => 1], false))->toBe('/quran-reader/pages/1.json');
    expect(route('quran-reader-search-index', [], false))->toBe('/quran-reader/search-index.json');
});

it('keeps quran manager table defaults for pagination filters and replace confirmation', function () {
    $historyManagerTableSource = file_get_contents(app_path('Livewire/QuranApp/HistoryManagerTable.php'));
    $bookmarksManagerTableSource = file_get_contents(app_path('Livewire/QuranApp/BookmarksManagerTable.php'));
    $quranReaderViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));
    $historyModalPartialSource = file_get_contents(
        resource_path('views/components/partials/quran-app/history-modal.blade.php'),
    );
    $bookmarksModalPartialSource = file_get_contents(
        resource_path('views/components/partials/quran-app/bookmarks-modal.blade.php'),
    );

    expect($historyManagerTableSource)->not->toBeFalse()
        ->and($historyManagerTableSource)->toContain('->defaultPaginationPageOption(100)')
        ->and($historyManagerTableSource)->toContain('->selectable(false)')
        ->and($historyManagerTableSource)->toContain("->defaultSortOptionLabel('-')")
        ->and($historyManagerTableSource)->toContain('->paginationPageOptions([100])')
        ->and($historyManagerTableSource)->toContain('->stackedOnMobile()')
        ->and($historyManagerTableSource)->toContain("->placeholder(arabic_text('الكل'))")
        ->and($historyManagerTableSource)->not->toContain("'all' => arabic_text('الكل')")
        ->and($historyManagerTableSource)->toContain('private function recordHasPersistenceMeta(array $record): bool')
        ->and($historyManagerTableSource)->toContain('->concat(')
        ->and($historyManagerTableSource)->toContain('ActionGroup::make([')
        ->and($historyManagerTableSource)->toContain('RecordActionsPosition::BeforeColumns')
        ->and($historyManagerTableSource)->toContain("->label(arabic_text('الوسوم والملاحظة'))")
        ->and($historyManagerTableSource)->toContain("->modalSubmitActionLabel(arabic_text('تعديل'))")
        ->and($historyManagerTableSource)->toContain("->modalSubmitAction(fn (Action \$action): Action => \$action->icon('heroicon-o-pencil-square'))")
        ->and($historyManagerTableSource)->toContain("Action::make('remove')")
        ->and($historyManagerTableSource)->toContain("'quran-history-manager-removed'")
        ->and($historyManagerTableSource)->toContain("->icon('heroicon-o-x-mark')");

    expect($bookmarksManagerTableSource)->not->toBeFalse()
        ->and($bookmarksManagerTableSource)->toContain("Action::make('replacePage')")
        ->and($bookmarksManagerTableSource)->toContain('->selectable(false)')
        ->and($bookmarksManagerTableSource)->toContain('->defaultPaginationPageOption(100)')
        ->and($bookmarksManagerTableSource)->toContain("->defaultSortOptionLabel('-')")
        ->and($bookmarksManagerTableSource)->toContain('->paginationPageOptions([100])')
        ->and($bookmarksManagerTableSource)->toContain('->stackedOnMobile()')
        ->and($bookmarksManagerTableSource)->toContain('private function resolveBookmarkRecency(array $record): int')
        ->and($bookmarksManagerTableSource)->toContain('private function recordHasPersistenceMeta(array $record): bool')
        ->and($bookmarksManagerTableSource)->toContain('private function resolveBookmarkSurahLabel(int $pageNumber): string')
        ->and($bookmarksManagerTableSource)->toContain("return arabic_text(sprintf('%d • %s', \$sortOrder, \$surah));")
        ->and($bookmarksManagerTableSource)->toContain('ActionGroup::make([')
        ->and($bookmarksManagerTableSource)->toContain('RecordActionsPosition::BeforeColumns')
        ->and($bookmarksManagerTableSource)->toContain("->label(arabic_text('الوسوم والملاحظة'))")
        ->and($bookmarksManagerTableSource)->toContain('->requiresConfirmation()')
        ->and($bookmarksManagerTableSource)->toContain('->modalHeading(arabic_text(\'تأكيد استبدال الصفحة\'))')
        ->and($bookmarksManagerTableSource)->toContain("->placeholder(arabic_text('الكل'))")
        ->and($bookmarksManagerTableSource)->not->toContain("'all' => arabic_text('الكل')")
        ->and($bookmarksManagerTableSource)->toContain("->modalSubmitActionLabel(arabic_text('تعديل'))")
        ->and($bookmarksManagerTableSource)->toContain("->modalSubmitAction(fn (Action \$action): Action => \$action->icon('heroicon-o-pencil-square'))")
        ->and($bookmarksManagerTableSource)->toContain("Action::make('remove')")
        ->and($bookmarksManagerTableSource)->toContain("->icon('heroicon-o-x-mark')");

    expect($quranReaderViewSource)->not->toBeFalse()
        ->and($quranReaderViewSource)->toContain(
            'x-on:quran-history-manager-removed.window="removeHistoryEntry($event.detail?.id)"',
        );

    expect($historyModalPartialSource)->not->toBeFalse()
        ->and($historyModalPartialSource)->toContain('quran-history-manager-request-sync');

    expect($bookmarksModalPartialSource)->not->toBeFalse()
        ->and($bookmarksModalPartialSource)->toContain('quran-bookmarks-manager-request-sync');
});

it('keeps sacred divine name tokens out of stem and root search stages', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $sacredTokenResults = $service->searchProgressively('تالله', 20);

    expect($sacredTokenResults)->toBeArray()->not->toBeEmpty()
        ->and(collect($sacredTokenResults)->contains(
            static fn (array $item): bool => (string) ($item['match_strategy'] ?? '') === 'ayah_exact',
        ))->toBeTrue()
        ->and(collect($sacredTokenResults)->contains(
            static fn (array $item): bool => in_array(
                (string) ($item['match_strategy'] ?? ''),
                ['ayah_sarf', 'ayah_jathr'],
                true,
            ),
        ))->toBeFalse();
});

it('matches conjunction-attached aal tokens before falling back to root search', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $results = $service->searchProgressively('آل عمران', 20);
    $firstResult = $results[0] ?? null;

    $aliImranVerseMatch = collect($results)->first(
        static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 3
            && (int) ($item['ayah_number'] ?? 0) === 33
            && ! str_starts_with((string) ($item['match_strategy'] ?? ''), 'surah_'),
    );

    expect($results)->toBeArray()->not->toBeEmpty()
        ->and($firstResult)->toBeArray()
        ->and((string) ($firstResult['match_strategy'] ?? ''))->toBe('surah_exact')
        ->and($aliImranVerseMatch)->toBeArray()
        ->and((string) ($aliImranVerseMatch['match_strategy'] ?? ''))->toBe('ayah_close');
});

it('guards mobile js error reporting against known benign runtime noise', function () {
    $mobileJsErrorsHandlerSource = file_get_contents(
        resource_path('views/components/partials/scripts/mobile-js-errors-handler.blade.php'),
    );
    $breakpointerSource = file_get_contents(resource_path('js/support/alpine/storage/breakpointer.js'));
    $livewireTransitionConsistencySource = file_get_contents(
        resource_path('js/overrides/livewire-transition-consistency.js'),
    );

    expect($mobileJsErrorsHandlerSource)->not->toBeFalse()
        ->and($mobileJsErrorsHandlerSource)->toContain('Uncaught [object Object]')
        ->and($mobileJsErrorsHandlerSource)->toContain('isLikelyOpaqueLivewireThrow')
        ->and($mobileJsErrorsHandlerSource)->toContain('shouldPreventDefaultErrorEvent')
        ->and($mobileJsErrorsHandlerSource)->toContain('event.preventDefault();');

    expect($breakpointerSource)->not->toBeFalse()
        ->and($breakpointerSource)->toContain("typeof window.AndroidBridge === 'undefined'");

    expect($livewireTransitionConsistencySource)->not->toBeFalse()
        ->and($livewireTransitionConsistencySource)->toContain('window.isRecordSelected = () => false;');
});

it('suppresses immersive mobile edge captions while quran manager modals are open', function () {
    $quranReaderChromeSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/reader-navigation-fit-page-adjust-and-chrome.js'),
    );

    expect($quranReaderChromeSource)->not->toBeFalse()
        ->and($quranReaderChromeSource)->toContain('shouldShowImmersiveMobileEdgeCaptions()')
        ->and($quranReaderChromeSource)->toContain('const hasManagerModalOpen =')
        ->and($quranReaderChromeSource)->toContain('this.isSearchModalWindowVisible() ||')
        ->and($quranReaderChromeSource)->toContain('this.isModalWindowVisibleById(this.historyModalId) ||')
        ->and($quranReaderChromeSource)->toContain('this.isModalWindowVisibleById(this.bookmarksModalId) ||')
        ->and($quranReaderChromeSource)->toContain('this.isModalWindowVisibleById(this.jumpPageModalId) ||')
        ->and($quranReaderChromeSource)->toContain('this.search.modalOpen ||')
        ->and($quranReaderChromeSource)->toContain('this.historyModalOpen ||')
        ->and($quranReaderChromeSource)->toContain('this.bookmarksModalOpen ||')
        ->and($quranReaderChromeSource)->toContain(
            'if (this.hasBlockingModalLifecycleState({ recoverStaleState: true })) {',
        );
});

it('returns matches for legacy orthography phrases in quran search endpoint', function () {
    if (! Schema::hasTable('quran_verses')) {
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
        ))->toBeTrue()
        ->and(collect($legacyItems)->contains(
            static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 10
                && (int) ($item['ayah_number'] ?? 0) === 25
                && (string) ($item['match_strategy'] ?? '') === 'ayah_exact',
        ))->toBeTrue();
});

it('matches quran orthography variants when the query drops the ra from al-quran', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $response = $this->getJson(route('quran-reader-search-index', [
        'q' => 'والقآن المجيد',
    ], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and((int) ($items[0]['surah_number'] ?? 0))->toBe(50)
        ->and((int) ($items[0]['ayah_number'] ?? 0))->toBe(1)
        ->and((string) ($items[0]['match_strategy'] ?? ''))->toBe('ayah_exact');
});

it('treats hamza-on-line alif phrase variants as exact quran matches', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $response = $this->getJson(route('quran-reader-search-index', [
        'q' => 'آمن الرسول بما',
    ], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);
    $targetMatch = collect($items)->first(
        static fn (array $item): bool => (int) ($item['surah_number'] ?? 0) === 2
            && (int) ($item['ayah_number'] ?? 0) === 285,
    );

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and($targetMatch)->toBeArray()
        ->and((string) ($targetMatch['match_strategy'] ?? ''))->toBe('ayah_exact');
});

it('exposes local quran search index payload for client-side instant preview', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    $response = $this->getJson(route('quran-reader-search-index', [
        'local' => 1,
    ], false));

    $response->assertSuccessful();

    $items = $response->json('items', []);

    expect($items)->toBeArray()->not->toBeEmpty()
        ->and((int) ($items[0]['id'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($items[0]['surah_number'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($items[0]['ayah_number'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($items[0]['page_number'] ?? 0))->toBeGreaterThan(0)
        ->and((string) ($items[0]['text_searchable_typed'] ?? ''))->not->toBe('');
});

it('normalizes invisible directional chars in quran search queries while preserving exact phrase ranking', function () {
    if (! Schema::hasTable('quran_verses')) {
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
                && (string) ($item['match_strategy'] ?? '') === 'ayah_exact',
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
        ->and((string) ($invocationItems[0]['match_strategy'] ?? ''))->toBe('ayah_exact');
});

it('caches repeated quran search queries while preserving complete progress emission', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    Cache::flush();

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
        ->and(count($progressEvents))->toBeGreaterThan(1)
        ->and($progressEvents[array_key_last($progressEvents)]['stage'])->toBe('complete')
        ->and($progressEvents[array_key_last($progressEvents)]['is_complete'])->toBeTrue()
        ->and((int) $progressEvents[array_key_last($progressEvents)]['count'])->toBe(count($firstResults));

    $nonCompleteEvents = array_values(array_filter(
        $progressEvents,
        static fn (array $event): bool => ! (bool) ($event['is_complete'] ?? false),
    ));
    $eventCounts = array_map(
        static fn (array $event): int => max(0, (int) ($event['count'] ?? 0)),
        $progressEvents,
    );

    expect($nonCompleteEvents)->not->toBeEmpty()
        ->and($eventCounts)->toEqual(collect($eventCounts)->sort()->values()->all());
});

it('emits uncached quran search progress in incremental result steps', function () {
    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    Cache::flush();
    $progressEvents = [];

    $service->searchProgressively(
        'آل عمران',
        24,
        function (array $matches, string $stage, bool $isComplete) use (&$progressEvents): void {
            if ($isComplete) {
                return;
            }

            $progressEvents[] = [
                'stage' => $stage,
                'count' => count($matches),
            ];
        },
    );

    $counts = array_map(
        static fn (array $event): int => max(0, (int) ($event['count'] ?? 0)),
        $progressEvents,
    );

    expect($progressEvents)->not->toBeEmpty()
        ->and($counts)->toEqual(collect($counts)->sort()->values()->all());

    for ($index = 1; $index < count($counts); $index++) {
        expect($counts[$index] - $counts[$index - 1])->toBeLessThanOrEqual(1);
    }
});

it('injects visible basmallah lines under late-page surah headers', function () {
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();
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
        ->and($page['basmallahFontUrl'] ?? null)->toBe(url('/vendor/arabicable/quran-common.woff2'))
        ->and($firstAyahLine)->toBeArray();

    expect(public_path('vendor/arabicable/quran-common.woff2'))->toBeFile();

    config()->set('arabicable.quran_fonts.basmalah.preferred', 'madina-default');

    $madinaPage = $service->resolvePage(604);

    expect($madinaPage['basmallahFontFamily'] ?? null)->toBe('MadinaQuran')
        ->and($madinaPage['basmallahFontUrl'] ?? null)->toBeNull()
        ->and($madinaPage['basmallahText'] ?? null)->toContain('بِسْمِ');
});

it('prefers published static quran helper font assets over dynamic binary routes', function () {
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();
    config()->set('arabicable.quran_fonts.basmalah.preferred', 'quran-common-ligature');

    $page = $service->resolvePage(604);

    expect($page['surahHeaderFontUrl'] ?? null)->toBe(url('/vendor/arabicable/surah-name-v4.ttf'))
        ->and($page['basmallahFontUrl'] ?? null)->toBe(url('/vendor/arabicable/quran-common.woff2'));
});

it('does not repeat surah preludes on continuation pages', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

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
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    /** @var object|null $firstAyah */
    $firstAyah = DB::table('quran_verses')
        ->select(['ayah_index', 'text_searchable_typed', 'text_uthmani'])
        ->where('surah_number', 1)
        ->where('ayah_number', 1)
        ->first();

    /** @var object|null $secondAyah */
    $secondAyah = DB::table('quran_verses')
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
        QuranWordCopyText::normalizeToken($uthmani, $typed) ?? '',
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
    if (! Schema::hasTable('quran_verses') || ! Schema::hasTable('quran_words')) {
        $this->markTestSkipped('Quran verses or words table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    $page = $service->resolvePage(604);
    $lines = collect($page['mushafLines'] ?? []);

    /** @var object|null $targetAyah */
    $targetAyah = DB::table('quran_verses')
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
        QuranWordCopyText::normalizeToken(
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

    $expectedWordTokens = collect(DB::table('quran_words')
        ->select(['token_uthmani', 'token_searchable_typed'])
        ->where('surah_number', 112)
        ->where('ayah_number', 1)
        ->orderBy('word_position')
        ->get())
        ->map(static fn (object $word): string => $normalize(
            QuranWordCopyText::normalizeToken(
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
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    $pageNumbers = DB::table('quran_mushaf_lines')
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
    if (! Schema::hasTable('quran_mushaf_lines')) {
        $this->markTestSkipped('Quran mushaf lines table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

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
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);
    Cache::flush();

    $normalize = static fn (?string $text): string => (string) preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $text),
    );

    $expectedAyahTextByIndex = [];

    foreach (DB::table('quran_verses')
        ->select(['ayah_index', 'text_uthmani', 'text_searchable_typed'])
        ->orderBy('ayah_index')
        ->get() as $verseRow) {
        $ayahIndex = (int) ($verseRow->ayah_index ?? 0);

        if ($ayahIndex < 1) {
            continue;
        }

        $normalizedAyahText = $normalize(
            QuranWordCopyText::normalizeToken(
                (string) ($verseRow->text_uthmani ?? ''),
                (string) ($verseRow->text_searchable_typed ?? ''),
            ) ?? '',
        );

        if ($normalizedAyahText === '') {
            continue;
        }

        $expectedAyahTextByIndex[$ayahIndex] = $normalizedAyahText;
    }

    $pageNumbers = DB::table('quran_mushaf_lines')
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

it('reacts to quran search query changes through an alpine watcher', function () {
    $quranReaderInitialStateSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/initial-state.js'),
    );
    $quranReaderLifecycleSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/lifecycle-bootstrap-environment-and-cache.js'),
    );
    $quranReaderSearchStreamSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/search-and-modals-stream-and-results.js'),
    );

    expect($quranReaderInitialStateSource)->not->toBeFalse()
        ->and($quranReaderInitialStateSource)->toContain('_stopSearchQueryWatcher: null');

    expect($quranReaderLifecycleSource)->not->toBeFalse()
        ->and($quranReaderLifecycleSource)->toContain(
            "this._stopSearchQueryWatcher = this.\$watch('search.query', () => {",
        )
        ->and($quranReaderLifecycleSource)->toContain('this.queueSearchResultsUpdate();');

    expect($quranReaderSearchStreamSource)->not->toBeFalse()
        ->and($quranReaderSearchStreamSource)->toContain('bindSearchModalInputSyncListener()');

    expect($quranReaderInitialStateSource)->toContain('inputDebounceMs: 600');
});

it('keeps quran search progressive stages and reader modal contracts aligned', function () {
    $quranReaderClassSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $quranSearchModalViewSource = file_get_contents(
        resource_path('views/components/partials/quran-app/search-modal.blade.php'),
    );
    $quranReaderSearchStreamSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/search-and-modals-stream-and-results.js'),
    );
    $quranReaderWarmSearchSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/manager-and-search-actions-warm-and-navigate.js'),
    );
    $quranReaderLocalIndexSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/manager-and-search-actions-ui-and-local-index.js'),
    );
    $quranReaderSharedSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/shared.js'),
    );
    $quranReaderInitialStateSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/initial-state.js'),
    );

    expect($quranReaderClassSource)->not->toBeFalse()
        ->and($quranReaderClassSource)->toContain('private const SEARCH_STREAM_PADDING_BYTES = 65536;')
        ->and($quranReaderClassSource)->toContain('private const SEARCH_STREAM_FRAME_DELIMITER =')
        ->and($quranReaderClassSource)->toContain("'pad' => str_repeat(' ', self::SEARCH_STREAM_PADDING_BYTES),")
        ->and($quranReaderClassSource)->toContain('replace: false,')
        ->and($quranReaderClassSource)->toContain(
            "\$this->streamSearchPayload(\n            [],\n            [],\n            \$normalizedRequestSerial,\n            'start',\n            false,\n        );",
        );

    $quranReaderDataServiceSource = file_get_contents(app_path('Services/Quran/QuranReaderDataService.php'));

    expect($quranReaderDataServiceSource)->not->toBeFalse()
        ->and($quranReaderDataServiceSource)->toContain('appendExactPhraseMatchesFromSearchIndex')
        ->and($quranReaderDataServiceSource)->toContain('appendSemanticTokenMatchesFromQuranWords')
        ->and($quranReaderDataServiceSource)->toContain('->lazy(512)')
        ->and($quranReaderDataServiceSource)->not->toContain('collectVerseIdsByStemTokens')
        ->and($quranReaderDataServiceSource)->not->toContain('collectVerseIdsByRootTokens');

    expect($quranSearchModalViewSource)->not->toBeFalse()
        ->and($quranSearchModalViewSource)->toContain('wire:stream="quran-search-results-stream"')
        ->and($quranSearchModalViewSource)->toContain('x-for="chunk in searchResultChunks()"');

    expect($quranReaderSharedSource)->not->toBeFalse()
        ->and($quranReaderSharedSource)->toContain('quranSearchStreamFrameDelimiter');

    expect($quranReaderInitialStateSource)->not->toBeFalse()
        ->and($quranReaderInitialStateSource)->toContain('_lastSearchStreamPayloadOffset')
        ->and($quranReaderInitialStateSource)->toContain('_searchStreamFrameRemainder');

    expect($quranReaderSearchStreamSource)->not->toBeFalse()
        ->and($quranReaderSearchStreamSource)->toContain('split(quranSearchStreamFrameDelimiter)')
        ->and($quranReaderSearchStreamSource)->toContain('prepareSearchUiForNextQuery(normalizedQuery = \'\')')
        ->and($quranReaderSearchStreamSource)->toContain(
            'this.prepareSearchUiForNextQuery(this.normalizeSearchQuery(this.search.query));',
        );

    expect($quranReaderWarmSearchSource)->not->toBeFalse()
        ->and($quranReaderWarmSearchSource)->toContain('warmSearchLocalIndex()')
        ->and($quranReaderWarmSearchSource)->toContain('applyLocalSearchPreview(normalizedQuery, requestSerial)');

    expect($quranReaderLocalIndexSource)->not->toBeFalse()
        ->and($quranReaderLocalIndexSource)->toContain('searchLocalIndexRequestUrl()');

    $searchIndexControllerSource = file_get_contents(
        app_path('Http/Controllers/Quran/ReaderSearchIndexController.php'),
    );

    expect($searchIndexControllerSource)->not->toBeFalse()
        ->and($searchIndexControllerSource)->toContain("\$isLocalIndexRequest = \$request->boolean('local');")
        ->and($searchIndexControllerSource)->toContain("'items' => \$readerDataService->searchIndex()");
});
