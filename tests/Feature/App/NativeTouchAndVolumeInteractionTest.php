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
        ->and($quranGateSource)->toContain('if (this.projectedMode) {')
        ->and($quranGateSource)->toContain('this.armMode(this.projectedMode);');

    expect($mainMenuSource)->not->toBeFalse()
        ->and($mainMenuSource)->toContain('insightsTouchRowsUnlockDelayMs: 120')
        ->and($mainMenuSource)->toContain('handleInsightsTouchStart(event = null)')
        ->and($mainMenuSource)->toContain('handleInsightsRowTouchEnd(mode, event = null)')
        ->and($mainMenuSource)->toContain("event.target.closest('.main-menu-insights-row--button')");

    expect($mainMenuViewSource)->not->toBeFalse()
        ->and($mainMenuViewSource)->toContain(
            'x-on:touchstart.passive="handleInsightsTouchStart($event)"',
        )
        ->and($mainMenuViewSource)->toContain(
            'x-on:touchend.stop.prevent="handleInsightsRowTouchEnd(row.key, $event)"',
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
            "if (normalizedDirection === 'previous' || normalizedDirection === 'prev') {",
        );
});
