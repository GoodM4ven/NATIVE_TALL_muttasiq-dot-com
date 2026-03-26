<?php

declare(strict_types=1);

it('navigates to quran gate, persists it across refresh, and handles native back on mobile', function () {
    $desktopPage = visit('/');

    resetBrowserState($desktopPage);
    waitForScript($desktopPage, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($desktopPage, '#quran-app-gate', true);

    waitForScript($desktopPage, homeDataScript('data.activeView'), 'quran-app-gate');
    waitForScript($desktopPage, 'window.location.hash', '#quran-app-gate');
    waitForQuranGateVisible($desktopPage);
    waitForScript(
        $desktopPage,
        'JSON.parse(localStorage.getItem("app-active-view"))',
        'quran-app-gate',
    );

    $desktopPage->refresh();

    waitForAlpineReady($desktopPage);
    waitForScript($desktopPage, homeDataScript('data.activeView'), 'quran-app-gate');
    waitForScript($desktopPage, 'window.location.hash', '#quran-app-gate');
    waitForQuranGateVisible($desktopPage);

    $mobilePage = visit('/');

    resetBrowserState($mobilePage, true);
    waitForScript($mobilePage, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($mobilePage, '#quran-app-gate', true);

    waitForScript($mobilePage, homeDataScript('data.activeView'), 'quran-app-gate');
    waitForScript($mobilePage, 'window.location.hash', '#quran-app-gate');
    waitForQuranGateVisible($mobilePage);

    expect($mobilePage->script('window.__nativeBackAction()'))->toBeTrue();
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');

    expect($mobilePage->script('window.__nativeBackAction()'))->toBe('exit');
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');
});

it('keeps quran reader stable for layout, slider navigation, and modal refit timing', function () {
    $page = visit('/');

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageScale ?? 0) > 0'),
        true,
        6_000,
    );

    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const panel = document.querySelector('.quran-reader-panel');
  if (!panel) {
    return false;
  }

  const panelWidth = panel.getBoundingClientRect().width;
  const viewportWidth = Math.max(0, Number(window.innerWidth || 0));
  const minPanelWidth = viewportWidth >= 1024
    ? 620
    : Math.max(520, Math.floor(viewportWidth * 0.78));

  return panelWidth >= minPanelWidth;
})()
JS,
        true,
        5_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const chip = document.querySelector('.quran-page-slider-chip');
  const slider = document.querySelector('.quran-page-slider');
  if (!chip || !slider) {
    return false;
  }

  const chipRect = chip.getBoundingClientRect();
  const sliderRect = slider.getBoundingClientRect();

  return sliderRect.top >= chipRect.bottom - 1;
})()
JS,
        true,
        5_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const footer = document.querySelector('.quran-bottom-strip');
  const prev = document.querySelector('.quran-bottom-strip-nav-prev');
  const next = document.querySelector('.quran-bottom-strip-nav-next');
  if (!footer || !prev || !next) {
    return false;
  }

  const footerRect = footer.getBoundingClientRect();
  const footerMid = footerRect.top + (footerRect.height / 2);
  const prevRect = prev.getBoundingClientRect();
  const nextRect = next.getBoundingClientRect();
  const prevMid = prevRect.top + (prevRect.height / 2);
  const nextMid = nextRect.top + (nextRect.height / 2);

  return Math.abs(prevMid - footerMid) <= 5 && Math.abs(nextMid - footerMid) <= 5;
})()
JS,
        true,
        5_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  if (window.innerWidth < 1536) {
    return true;
  }

  const panel = document.querySelector('.quran-reader-panel');
  if (!panel) {
    return false;
  }

  return panel.getBoundingClientRect().width <= 900;
})()
JS,
        true,
        5_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const lines = Array.from(document.querySelectorAll('.quran-page-lines [data-quran-line-text]'));
  if (!lines.length) {
    return false;
  }

  return lines.every((line) => {
    if (line.querySelector('.quran-word-button')) {
      return line.querySelectorAll('.quran-word-button').length > 0;
    }

    const text = String(line.textContent ?? '').replace(/\s+/g, '').trim();
    return text.length > 0;
  });
})()
JS,
        true,
        5_000,
    );

    $sliderTargetPage = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const slider = document.querySelector('.quran-page-slider');
  if (!slider) {
    return 0;
  }

  const currentPage = Number(data.pageInput ?? data.pageNumber ?? 1);
  const targetPage = Math.min(Number(data.maxPage ?? 1), currentPage + 1);
  slider.value = String(targetPage);
  slider.dispatchEvent(new Event('input', { bubbles: true }));
  slider.dispatchEvent(new Event('change', { bubbles: true }));

  return targetPage;
})()
JS,
        ),
    );

    expect((int) $sliderTargetPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), true, 1_200);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageInput'), (int) $sliderTargetPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), (int) $sliderTargetPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 2_500);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageScale ?? 0) > 0'),
        true,
        6_000,
    );

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);
    waitForScript($page, quranReaderDataScript('data.isFittingPage'), true);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const grid = document.querySelector('#quran-reader-search-modal .quran-surah-grid');
  const activeTile = document.querySelector('#quran-reader-search-modal .quran-surah-tile--active');
  if (!grid || !activeTile) {
    return false;
  }

  const gridRect = grid.getBoundingClientRect();
  const tileRect = activeTile.getBoundingClientRect();

  return tileRect.top >= gridRect.top - 4
    && tileRect.bottom <= gridRect.bottom + 4;
})()
JS,
        true,
        5_000,
    );

    $targetSurahSelection = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const currentPage = Number(data.pageInput ?? data.pageNumber ?? 1);
  const directory = Array.isArray(data.search?.surahDirectory) ? data.search.surahDirectory : [];
  const index = directory.findIndex((entry) => Number(entry?.page_number ?? 0) > currentPage);
  const fallbackIndex = directory.findIndex((entry) => Number(entry?.page_number ?? 0) > 1);
  const effectiveIndex = index >= 0 ? index : fallbackIndex;
  const entry = effectiveIndex >= 0 ? directory[effectiveIndex] : null;

  return {
    index: effectiveIndex,
    pageNumber: Number(entry?.page_number ?? 0),
  };
})()
JS,
        ),
    );

    expect($targetSurahSelection)->toBeArray();
    expect((int) ($targetSurahSelection['index'] ?? -1))->toBeGreaterThanOrEqual(0);
    expect((int) ($targetSurahSelection['pageNumber'] ?? 0))->toBeGreaterThan(1);

    $page->script(js_template(
        <<<'JS'
(() => {
  const index = Number({{index}});
  const tiles = Array.from(document.querySelectorAll('#quran-reader-search-modal .quran-surah-tile'));
  const tile = tiles[index];

  if (!tile) {
    return false;
  }

  tile.click();

  return true;
})()
JS,
        ['index' => (int) ($targetSurahSelection['index'] ?? -1)],
    ));

    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.pageInput'),
        (int) ($targetSurahSelection['pageNumber'] ?? 0),
        6_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);
    waitForScript($page, quranReaderDataScript('data.isFittingPage'), true);
    safeClick($page, '.fi-modal-window .fi-modal-close-btn');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), true, 400);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 3_000);

    safeClick($page, '.quran-page-slider-chip');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector(".fi-modal-window"))', true, 5_000);
    waitForScript($page, quranReaderDataScript('data.isFittingPage'), true);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const modal = document.querySelector('#quran-reader-jump-page-modal');
  if (!modal) {
    return false;
  }

  const modalWidth = modal.getBoundingClientRect().width;
  const viewportWidth = Math.max(0, Number(window.innerWidth || 0));
  const maxAllowedWidth = Math.min(320, Math.floor(viewportWidth * 0.5));

  return modalWidth <= maxAllowedWidth;
})()
JS,
        true,
        5_000,
    );
    safeClick($page, '.fi-modal-window .fi-modal-close-btn');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), true, 400);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 3_000);

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(604, 'test-quran-fit-last-page');"),
    );

    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), 604, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const frame = document.querySelector('[x-ref="pageFrame"]');
  const lines = document.querySelector('.quran-page-lines');

  if (!frame || !lines) {
    return false;
  }

  const frameRect = frame.getBoundingClientRect();
  const linesRect = lines.getBoundingClientRect();

  if (frameRect.height <= 0 || linesRect.height <= 0) {
    return false;
  }

  return (linesRect.height / frameRect.height) >= 0.64;
})()
JS,
        true,
        6_000,
    );
});

