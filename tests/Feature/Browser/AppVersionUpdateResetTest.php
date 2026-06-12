<?php

declare(strict_types=1);

uses()->group('browser-flaky');

it('returns native users to main menu and clears only transient caches when the app version major or minor changes', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'app.custom.app_version' => '2.0.0',
    ]);

    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page, true);
    waitForAlpineReady($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);

    setLocalStorageValue($page, 'athkar-settings-user-overrides-v1', [
        'enable_visual_enhancements' => true,
    ]);
    setLocalStorageValue($page, 'athkar-overrides-v1', [
        ['thikr_id' => 'athkar-1', 'order' => 2],
    ]);
    setLocalStorageValue($page, 'quran-reader-bookmarks-v1', [
        ['id' => 'bookmark-1', 'page_number' => 12],
    ]);
    setLocalStorageValue($page, 'quran-reader-navigation-history-v1', [
        ['id' => 'history-1', 'page_number' => 8],
    ]);
    setLocalStorageValue($page, 'quran-reader-last-page-v1', 123);
    setLocalStorageValue($page, 'quran-reader-wird-progress-v1', [
        'dayRecords' => [],
    ]);
    setLocalStorageValue($page, 'quran-reader-fit-cache-v18', [
        'version' => 18,
        'entries' => ['sample' => ['scale' => 1.2]],
        'order' => ['sample'],
    ]);
    setLocalStorageValue($page, 'quran-reader-content-version', '2026.06.12');

    $page->script(homeDataCommandScript("data.applyViewState('quran-app-gate', { persist: true });"));
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-gate');

    waitForScript($page, <<<'JS'
(() => {
  return (async () => {
    await window.appVersionRouting?.clearNativeUpdateCaches?.();
    return true;
  })();
})()
JS, true);
    $page->script(homeDataCommandScript("data.applyViewState('main-menu', { persist: true });"));

    waitForScript($page, homeDataScript('data.activeView'), 'main-menu');
    waitForScript(
        $page,
        'JSON.parse(localStorage.getItem("athkar-settings-user-overrides-v1"))?.enable_visual_enhancements === true',
        true,
    );
    waitForScript(
        $page,
        'JSON.parse(localStorage.getItem("athkar-overrides-v1"))?.[0]?.thikr_id === "athkar-1"',
        true,
    );
    waitForScript(
        $page,
        'JSON.parse(localStorage.getItem("quran-reader-bookmarks-v1"))?.[0]?.id === "bookmark-1"',
        true,
    );
    waitForScript(
        $page,
        'JSON.parse(localStorage.getItem("quran-reader-navigation-history-v1"))?.[0]?.id === "history-1"',
        true,
    );
    waitForScript($page, 'JSON.parse(localStorage.getItem("quran-reader-last-page-v1"))', 123);
    waitForScript(
        $page,
        'JSON.parse(localStorage.getItem("quran-reader-wird-progress-v1"))?.dayRecords && typeof JSON.parse(localStorage.getItem("quran-reader-wird-progress-v1")).dayRecords === "object"',
        true,
    );

    expect($page->script('JSON.parse(localStorage.getItem("quran-reader-last-page-v1"))'))->toBe(123);

});
