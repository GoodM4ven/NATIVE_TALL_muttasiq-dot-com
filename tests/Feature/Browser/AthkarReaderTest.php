<?php

declare(strict_types=1);

use App\Models\Thikr;
use App\Services\Enums\ThikrTime;

it('honors auto-advance and overcount settings on tap', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_automatically_switch_completed_athkar' => true,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$singleIndex}); data.setCount({$singleIndex}, 0, { allowOvercount: true });",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);

    $settings = [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$singleIndex}); data.setCount({$singleIndex}, 0, { allowOvercount: true });",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');
    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);
    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);
});

it('swipes count when setting 2 is enabled', function (bool $isMobile, string $pointerType) {
    $page = $isMobile ? visitMobile('/') : visit('/');

    resetBrowserState($page, $isMobile);
    openAthkarReader($page, 'sabah', $isMobile);

    $settings = [
        'does_clicking_switch_athkar_too' => true,
        'does_automatically_switch_completed_athkar' => true,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript("data.setActiveIndex({$singleIndex});"));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    swipeReader($page, 'forward', $pointerType);

    waitForScript($page, athkarReaderDataScript('data.countAt('.$singleIndex.')'), 1);
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);
})->with([
    'desktop' => [false, 'mouse'],
    // 'mobile' => [true, 'touch'],
]);

it('swipes only navigate without counting when setting 2 is disabled', function (bool $isMobile, string $pointerType) {
    $page = $isMobile ? visitMobile('/') : visit('/');

    resetBrowserState($page, $isMobile);
    openAthkarReader($page, 'sabah', $isMobile);

    $settings = [
        'does_clicking_switch_athkar_too' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$singleIndex}); data.setCount({$singleIndex}, 0, { allowOvercount: true });",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    swipeReader($page, 'forward', $pointerType);

    waitForScript($page, athkarReaderDataScript('data.countAt('.$singleIndex.')'), 0);
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);
})->with([
    'desktop' => [false, 'mouse'],
    // 'mobile' => [true, 'touch'],
]);

it('prevents swiping past incomplete athkar and allows quick navigation when disabled', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_automatically_switch_completed_athkar' => false,
        'does_clicking_switch_athkar_too' => false,
        'does_prevent_switching_athkar_until_completion' => true,
    ]);
    waitForScript($page, athkarReaderDataScript('data.settings.does_prevent_switching_athkar_until_completion'), true);

    swipeReader($page, 'forward', 'mouse');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), 0);

    $page->script(athkarReaderCommandScript('data.completeThikr(data.activeIndex);'));

    waitForScript(
        $page,
        athkarReaderDataScript('data.isItemComplete(data.activeIndex)'),
        true,
    );

    swipeReader($page, 'forward', 'mouse');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), 1);

    $settings = [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(
        athkarReaderCommandScript('data.setActiveIndex(data.activeList.length - 1);'),
    );

    $lastIndex = $page->script(athkarReaderDataScript('data.activeList.length - 1'));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $lastIndex);
});

it('persists athkar counts, overcounts, and restores the reader on reload', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ]);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript("data.setActiveIndex({$singleIndex});"));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');
    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);

    $progress = $page->script('JSON.parse(localStorage.getItem("athkar-progress-v1"))');

    expect($progress['sabah']['counts'][$singleIndex] ?? null)->toBe(2);

    waitForScript($page, 'JSON.parse(localStorage.getItem("athkar-active-mode"))', 'sabah');
    waitForScript($page, 'JSON.parse(localStorage.getItem("athkar-reader-visible"))', true);
    waitForScript($page, 'JSON.parse(localStorage.getItem("app-active-view"))', 'athkar-app-sabah');
    waitForScript($page, 'window.location.hash', '#athkar-app-sabah');

    $page->refresh();

    waitForAlpineReady($page);
    waitForReaderVisible($page);
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-sabah');
    waitForScript($page, athkarReaderDataScript('data.activeMode'), 'sabah');
    waitForScript($page, athkarReaderDataScript('data.countAt('.$singleIndex.')'), 2);
});

it('locks completed modes on the gate unless setting 3 is disabled', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_skip_notice_panels' => true,
        'does_prevent_switching_athkar_until_completion' => true,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(athkarReaderCommandScript('data.markAllActiveModeComplete();'));

    waitForGateVisible($page);
    waitForScript($page, athkarReaderDataScript('data.activeMode'), null);

    waitForScript($page, athkarReaderDataScript('data.isModeComplete("sabah")'), true);
    waitForScript($page, athkarReaderDataScript('data.isModeLocked("sabah")'), true);

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const badge = document.querySelector('button[aria-label="أذكار الصباح"] [x-show="isModeComplete(\'sabah\')"]');
  if (!badge) {
    return false;
  }
  return getComputedStyle(badge).display !== 'none';
})()
JS,
        true,
    );

    waitForScript(
        $page,
        'window.location.hash === "#athkar-app-gate" || window.location.hash === ""',
        true,
    );

    scriptClick($page, 'button[aria-label="أذكار الصباح"]');

    waitForScript(
        $page,
        'window.location.hash === "#athkar-app-gate" || window.location.hash === ""',
        true,
    );

    $settings = [
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    waitForScript($page, athkarReaderDataScript('data.isModeLocked("sabah")'), false);

    scriptClick($page, 'button[aria-label="أذكار الصباح"]');

    waitForScript($page, 'window.location.hash', '#athkar-app-sabah');
    waitForNoticeVisible($page);
});