it('persists local reader state for last page, navigation history, and bookmarks', function () {
    $page = visit('/');

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(3, 'test-reader-fit-height');"),
    );

    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), 3, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const frame = document.querySelector('[x-ref="pageFrame"]');
  const lines = document.querySelector('.quran-page-lines');

  if (!frame || !lines) {
    return false;
  }

  const frameRect = frame.getBoundingClientRect();
  const linesRect = lines.getBoundingClientRect();

  if (frameRect.height <= 0 || linesRect.height <= 0) {
    return false;
  }

  return (linesRect.height / frameRect.height) >= 0.76;
})()
JS,
        true,
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const frame = document.querySelector('[x-ref="pageFrame"]');
  const lines = document.querySelector('.quran-page-lines');

  if (!frame || !lines) {
    return false;
  }

  const frameRect = frame.getBoundingClientRect();
  const linesRect = lines.getBoundingClientRect();

  if (frameRect.width <= 0 || linesRect.width <= 0) {
    return false;
  }

  return (linesRect.width / frameRect.width) >= 0.8;
})()
JS,
        true,
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        "Number(JSON.parse(localStorage.getItem('quran-reader-last-page-v1') ?? 'null') ?? 0)",
        3,
        5_000,
    );

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);

    $quickNavTarget = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const currentPage = Number(data.pageInput ?? data.pageNumber ?? 1);
  const directory = Array.isArray(data.search?.surahDirectory) ? data.search.surahDirectory : [];
  const entry = directory.find((item) => Number(item?.page_number ?? 0) > currentPage) ?? directory[0] ?? null;

  return {
    surahNumber: Number(entry?.surah_number ?? 0),
    pageNumber: Number(entry?.page_number ?? 0),
  };
})()
JS,
        ),
    );

    expect($quickNavTarget)->toBeArray();
    expect((int) ($quickNavTarget['surahNumber'] ?? 0))->toBeGreaterThan(0);
    expect((int) ($quickNavTarget['pageNumber'] ?? 0))->toBeGreaterThan(0);

    $page->script(
        quranReaderCommandScript(
            js_template(
                <<<'JS'
const targetSurah = Number({{surahNumber}});
const entry = (Array.isArray(data.search?.surahDirectory) ? data.search.surahDirectory : [])
  .find((item) => Number(item?.surah_number ?? 0) === targetSurah);

if (!entry) {
  return false;
}

data.goToSurahFromDirectory(entry);

return true;
JS,
                ['surahNumber' => (int) ($quickNavTarget['surahNumber'] ?? 0)],
            ),
        ),
    );

    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.pageNumber'),
        (int) ($quickNavTarget['pageNumber'] ?? 0),
        6_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            "data.navigationHistory.some((entry) => entry.source === 'surah-directory' && Number(entry.page_number ?? 0) === Number(data.pageNumber ?? 0))",
        ),
        true,
        6_000,
    );

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);
    $page->script(<<<'JS'
(() => {
  const searchInput = document.querySelector('#quran-reader-search-input');

  if (!(searchInput instanceof HTMLInputElement)) {
    return false;
  }

  searchInput.value = 'الذين';
  searchInput.dispatchEvent(new Event('input', { bubbles: true }));

  return true;
})()
JS);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.search.results.length > 0'), true, 12_000);

    $targetSearchPage = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const currentPage = Number(data.pageNumber ?? 0);
  const results = Array.isArray(data.search?.results) ? data.search.results : [];
  const result = results.find((entry) => Number(entry?.page_number ?? 0) !== currentPage) ?? results[0] ?? null;

  return Number(result?.page_number ?? 0);
})()
JS,
        ),
    );

    expect((int) $targetSearchPage)->toBeGreaterThan(0);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
