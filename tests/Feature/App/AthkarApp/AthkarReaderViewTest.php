<?php

declare(strict_types=1);

it('keeps manager button styling and mobile counter expansion layout contracts in the reader view', function () {
    $source = file_get_contents(resource_path('views/components/partials/athkar-app/reader.blade.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('athkar-chip--manager')
        ->and($source)->toContain('data-athkar-open-manager')
        ->and($source)->toContain('--athkar-manager-button-fill-start: color-mix(in srgb, var(--primary-600)')
        ->and($source)->toContain('--athkar-manager-button-fill-start: color-mix(in srgb, var(--primary-100)')
        ->and($source)->toContain('--athkar-manager-button-fill-end: color-mix(in srgb, var(--primary-50)')
        ->and($source)->toContain('--athkar-manager-button-bevel:')
        ->and($source)->toContain('--athkar-manager-button-text: var(--primary-600)')
        ->and($source)->toContain('inset 0 0 0 1px var(--athkar-manager-button-bevel),')
        ->and($source)->toContain('.athkar-chip--manager::after')
        ->and($source)->toContain('transition: opacity 220ms ease;')
        ->and($source)->toContain('.athkar-chip--manager:hover::after')
        ->and($source)->toContain('animation: athkar-manager-sheen 500ms linear;');
    expect($source)
        ->toContain('absolute inset-x-0 top-2 z-30 h-10 overflow-visible sm:hidden')
        ->and($source)->toContain('absolute inset-x-0 top-0 h-11 overflow-visible')
        ->and($source)->toContain('group relative h-11')
        ->and($source)->toMatch('/class=\"pointer-events-auto absolute left-1\\/2 top-0 z-20 flex size-\\[2\\.6rem\\][^\"]*touch-manipulation[^\"]*\"/')
        ->and($source)->toContain("x-bind:class=\"isHintOpen(activeIndex) ? '")
        ->and($source)->toContain("pointer-events-none' : ''\"");
});

it('persists and reuses athkar notice bypass flags after first acknowledged display', function () {
    $sharedSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/shared.js'));
    $initialStateSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/initial-state.js'));
    $modeFlowSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/mode-flow-module.js'));
    $lifecycleSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/lifecycle-module.js'));
    $noticeViewSource = file_get_contents(resource_path('views/components/partials/athkar-app/notice.blade.php'));

    expect($sharedSource)->not->toBeFalse()
        ->and($sharedSource)->toContain("noticeBypassFlagsStorageKey = 'athkar-notice-bypass-flags-v1'")
        ->and($sharedSource)->toContain("athkarReaderNoticeBypassKey = 'athkar-reader-notice-v2'");

    expect($initialStateSource)->not->toBeFalse()
        ->and($initialStateSource)->toContain('noticeBypassFlags: window.Alpine.$persist({}).as(noticeBypassFlagsStorageKey)');

    expect($modeFlowSource)->not->toBeFalse()
        ->and($modeFlowSource)->toContain('shouldBypassNoticeOnce(athkarReaderNoticeBypassKey)')
        ->and($modeFlowSource)->toContain('markNoticeDisplayed(athkarReaderNoticeBypassKey)')
        ->and($modeFlowSource)->toContain('markNoticeBypassedOnce(normalizedNoticeKey)')
        ->and($modeFlowSource)->toContain('const shouldMarkBypassed = options?.markBypassed === true;')
        ->and($modeFlowSource)->toContain('confirmNoticeAndBypassFutureDisplay()')
        ->and($modeFlowSource)->toContain('this.confirmNotice({ markBypassed: false });');

    expect($lifecycleSource)->not->toBeFalse()
        ->and($lifecycleSource)->toContain('this.confirmNotice({ markBypassed: false });');

    expect($noticeViewSource)->not->toBeFalse()
        ->and($noticeViewSource)->toContain('أو لا تظهر هذا مجدّدًا')
        ->and($noticeViewSource)->toContain('athkar-notice__cta-subtext')
        ->and($noticeViewSource)->toContain('x-on:click.stop="confirmNoticeAndBypassFutureDisplay()"');
});