it('executes hidden completion buttons on desktop for single thikr and all athkar', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_prevent_switching_athkar_until_completion' => false,
    ]);

    $multiIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item) => Number(item.count ?? 1) > 1)',
        ),
    );

    expect($multiIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript("data.setActiveIndex({$multiIndex});"));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $multiIndex);

    $desktopCompleteSelector = '[data-athkar-slide][data-active="true"] .sm\\:flex button[aria-label="إتمام الذكر"]';
    waitForScript(
        $page,
        js_template('Boolean(document.querySelector({{selector}}))', ['selector' => $desktopCompleteSelector]),
        true,
    );
    scriptClick($page, $desktopCompleteSelector);

    waitForScript(
        $page,
        athkarReaderDataScript('data.countAt('.$multiIndex.') === data.requiredCount('.$multiIndex.')'),
        true,
    );

    $page->script(athkarReaderCommandScript('data.showCompletionHack({ pinned: true })'));

    waitForScript($page, athkarReaderDataScript('data.completionHack.isVisible'), true);

    safeClick($page, 'button[aria-label="إتمام جميع الأذكار"]');

    waitForScript($page, 'Boolean(document.querySelector(".fi-modal-window"))', true);

    clickModalAction($page, 'قرأتها');

    waitForScript($page, athkarReaderDataScript('data.isModeComplete("sabah")'), true);
    waitForScript($page, athkarReaderDataScript('data.activeMode'), null);
});

it('tracks progress by letters and counters by counts while updating page position', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript("data.setActiveIndex({$singleIndex});"));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    $initialLetters = $page->script(athkarReaderDataScript('data.totalCompletedLetters'));
    $initialCounts = $page->script(athkarReaderDataScript('data.totalCompletedCount'));

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 1);

    $completedLetters = $page->script(athkarReaderDataScript('data.totalCompletedLetters'));
    $completedCounts = $page->script(athkarReaderDataScript('data.totalCompletedCount'));
    $completedPercent = $page->script(athkarReaderDataScript('data.slideProgressPercent'));

    expect($completedLetters)->toBeGreaterThan($initialLetters);
    expect($completedCounts)->toBe($initialCounts + 1);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);

    $overcountLetters = $page->script(athkarReaderDataScript('data.totalCompletedLetters'));
    $overcountCounts = $page->script(athkarReaderDataScript('data.totalCompletedCount'));
    $overcountPercent = $page->script(athkarReaderDataScript('data.slideProgressPercent'));

    expect($overcountLetters)->toBe($completedLetters);
    expect($overcountCounts)->toBe($completedCounts + 1);
    expect($overcountPercent)->toBe($completedPercent);

    scriptClick($page, 'button[aria-label="التالي"]');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);

    $pageCount = $page->script(athkarReaderDataScript('data.activeIndex + 1'));
    $totalPages = $page->script(athkarReaderDataScript('data.activeList.length'));

    expect($pageCount)->toBe($singleIndex + 2);
    expect($totalPages)->toBeGreaterThanOrEqual($pageCount);
});

it('exposes all athkar for the active mode and navigates when switching is allowed', function () {
    $expectedCount = Thikr::query()
        ->whereIn('time', [ThikrTime::Shared, ThikrTime::Sabah])
        ->count();

    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_prevent_switching_athkar_until_completion' => true,
    ]);

    $activeCount = $page->script(athkarReaderDataScript('data.activeList.length'));

    expect($activeCount)->toBe($expectedCount);

    waitForScript($page, athkarReaderDataScript('data.maxNavigableIndex'), 0);

    $settings = [
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(athkarReaderCommandScript('data.setActiveIndex(data.activeList.length - 1);'));

    $lastIndex = $page->script(athkarReaderDataScript('data.activeList.length - 1'));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $lastIndex);
});

it('shows the congrats panel briefly then returns to the gate when setting 4 is disabled', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_skip_notice_panels' => false,
    ]);

    $page->script(athkarReaderCommandScript('data.markAllActiveModeComplete();'));

    waitForScript($page, athkarReaderDataScript('data.isCompletionVisible'), true);
    waitForScriptWithTimeout($page, athkarReaderDataScript('data.isCompletionVisible'), false, 4000);
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript(
        $page,
        'window.location.hash === "#athkar-app-gate" || window.location.hash === ""',
        true,
    );
});

it('resets athkar progress when the day changes', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.totalCompletedCount'), 1);

    $page->script(
        athkarReaderCommandScript('data.lastSeenDay = "2000-01-01"; data.syncDay();'),
    );

    waitForScript($page, athkarReaderDataScript('data.activeMode'), null);
    waitForScript(
        $page,
        athkarReaderDataScript('data.progress.sabah.counts.every((count) => Number(count) === 0)'),
        true,
    );
});
