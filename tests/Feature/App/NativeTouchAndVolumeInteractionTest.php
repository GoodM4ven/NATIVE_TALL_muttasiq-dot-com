<?php

declare(strict_types=1);

it('keeps native touch interaction contracts for quran gate and main menu insights', function () {
    $quranGateSource = file_get_contents(resource_path('js/support/alpine/data/quran-app-gate.js'));
    $mainMenuSource = file_get_contents(resource_path('js/support/alpine/data/main-menu.js'));
    $mainMenuViewSource = file_get_contents(resource_path('views/components/main-menu/index.blade.php'));

    expect($quranGateSource)->not->toBeFalse()
        ->and($quranGateSource)->toContain('this.clearArmedMode();')
        ->and($quranGateSource)->toContain('this.syncProjectedModeWithOrbitAngle(orbitAngleDeg);')
        ->and($quranGateSource)->toContain('this.setOrbitAngle(orbitAngleDeg);');

    expect($mainMenuSource)->not->toBeFalse()
        ->and($mainMenuSource)->toContain('insightsTouchRowsUnlockDelayMs: 260')
        ->and($mainMenuSource)->toContain('handleInsightsTouchStart(event = null)')
        ->and($mainMenuSource)->toContain("event.target.closest('.main-menu-insights-row--button')");

    expect($mainMenuViewSource)->not->toBeFalse()
        ->and($mainMenuViewSource)->toContain(
            'x-on:touchstart.passive="handleInsightsTouchStart($event)"',
        );
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
