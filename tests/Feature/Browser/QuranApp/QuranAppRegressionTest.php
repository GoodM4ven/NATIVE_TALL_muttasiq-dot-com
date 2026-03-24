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
        <<<'JS'
(() => {
  const panel = document.querySelector('.quran-reader-panel');
  if (!panel) {
    return false;
  }

  return panel.getBoundingClientRect().width >= 640;
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

  return modal.getBoundingClientRect().width <= 200;
})()
JS,
        true,
        5_000,
    );
    safeClick($page, '.fi-modal-window .fi-modal-close-btn');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), true, 400);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 3_000);
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
