<?php

declare(strict_types=1);

uses()->group('browser-flaky');

it('loads quran fonts without relying on persistent browser cache state', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
    ]);

    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page, true);
    waitForAlpineReady($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);

    $page->script(homeDataCommandScript(<<<'JS'
data.applyViewState('quran-app-tilawa', { persist: true });
JS));

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, quranReaderDataScript('data.ready && Boolean(data.qpcPageFontUrl)'), true);

    $page->script(quranReaderCommandScript(<<<'JS'
(async () => {
  window.__fontFetchCalls = [];

  const originalFetch = window.fetch.bind(window);
  window.fetch = (input, init = {}) => {
    const url = String(input);

    if (url.includes('font-cache-test=1')) {
      window.__fontFetchCalls.push({
        cache: typeof init?.cache === 'string' ? init.cache : null,
        url,
      });
    }

    return originalFetch(input, init);
  };

  const cacheName = data.cacheNames?.fonts;
  const fontsCache = cacheName ? await caches.open(cacheName) : null;

  window.__fontCacheCountBefore = fontsCache ? (await fontsCache.keys()).length : null;

  await data.ensureDynamicFontFace({
    styleId: 'font-cache-test',
    family: 'FontCacheTest',
    url: `${data.qpcPageFontUrl}?font-cache-test=1`,
    format: data.qpcPageFontFormat,
  });

  window.__fontCacheCountAfter = fontsCache ? (await fontsCache.keys()).length : null;
  window.__fontFetchDone = true;
  return true;
})()
JS));

    waitForScript($page, 'window.__fontFetchDone === true', true);
    waitForScript($page, 'window.__fontFetchCalls.length > 0', true);
    waitForScript(
        $page,
        'window.__fontCacheCountBefore === window.__fontCacheCountAfter',
        true,
    );

    expect($page->script('window.__fontFetchCalls.length > 0'))->toBeTrue();
    expect($page->script('window.__fontCacheCountBefore === window.__fontCacheCountAfter'))->toBeTrue();
});