const currentPage = Number(data.pageNumber ?? 0);
const results = Array.isArray(data.search?.results) ? data.search.results : [];
const result = results.find((entry) => Number(entry?.page_number ?? 0) !== currentPage) ?? results[0] ?? null;

if (!result) {
  return false;
}

data.goToSearchResult(result);

return true;
JS,
        ),
    );

    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), (int) $targetSearchPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            "data.navigationHistory.some((entry) => entry.source === 'search-result' && Number(entry.page_number ?? 0) === Number(data.pageNumber ?? 0))",
        ),
        true,
        6_000,
    );

    scriptClick($page, '[data-quran-open-history]');
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-history-modal"))',
        true,
        5_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.navigationHistory.length >= 2'), true, 6_000);

    $page->script(<<<'JS'
(() => {
  const tagsInput = document.querySelector('#quran-reader-history-modal [data-quran-history-tags]');

  if (!(tagsInput instanceof HTMLInputElement)) {
    return false;
  }

  tagsInput.value = 'مميز';
  tagsInput.dispatchEvent(new Event('input', { bubbles: true }));

  return true;
})()
JS);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            "data.navigationHistory.some((entry) => Array.isArray(entry?.tags) && entry.tags.includes('مميز'))",
        ),
        true,
        6_000,
    );

    safeClick($page, '#quran-reader-history-modal [data-quran-history-clear]');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'data.navigationHistory.length >= 1 && data.navigationHistory.every((entry) => Array.isArray(entry?.tags) && entry.tags.length > 0)',
        ),
        true,
        6_000,
    );

    safeClick($page, '.fi-modal-window .fi-modal-close-btn');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);

    scriptClick($page, '[data-quran-bookmark-toggle]');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.bookmarks.length >= 1 && data.isCurrentPageBookmarked()'),
        true,
        6_000,
    );

    $bookmarkReplacementTarget = $page->script(
        quranReaderDataScript('Math.min(Number(data.maxPage ?? 1), Number(data.pageNumber ?? 1) + 1)'),
    );

    expect((int) $bookmarkReplacementTarget)->toBeGreaterThan(0);

    $page->script(
        quranReaderCommandScript(
            js_template(
                "data.dispatchPageNavigationRequest({{page}}, 'test-bookmark-replace');",
                ['page' => (int) $bookmarkReplacementTarget],
            ),
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.pageNumber'),
        (int) $bookmarkReplacementTarget,
        6_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->script(<<<'JS'
(() => {
  const bookmarkButton = document.querySelector('[data-quran-bookmark-toggle]');

  if (!(bookmarkButton instanceof HTMLButtonElement)) {
    return false;
  }

  bookmarkButton.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 707,
      pointerType: 'mouse',
    }),
  );

  window.setTimeout(() => {
    bookmarkButton.dispatchEvent(
      new PointerEvent('pointerup', {
        bubbles: true,
        pointerId: 707,
        pointerType: 'mouse',
      }),
    );
  }, 740);

  return true;
})()
JS);

    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-bookmarks-modal"))',
        true,
        6_000,
    );

    safeClick($page, '#quran-reader-bookmarks-modal [data-quran-bookmark-replace]');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'data.bookmarks.length >= 1 && Number(data.bookmarks[0]?.page_number ?? 0) === Number(data.pageNumber ?? 0)',
        ),
        true,
        6_000,
    );

    $page->script(<<<'JS'
(() => {
  const titleInput = document.querySelector('#quran-reader-bookmarks-modal [data-quran-bookmark-title]');

  if (!(titleInput instanceof HTMLInputElement)) {
    return false;
  }

  titleInput.value = 'علامة اختبارية';
  titleInput.dispatchEvent(new Event('input', { bubbles: true }));

  return true;
})()
JS);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            "data.bookmarks.some((bookmark) => String(bookmark?.title ?? '').includes('اختبارية'))",
        ),
        true,
        6_000,
    );

    $page->script(
        quranReaderCommandScript(
            "data.dispatchPageNavigationRequest(Math.min(Number(data.maxPage ?? 1), Number(data.pageNumber ?? 1) + 1), 'test-bookmark-go');",
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0) > 1'),
        true,
        6_000,
    );

    safeClick($page, '#quran-reader-bookmarks-modal [data-quran-bookmark-go]');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.pageNumber'),
        (int) $bookmarkReplacementTarget,
        6_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $bookmarksBeforeRemoval = $page->script(quranReaderDataScript('Number(data.bookmarks.length ?? 0)'));
    $bookmarkPageForRemoval = $page->script(quranReaderDataScript('Number(data.bookmarks[0]?.page_number ?? 0)'));

    expect((int) $bookmarksBeforeRemoval)->toBeGreaterThan(0);
    expect((int) $bookmarkPageForRemoval)->toBeGreaterThan(0);

    $page->script(
        quranReaderCommandScript(
            js_template(
                "data.dispatchPageNavigationRequest({{page}}, 'test-bookmark-remove');",
                ['page' => (int) $bookmarkPageForRemoval],
            ),
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.pageNumber'),
        (int) $bookmarkPageForRemoval,
        6_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->script(quranReaderCommandScript('data.toggleCurrentPageBookmark();'));
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            js_template(
                'Number(data.bookmarks.length ?? 0) < Number({{before}})',
                ['before' => (int) $bookmarksBeforeRemoval],
            ),
        ),
        true,
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        'Array.isArray(JSON.parse(localStorage.getItem("quran-reader-navigation-history-v1") ?? "[]"))',
        true,
        4_000,
    );
    waitForScriptWithTimeout(
        $page,
        'Array.isArray(JSON.parse(localStorage.getItem("quran-reader-bookmarks-v1") ?? "[]"))',
        true,
        4_000,
    );

    $persistedPage = $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));

    expect((int) $persistedPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout(
        $page,
        "Number(JSON.parse(localStorage.getItem('quran-reader-last-page-v1') ?? 'null') ?? 0)",
        (int) $persistedPage,
        5_000,
    );

    hashAction($page, '#quran-app-gate', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-gate');
    waitForQuranGateVisible($page);

    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), (int) $persistedPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->assertNoJavaScriptErrors();
});

