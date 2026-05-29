<?php

declare(strict_types=1);

it('keeps native touch interaction contracts for quran gate and main menu insights', function () {
    $quranGateSource = file_get_contents(resource_path('js/support/alpine/data/quran-app-gate.js'));
    $mainMenuSource = file_get_contents(resource_path('js/support/alpine/data/main-menu.js'));
    $mainMenuViewSource = file_get_contents(resource_path('views/components/main-menu/index.blade.php'));

    expect($quranGateSource)->not->toBeFalse()
        ->and($quranGateSource)->toContain('modeForUiState()')
        ->and($quranGateSource)->toContain('this.modeForUiState() === mode')
        ->and($quranGateSource)->toContain('this.syncProjectedModeWithOrbitAngle(orbitAngleDeg);')
        ->and($quranGateSource)->toContain('this.setOrbitAngle(orbitAngleDeg);')
        ->and($quranGateSource)->toContain("if (event.pointerType !== 'touch' || !this.hasTouchInput()) {")
        ->and($quranGateSource)->toContain('didTouchOrbitMove')
        ->and($quranGateSource)->toContain('armProjectedModeAfterTouchRelease()')
        ->and($quranGateSource)->toContain('suppressNextOpenMode');

    expect($mainMenuSource)->not->toBeFalse()
        ->and($mainMenuSource)->toContain('insightsTouchRowsUnlockDelayMs: 120')
        ->and($mainMenuSource)->toContain('hasTouchInput()')
        ->and($mainMenuSource)->toContain('refreshTouchCapability({ resetTouchState = true } = {})')
        ->and($mainMenuSource)->toContain('handleInsightsTouchStart(event = null)')
        ->and($mainMenuSource)->toContain('handleInsightsRowTouchEnd(mode, event = null)')
        ->and($mainMenuSource)->toContain("event.target.closest('.main-menu-insights-row--button')")
        ->and($mainMenuSource)->toContain('const releaseElement = document.elementFromPoint(releaseX, releaseY);')
        ->and($mainMenuSource)->toContain(
            'if (isActiveItem && this.touchStartWasActive && !this.touchLeftStartItem) {',
        );

    expect($mainMenuViewSource)->not->toBeFalse()
        ->and($mainMenuViewSource)->toContain(
            'x-on:touchstart.passive="handleInsightsTouchStart($event)"',
        )
        ->and($mainMenuViewSource)->toContain(
            'x-on:touchend.stop="handleInsightsRowTouchEnd(row.key, $event)"',
        )
        ->and($mainMenuViewSource)->not->toContain('x-bind:disabled="isInsightsTouchRowsLocked()"');
});

it('keeps athkar gate touch flow as tap-to-arm then tap-to-enter', function () {
    $source = file_get_contents(resource_path('js/support/alpine/data/athkar-app-gate.js'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('if (this.hasTouchInput()) {')
        ->and($source)->toContain('this.activateSide(side);')
        ->and($source)->toContain("this.\$dispatch('athkar-gate-open', { mode });")
        ->and($source)->toContain('handleOutsideActivation()');
});

it('handles athkar native volume next actions through tap-completion flow', function () {
    $source = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/lifecycle-module.js'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain("if (normalizedDirection === 'next') {")
        ->and($source)->toContain('this.handleTap();')
        ->and($source)->toContain(
            'if (completedRequired <= 1 && nextRequired <= 1 && this.isMobileViewport()) {',
        )
        ->and($source)->toContain(
            "if (normalizedDirection === 'previous' || normalizedDirection === 'prev') {",
        );
});

it('uses touch capability for quran double tap copy mode beyond base breakpoint', function () {
    $source = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/selection-copy-settings-and-drag-state.js'),
    );
    $swipeSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/reader-navigation-fit-idle-warmup-and-scale-controls.js'),
    );
    $selectionPointerSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/selection-copy-compose-and-pointer.js'),
    );
    $readerViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain("if (typeof this.\$store?.bp?.isTouch === 'function') {")
        ->and($source)->toContain('return Boolean(this.$store.bp.isTouch() || this.$store.bp.hasTouch);')
        ->and($source)->toContain('return Boolean(this.$store?.bp?.hasTouch);');

    expect($swipeSource)->not->toBeFalse()
        ->and($swipeSource)->toContain('this.wordPress?.isSecondTap')
        ->and($swipeSource)->toContain('this.resetSwipeState();');

    expect($selectionPointerSource)->not->toBeFalse()
        ->and($selectionPointerSource)->toContain('this.wordPress.isSecondTap = shouldTreatAsSecondTap;')
        ->and($selectionPointerSource)->not->toContain('mobileDoubleTapHoldDelayMs');

    expect($readerViewSource)->not->toBeFalse()
        ->and($readerViewSource)->toContain(
            'x-on:pointermove.window.passive="onWordPointerMove($event)"',
        );
});

it('keeps the screen awake only while quran or athkar reader views are active', function () {
    $screenAwakeSource = file_get_contents(resource_path('js/support/screen-awake.js'));
    $appSource = file_get_contents(resource_path('js/app.js'));
    $quranLifecycleSource = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/lifecycle-bootstrap-environment-and-cache.js'),
    );
    $athkarLifecycleSource = file_get_contents(
        resource_path('js/support/alpine/data/athkar-app-reader/lifecycle-module.js'),
    );

    expect($screenAwakeSource)->not->toBeFalse()
        ->and($screenAwakeSource)->toContain('navigator.wakeLock.request(\'screen\')')
        ->and($screenAwakeSource)->toContain("document.addEventListener('visibilitychange', () => {")
        ->and($screenAwakeSource)->toContain('bridge.setScreenAwake')
        ->and($screenAwakeSource)->toContain('releaseWebWakeLock()')
        ->and($screenAwakeSource)->toContain('acquireScreenAwakeLock')
        ->and($screenAwakeSource)->toContain('releaseScreenAwakeLock');

    expect($appSource)->not->toBeFalse()
        ->and($appSource)->toContain("import './support/screen-awake';");

    expect($quranLifecycleSource)->not->toBeFalse()
        ->and($quranLifecycleSource)->toContain('syncReaderScreenAwakeLock()')
        ->and($quranLifecycleSource)->toContain('releaseReaderScreenAwakeLock()')
        ->and($quranLifecycleSource)->toContain('this._readerScreenAwakeLockToken = acquireScreenAwakeLock();');

    expect($athkarLifecycleSource)->not->toBeFalse()
        ->and($athkarLifecycleSource)->toContain('shouldKeepAthkarReaderScreenAwake()')
        ->and($athkarLifecycleSource)->toContain('syncReaderScreenAwakeLock()')
        ->and($athkarLifecycleSource)->toContain('this._readerScreenAwakeLockToken = acquireScreenAwakeLock();');
});
