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

    $page->script(<<<'JS'
(async () => {
  const createResponse = (value) => new Response(JSON.stringify(value), {
    headers: { 'Content-Type': 'application/json' },
  });
  const baseUrl = window.location.origin;

  const pagesCache = await caches.open('quran-reader-pages-v13');
  const searchCache = await caches.open('quran-reader-search-v3');
  const localIndexCache = await caches.open('quran-reader-search-local-index-v1');

  for (let index = 1; index <= 5; index += 1) {
    await pagesCache.put(new Request(`${baseUrl}/pages/${index}`), createResponse({ page: index }));
  }

  for (let index = 1; index <= 3; index += 1) {
    await searchCache.put(new Request(`${baseUrl}/search/${index}`), createResponse({ search: index }));
  }

  for (let index = 1; index <= 2; index += 1) {
    await localIndexCache.put(new Request(`${baseUrl}/local-index/${index}`), createResponse({ index }));
  }

  await window.appVersionRouting?.clearNativeUpdateCaches?.();

  const [trimmedPagesCache, trimmedSearchCache, trimmedLocalIndexCache] = await Promise.all([
    caches.open('quran-reader-pages-v13'),
    caches.open('quran-reader-search-v3'),
    caches.open('quran-reader-search-local-index-v1'),
  ]);

  const [pageKeys, searchKeys, localIndexKeys] = await Promise.all([
    trimmedPagesCache.keys(),
    trimmedSearchCache.keys(),
    trimmedLocalIndexCache.keys(),
  ]);

  window.__cacheCounts = {
    pages: pageKeys.length,
    search: searchKeys.length,
    localIndex: localIndexKeys.length,
  };
  window.__cacheCountsReady = true;
})()
JS);

    waitForScript($page, 'window.__cacheCountsReady === true', true);
    waitForScript($page, 'window.__cacheCounts.pages', 0);
    waitForScript($page, 'window.__cacheCounts.search', 0);
    waitForScript($page, 'window.__cacheCounts.localIndex', 0);

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