it('auto-copies activated text with popover feedback and uses normal history modal', function () {
    $page = visit('/');

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->script(<<<'JS'
(() => {
  window.__copiedTexts = [];
  const clipboard = {
    writeText(value) {
      window.__copiedTexts.push(String(value ?? ''));
      return Promise.resolve();
    },
  };

  try {
    Object.defineProperty(navigator, 'clipboard', {
      configurable: true,
      value: clipboard,
    });
  } catch (_) {
    navigator.clipboard = clipboard;
  }

  return true;
})()
JS);

    $didClickWord = $page->script(<<<'JS'
(() => {
  const wordButton = document.querySelector('.quran-page-lines .quran-word-button');

  if (!(wordButton instanceof HTMLButtonElement)) {
    return false;
  }

  const rect = wordButton.getBoundingClientRect();
  const point = {
    x: rect.left + (rect.width / 2),
    y: rect.top + (rect.height / 2),
  };

  window.__copyClickPoint = point;

  wordButton.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 912,
      pointerType: 'mouse',
      clientX: point.x,
      clientY: point.y,
    }),
  );

  wordButton.dispatchEvent(
    new PointerEvent('pointerup', {
      bubbles: true,
      pointerId: 912,
      pointerType: 'mouse',
      clientX: point.x,
      clientY: point.y,
    }),
  );

  wordButton.dispatchEvent(
    new MouseEvent('click', {
      bubbles: true,
      clientX: point.x,
      clientY: point.y,
    }),
  );

  return true;
})()
JS);

    expect($didClickWord)->toBeTrue();

    waitForScriptWithTimeout($page, 'Number(window.__copiedTexts?.length ?? 0) >= 1', true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.copyFeedback.visible'), true, 4_000);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const popover = document.querySelector('[data-quran-copy-popover]');
  const point = window.__copyClickPoint;

  if (!(popover instanceof HTMLElement) || !point) {
    return false;
  }

  const left = Number.parseFloat(popover.style.left || 'NaN');
  const top = Number.parseFloat(popover.style.top || 'NaN');

  return Number.isFinite(left)
    && Number.isFinite(top)
    && Math.abs(left - Number(point.x)) <= 1.5
    && Math.abs(top - Number(point.y)) <= 1.5;
})()
JS,
        true,
        5_000,
    );

    $didHoldWord = $page->script(<<<'JS'
(() => {
  const wordButton = document.querySelector('.quran-page-lines .quran-word-button');

  if (!(wordButton instanceof HTMLButtonElement)) {
    return false;
  }

  const rect = wordButton.getBoundingClientRect();
  const point = {
    x: rect.left + (rect.width / 2),
    y: rect.top + (rect.height / 2),
  };

  window.__copyHoldPoint = point;

  wordButton.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 913,
      pointerType: 'mouse',
      clientX: point.x,
      clientY: point.y,
    }),
  );

  window.setTimeout(() => {
    wordButton.dispatchEvent(
      new PointerEvent('pointerup', {
        bubbles: true,
        pointerId: 913,
        pointerType: 'mouse',
        clientX: point.x,
        clientY: point.y,
      }),
    );
  }, 820);

  return true;
})()
JS);

    expect($didHoldWord)->toBeTrue();

    waitForScriptWithTimeout($page, 'Number(window.__copiedTexts?.length ?? 0) >= 2', true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.copyFeedback.serial ?? 0) >= 2'), true, 6_000);

    $copiedTexts = $page->script('window.__copiedTexts');

    expect($copiedTexts)->toBeArray()
        ->and(trim((string) ($copiedTexts[0] ?? '')))->not->toBe('')
        ->and(trim((string) ($copiedTexts[1] ?? '')))->not->toBe('');

    scriptClick($page, '[data-quran-open-history]');
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-history-modal"))',
        true,
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const modal = document.querySelector('#quran-reader-history-modal');
  const modalWindow = modal?.closest?.('.fi-modal-window');

  if (!(modalWindow instanceof HTMLElement)) {
    return false;
  }

  return !modalWindow.className.includes('slide-over');
})()
JS,
        true,
        6_000,
    );

    safeClick($page, '.fi-modal-window .fi-modal-close-btn');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);

    $page->assertNoJavaScriptErrors();
});

it('adds extra spacing only under the al-fatiha surah header', function () {
    $page = visit('/');

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => document.querySelectorAll('.quran-page-lines .quran-surah-header-line').length > 0)()
JS,
        true,
        5_000,
    );

    $firstPageHeaderState = $page->script(<<<'JS'
(() => {
  const headers = Array.from(document.querySelectorAll('.quran-page-lines .quran-surah-header-line'));
  const fatihaHeaders = headers.filter((header) =>
    header.classList.contains('quran-surah-header-line--fatiha')
  );
  const firstFatihaHeader = fatihaHeaders[0] ?? null;

  return {
    headerCount: headers.length,
    fatihaHeaderCount: fatihaHeaders.length,
    fatihaMarginBottom: firstFatihaHeader
      ? Number.parseFloat(window.getComputedStyle(firstFatihaHeader).marginBottom || '0')
      : 0,
  };
})()
JS);

    expect($firstPageHeaderState)->toBeArray()
        ->and((int) ($firstPageHeaderState['headerCount'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($firstPageHeaderState['fatihaHeaderCount'] ?? 0))->toBe(1)
        ->and((float) ($firstPageHeaderState['fatihaMarginBottom'] ?? 0))->toBeGreaterThan(0);

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(2, 'test-fatiha-spacing');"),
    );

    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), 2, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => document.querySelectorAll('.quran-page-lines .quran-surah-header-line').length > 0)()
JS,
        true,
        5_000,
    );

    $secondPageHeaderState = $page->script(<<<'JS'
(() => {
  const headers = Array.from(document.querySelectorAll('.quran-page-lines .quran-surah-header-line'));
  const firstHeader = headers[0] ?? null;

  return {
    headerCount: headers.length,
    fatihaHeaderCount: headers.filter((header) =>
      header.classList.contains('quran-surah-header-line--fatiha')
    ).length,
    firstHeaderMarginBottom: firstHeader
      ? Number.parseFloat(window.getComputedStyle(firstHeader).marginBottom || '0')
      : 0,
  };
})()
JS);

    expect($secondPageHeaderState)->toBeArray()
        ->and((int) ($secondPageHeaderState['headerCount'] ?? 0))->toBeGreaterThan(0)
        ->and((int) ($secondPageHeaderState['fatihaHeaderCount'] ?? 0))->toBe(0)
        ->and((float) ($secondPageHeaderState['firstHeaderMarginBottom'] ?? 0))
        ->toBeLessThan((float) ($firstPageHeaderState['fatihaMarginBottom'] ?? 0));
});
