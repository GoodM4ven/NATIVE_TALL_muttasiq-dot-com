<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;

it('navigates to quran gate, persists it across refresh, and handles native back on mobile', function () {
    $desktopPage = visit('/', ['waitUntil' => 'domcontentloaded']);

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

    $mobilePage = visit('/', ['waitUntil' => 'domcontentloaded']);

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
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
localStorage.removeItem('quran-reader-wird-progress-v1');
data.wirdModeActive = false;
data.wirdBrowseStep = null;
data.wirdState = null;
data.wirdDailyRecord = null;
void data.ensureWirdDailyRecord({ forceRebuild: true });

return true;
JS,
        ),
    );
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

  return panel.getBoundingClientRect().width <= 1300;
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

it('re-focuses and scrolls the selected surah tile when reopening search modal', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);

    $targetSurahSelection = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const currentPage = Number(data.pageNumber ?? 1);
  const directory = Array.isArray(data.search?.surahDirectory) ? data.search.surahDirectory : [];
  const preferredBoundarySurahNumbers = [45, 55, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22];

  const preferredEntry = preferredBoundarySurahNumbers
    .map((surahNumber) =>
      directory.find(
        (item) =>
          Number(item?.surah_number ?? 0) === surahNumber
          && Number(item?.page_number ?? 0) > currentPage,
      ),
    )
    .find((entry) => entry);

  const fallbackEntry =
    preferredEntry
    ?? directory.find((item) => Number(item?.page_number ?? 0) > currentPage && Number(item?.surah_number ?? 0) > 1)
    ?? directory.find((item) => Number(item?.surah_number ?? 0) > 1)
    ?? null;

  return {
    surahNumber: Number(fallbackEntry?.surah_number ?? 0),
    pageNumber: Number(fallbackEntry?.page_number ?? 0),
  };
})()
JS,
        ),
    );

    expect($targetSurahSelection)->toBeArray();
    expect((int) ($targetSurahSelection['surahNumber'] ?? 0))->toBeGreaterThan(0);
    expect((int) ($targetSurahSelection['pageNumber'] ?? 0))->toBeGreaterThan(0);

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
                ['surahNumber' => (int) ($targetSurahSelection['surahNumber'] ?? 0)],
            ),
        ),
    );

    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.pageNumber'),
        (int) ($targetSurahSelection['pageNumber'] ?? 0),
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.search?.activeSurahNumber ?? 0)'),
        (int) ($targetSurahSelection['surahNumber'] ?? 0),
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.currentSurahNumber())'),
        (int) ($targetSurahSelection['surahNumber'] ?? 0),
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.surahTriggerSurahNumber ?? 0)'),
        (int) ($targetSurahSelection['surahNumber'] ?? 0),
        6_000,
    );

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.search?.activeSurahNumber ?? 0)'),
        (int) ($targetSurahSelection['surahNumber'] ?? 0),
        6_000,
    );

    $surahTileFocusState = $page->script(
        <<<'JS'
(() => {
  const modalRoots = Array.from(document.querySelectorAll('#quran-reader-search-modal'))
    .filter((node) => node instanceof HTMLElement);
  const activeModalRoot = modalRoots.find((modalRoot) =>
    modalRoot.closest('.fi-modal')?.classList.contains('fi-modal-open')
  ) ?? modalRoots[0] ?? null;
  const activeModal = activeModalRoot instanceof HTMLElement
    ? activeModalRoot.closest('.fi-modal')
    : null;

  if (!(activeModal instanceof HTMLElement)) {
    return {
      surahNumber: 0,
      scrollTop: 0,
      isTileVisible: false,
    };
  }

  const grid = activeModal.querySelector('.quran-surah-grid');
  const activeTile = grid instanceof HTMLElement ? grid.querySelector('.quran-surah-tile--active') : null;

  if (!(grid instanceof HTMLElement) || !(activeTile instanceof HTMLElement)) {
    return {
      surahNumber: 0,
      scrollTop: 0,
      isTileVisible: false,
    };
  }

  return {
    surahNumber: Number(activeTile.getAttribute('data-surah-number') ?? 0),
    scrollTop: Math.max(0, Math.trunc(Number(grid.scrollTop ?? 0))),
    gridClientHeight: Math.max(0, Math.trunc(Number(grid.clientHeight ?? 0))),
    tileHeight: Math.max(0, Math.trunc(activeTile.getBoundingClientRect().height ?? 0)),
    tileOffsetTop: Math.max(0, Math.trunc(Number(activeTile.offsetTop ?? 0))),
    isTileVisible: (() => {
      const gridRect = grid.getBoundingClientRect();
      const tileRect = activeTile.getBoundingClientRect();

      return tileRect.top >= gridRect.top - 1 && tileRect.bottom <= gridRect.bottom + 1;
    })(),
  };
})()
JS,
    );

    expect($surahTileFocusState)->toBeArray();
    $targetSurahNumber = (int) ($targetSurahSelection['surahNumber'] ?? 0);
    $focusedSurahNumber = (int) ($surahTileFocusState['surahNumber'] ?? 0);
    $surahGridScrollTop = (int) ($surahTileFocusState['scrollTop'] ?? 0);
    expect($focusedSurahNumber)->toBe($targetSurahNumber);
    expect($surahGridScrollTop)->toBeGreaterThanOrEqual(0);
});

it('restores the saved last page across quran modes and refresh', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
localStorage.removeItem('quran-reader-wird-progress-v1');
data.wirdModeActive = false;
data.wirdBrowseStep = null;
data.wirdState = null;
data.wirdDailyRecord = null;
void data.ensureWirdDailyRecord({ forceRebuild: true });

return true;
JS,
        ),
    );

    $targetPage = 14;
    $page->script(
        quranReaderCommandScript(
            js_template(
                "data.dispatchPageNavigationRequest({{page}}, 'test-last-page-restore');",
                ['page' => $targetPage],
            ),
        ),
    );

    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), $targetPage, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            <<<'JS'
(() => {
  const firstAyahLine = (Array.isArray(data.mushafLines) ? data.mushafLines : [])
    .find((line) => String(line?.line_type ?? '') === 'ayah');

  if (!firstAyahLine) {
    return false;
  }

  const firstAyahNumber = Number(
    firstAyahLine?.words?.[0]?.ayah_number ?? firstAyahLine?.segments?.[0]?.ayah_number ?? 0,
  );

  return firstAyahNumber > 1;
})()
JS,
        ),
        true,
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        "Number(JSON.parse(localStorage.getItem('quran-reader-last-page-v1') ?? 'null') ?? 0)",
        $targetPage,
        5_000,
    );

    hashAction($page, '#quran-app-gate', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-gate');
    waitForQuranGateVisible($page);

    hashAction($page, '#quran-app-hifth', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-hifth');
    waitForQuranReaderVisible($page);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), $targetPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    hashAction($page, '#quran-app-gate', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-gate');
    waitForQuranGateVisible($page);

    hashAction($page, '#quran-app-tadabbur', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tadabbur');
    waitForQuranReaderVisible($page);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), $targetPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->refresh();

    waitForAlpineReady($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), $targetPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            <<<'JS'
(() => {
  const firstAyahLine = (Array.isArray(data.mushafLines) ? data.mushafLines : [])
    .find((line) => String(line?.line_type ?? '') === 'ayah');

  if (!firstAyahLine) {
    return false;
  }

  const firstAyahNumber = Number(
    firstAyahLine?.words?.[0]?.ayah_number ?? firstAyahLine?.segments?.[0]?.ayah_number ?? 0,
  );

  return firstAyahNumber > 1;
})()
JS,
        ),
        true,
        6_000,
    );
});

it('persists local reader state for last page, navigation history, and bookmarks', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderVisibleAfterModalClose = function (int $timeoutMs = 8_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript(
                "Boolean(typeof data.isCurrentPageVisiblyReady === 'function' && data.isCurrentPageVisiblyReady())",
            ),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const surface = document.querySelector('.quran-page-surface');
  const frame = document.querySelector('[x-ref="pageFrame"]');
  const lines = document.querySelector('.quran-page-lines');

  if (
    !(surface instanceof HTMLElement) ||
    !(frame instanceof HTMLElement) ||
    !(lines instanceof HTMLElement)
  ) {
    return false;
  }

  const surfaceRect = surface.getBoundingClientRect();
  const frameRect = frame.getBoundingClientRect();
  const linesRect = lines.getBoundingClientRect();
  const styles = window.getComputedStyle(lines);
  const opacity = Number.parseFloat(styles.opacity || '0');

  if (surfaceRect.width <= 0 || frameRect.width <= 0 || linesRect.width <= 0) {
    return false;
  }

  return (
    styles.visibility !== 'hidden' &&
    opacity > 0.5 &&
    (frameRect.width / surfaceRect.width) >= 0.58 &&
    (linesRect.width / frameRect.width) >= 0.76
  );
})()
JS,
            true,
            $timeoutMs,
        );
    };

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
localStorage.removeItem('quran-reader-wird-progress-v1');
data.wirdModeActive = false;
data.wirdBrowseStep = null;
data.wirdState = null;
data.wirdDailyRecord = null;
void data.ensureWirdDailyRecord({ forceRebuild: true });

return true;
JS,
        ),
    );

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
    $assertReaderVisibleAfterModalClose();

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
data.search.modalOpen = true;
data.search.query = 'الذين';
return data.updateSearchResults();
JS,
        ),
    );
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
    $assertReaderVisibleAfterModalClose();

    scriptClick($page, '[data-quran-open-history]');
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-history-modal"))',
        true,
        5_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.navigationHistory.length >= 2'), true, 6_000);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
const entry = Array.isArray(data.navigationHistory)
  ? (data.navigationHistory.find((item) => !Array.isArray(item?.tags) || item.tags.length < 1) ?? data.navigationHistory[0] ?? null)
  : null;

if (!entry?.id || typeof data.updateHistoryEntryTags !== 'function') {
  return false;
}

data.updateHistoryEntryTags(entry.id, ['مميز']);

return true;
JS,
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            "data.navigationHistory.some((entry) => Array.isArray(entry?.tags) && entry.tags.includes('مميز'))",
        ),
        true,
        6_000,
    );

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
if (typeof data.clearNavigationHistory !== 'function') {
  return false;
}

data.clearNavigationHistory();

return true;
JS,
        ),
    );
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
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Number(data.navigationHistory.length ?? 0) >= 1',
        ),
        true,
        6_000,
    );

    $page->script(quranReaderCommandScript('data.openBookmarksManager();'));

    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-bookmarks-modal"))',
        true,
        6_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const modal = document.querySelector('#quran-reader-bookmarks-modal');
  if (!modal) {
    return false;
  }

  if (modal.querySelectorAll('.fi-ta-row').length > 0) {
    return true;
  }

  return !String(modal.textContent ?? '').includes('لا توجد علامات محفوظة.');
})()
JS,
        true,
        6_000,
    );

    $bookmarkIdForReplace = (string) $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const currentPage = Number(data.pageNumber ?? 0);
  const entries = Array.isArray(data.bookmarks) ? data.bookmarks : [];
  const entry = entries.find((bookmark) => Number(bookmark?.page_number ?? 0) !== currentPage)
    ?? entries[0]
    ?? null;

  return String(entry?.id ?? '');
})()
JS,
        ),
    );

    expect($bookmarkIdForReplace)->not->toBe('');

    $page->script(
        quranReaderCommandScript(
            js_template(
                <<<'JS'
const bookmarkId = String({{bookmarkId}});
if (!bookmarkId || typeof data.replaceBookmarkPage !== 'function') {
  return false;
}

data.replaceBookmarkPage(bookmarkId);

return true;
JS,
                ['bookmarkId' => $bookmarkIdForReplace],
            ),
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            js_template(
                <<<'JS'
(() => {
  const bookmarkId = String({{bookmarkId}});
  const bookmark = (Array.isArray(data.bookmarks) ? data.bookmarks : [])
    .find((entry) => String(entry?.id ?? '') === bookmarkId);

  return Number(bookmark?.page_number ?? 0) === Number(data.pageNumber ?? 0);
})()
JS,
                ['bookmarkId' => $bookmarkIdForReplace],
            ),
        ),
        true,
        6_000,
    );

    $page->script(
        quranReaderCommandScript(
            js_template(
                <<<'JS'
const bookmarkId = String({{bookmarkId}});
if (!bookmarkId || typeof data.updateBookmarkNote !== 'function') {
  return false;
}

data.updateBookmarkNote(bookmarkId, 'علامة اختبارية');

return true;
JS,
                ['bookmarkId' => $bookmarkIdForReplace],
            ),
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            js_template(
                <<<'JS'
(() => {
  const bookmarkId = String({{bookmarkId}});
  const bookmark = (Array.isArray(data.bookmarks) ? data.bookmarks : [])
    .find((entry) => String(entry?.id ?? '') === bookmarkId);

  return String(bookmark?.note ?? '').includes('اختبارية');
})()
JS,
                ['bookmarkId' => $bookmarkIdForReplace],
            ),
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

    $page->script(
        quranReaderCommandScript(
            js_template(
                <<<'JS'
const bookmarkId = String({{bookmarkId}});
if (!bookmarkId || typeof data.handleBookmarksManagerGoEvent !== 'function') {
  return false;
}

void data.handleBookmarksManagerGoEvent({ id: bookmarkId });

return true;
JS,
                ['bookmarkId' => $bookmarkIdForReplace],
            ),
        ),
    );
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.pageNumber'),
        (int) $bookmarkReplacementTarget,
        6_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $bookmarksBeforeRemoval = $page->script(quranReaderDataScript('Number(data.bookmarks.length ?? 0)'));
    $bookmarkPageForRemoval = $page->script(
        quranReaderDataScript(
            js_template(
                <<<'JS'
(() => {
  const bookmarkId = String({{bookmarkId}});
  const bookmark = (Array.isArray(data.bookmarks) ? data.bookmarks : [])
    .find((entry) => String(entry?.id ?? '') === bookmarkId);

  return Number(bookmark?.page_number ?? 0);
})()
JS,
                ['bookmarkId' => $bookmarkIdForReplace],
            ),
        ),
    );

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
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

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
    $didDragThenSingleClick = $page->script(<<<'JS'
(() => {
  const wordButtons = Array.from(document.querySelectorAll('.quran-page-lines .quran-word-button'))
    .filter((button) => button instanceof HTMLButtonElement && !button.disabled);

  if (wordButtons.length < 4) {
    return false;
  }

  const pointFor = (button) => {
    const rect = button.getBoundingClientRect();

    return {
      x: rect.left + (rect.width / 2),
      y: rect.top + (rect.height / 2),
    };
  };

  const startWord = wordButtons[0];
  const middleWord = wordButtons[1];
  const endWord = wordButtons[2];
  const postDragWord = wordButtons[3];
  const startPoint = pointFor(startWord);
  const middlePoint = pointFor(middleWord);
  const endPoint = pointFor(endWord);
  const postDragPoint = pointFor(postDragWord);

  startWord.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 914,
      pointerType: 'mouse',
      clientX: startPoint.x,
      clientY: startPoint.y,
    }),
  );

  middleWord.dispatchEvent(
    new PointerEvent('pointermove', {
      bubbles: true,
      pointerId: 914,
      pointerType: 'mouse',
      clientX: middlePoint.x,
      clientY: middlePoint.y,
    }),
  );

  endWord.dispatchEvent(
    new PointerEvent('pointermove', {
      bubbles: true,
      pointerId: 914,
      pointerType: 'mouse',
      clientX: endPoint.x,
      clientY: endPoint.y,
    }),
  );

  endWord.dispatchEvent(
    new PointerEvent('pointerup', {
      bubbles: true,
      pointerId: 914,
      pointerType: 'mouse',
      clientX: endPoint.x,
      clientY: endPoint.y,
    }),
  );

  window.setTimeout(() => {
    postDragWord.dispatchEvent(
      new PointerEvent('pointerdown', {
        bubbles: true,
        pointerId: 915,
        pointerType: 'mouse',
        clientX: postDragPoint.x,
        clientY: postDragPoint.y,
      }),
    );

    postDragWord.dispatchEvent(
      new PointerEvent('pointerup', {
        bubbles: true,
        pointerId: 915,
        pointerType: 'mouse',
        clientX: postDragPoint.x,
        clientY: postDragPoint.y,
      }),
    );

    postDragWord.dispatchEvent(
      new MouseEvent('click', {
        bubbles: true,
        clientX: postDragPoint.x,
        clientY: postDragPoint.y,
      }),
    );
  }, 260);

  return true;
})()
JS);

    expect($didDragThenSingleClick)->toBeTrue();

    waitForScriptWithTimeout($page, 'Number(window.__copiedTexts?.length ?? 0) >= 3', true, 6_000);
    waitForScriptWithTimeout($page, 'Number(window.__copiedTexts?.length ?? 0) >= 4', true, 6_000);

    $didOutOfOrderAyahDragCopy = $page->script(<<<'JS'
(() => {
  const ayahSevenWord = document.querySelector('.quran-page-lines .quran-word-button[data-quran-ayah-number="7"]');
  const ayahSixWord = document.querySelector('.quran-page-lines .quran-word-button[data-quran-ayah-number="6"]');

  if (!(ayahSevenWord instanceof HTMLButtonElement) || !(ayahSixWord instanceof HTMLButtonElement)) {
    return false;
  }

  const pointFor = (button) => {
    const rect = button.getBoundingClientRect();

    return {
      x: rect.left + (rect.width / 2),
      y: rect.top + (rect.height / 2),
    };
  };

  const ayahSevenPoint = pointFor(ayahSevenWord);
  const ayahSixPoint = pointFor(ayahSixWord);

  ayahSevenWord.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 916,
      pointerType: 'mouse',
      clientX: ayahSevenPoint.x,
      clientY: ayahSevenPoint.y,
    }),
  );

  ayahSixWord.dispatchEvent(
    new PointerEvent('pointermove', {
      bubbles: true,
      pointerId: 916,
      pointerType: 'mouse',
      clientX: ayahSixPoint.x,
      clientY: ayahSixPoint.y,
    }),
  );

  ayahSixWord.dispatchEvent(
    new PointerEvent('pointerup', {
      bubbles: true,
      pointerId: 916,
      pointerType: 'mouse',
      clientX: ayahSixPoint.x,
      clientY: ayahSixPoint.y,
    }),
  );

  return true;
})()
JS);

    expect($didOutOfOrderAyahDragCopy)->toBeTrue();

    waitForScriptWithTimeout($page, 'Number(window.__copiedTexts?.length ?? 0) >= 5', true, 6_000);

    $copiedTexts = $page->script('window.__copiedTexts');
    $outOfOrderCopiedAyahText = trim((string) ($copiedTexts[4] ?? ''));

    expect($copiedTexts)->toBeArray()
        ->and(trim((string) ($copiedTexts[0] ?? '')))->not->toBe('')
        ->and(trim((string) ($copiedTexts[1] ?? '')))->not->toBe('')
        ->and(trim((string) ($copiedTexts[2] ?? '')))->not->toBe('')
        ->and(trim((string) ($copiedTexts[3] ?? '')))->not->toBe('')
        ->and($outOfOrderCopiedAyahText)->not->toBe('')
        ->and($outOfOrderCopiedAyahText)->not->toContain('۝');

    $crossSurahPageNumber = (int) DB::table('quran_mushaf_lines')
        ->select('page_number')
        ->where('line_type', 'ayah')
        ->whereNotNull('surah_number')
        ->groupBy('page_number')
        ->havingRaw('COUNT(DISTINCT surah_number) > 1')
        ->orderBy('page_number')
        ->value('page_number');

    if ($crossSurahPageNumber > 0) {
        $page->script(
            quranReaderCommandScript(
                "data.dispatchPageNavigationRequest({$crossSurahPageNumber}, 'test-cross-surah-multi-copy');",
            ),
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.pageNumber'),
            $crossSurahPageNumber,
            6_000,
        );
        waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

        $crossSurahSelection = $page->script(<<<'JS'
(() => {
  const wordButtons = Array.from(document.querySelectorAll('.quran-page-lines .quran-word-button'))
    .filter((button) => button instanceof HTMLButtonElement && !button.disabled)
    .map((button) => ({
      button,
      surahNumber: Number(button.getAttribute('data-quran-surah-number') ?? 0),
      ayahNumber: Number(button.getAttribute('data-quran-ayah-number') ?? 0),
    }))
    .filter((entry) => entry.surahNumber > 0 && entry.ayahNumber > 0);

  if (wordButtons.length < 2) {
    return null;
  }

  let boundaryStart = null;
  let boundaryEnd = null;

  for (let index = 0; index < wordButtons.length - 1; index += 1) {
    const currentWord = wordButtons[index];
    const nextWord = wordButtons[index + 1];

    if (currentWord.surahNumber === nextWord.surahNumber) {
      continue;
    }

    boundaryStart = currentWord;
    boundaryEnd = nextWord;

    break;
  }

  if (!boundaryStart || !boundaryEnd) {
    return null;
  }

  const pointFor = (button) => {
    const rect = button.getBoundingClientRect();

    return {
      x: rect.left + (rect.width / 2),
      y: rect.top + (rect.height / 2),
    };
  };

  const startPoint = pointFor(boundaryStart.button);
  const endPoint = pointFor(boundaryEnd.button);

  boundaryStart.button.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 917,
      pointerType: 'mouse',
      clientX: startPoint.x,
      clientY: startPoint.y,
    }),
  );

  boundaryEnd.button.dispatchEvent(
    new PointerEvent('pointermove', {
      bubbles: true,
      pointerId: 917,
      pointerType: 'mouse',
      clientX: endPoint.x,
      clientY: endPoint.y,
    }),
  );

  boundaryEnd.button.dispatchEvent(
    new PointerEvent('pointerup', {
      bubbles: true,
      pointerId: 917,
      pointerType: 'mouse',
      clientX: endPoint.x,
      clientY: endPoint.y,
    }),
  );

  return {
    firstSurahNumber: boundaryStart.surahNumber,
    secondSurahNumber: boundaryEnd.surahNumber,
    firstAyahNumber: boundaryStart.ayahNumber,
    secondAyahNumber: boundaryEnd.ayahNumber,
  };
})()
JS);

        expect($crossSurahSelection)->toBeArray()
            ->and((int) ($crossSurahSelection['firstSurahNumber'] ?? 0))->toBeGreaterThan(0)
            ->and((int) ($crossSurahSelection['secondSurahNumber'] ?? 0))->toBeGreaterThan(0)
            ->and((int) ($crossSurahSelection['firstAyahNumber'] ?? 0))->toBeGreaterThan(0)
            ->and((int) ($crossSurahSelection['secondAyahNumber'] ?? 0))->toBeGreaterThan(0);

        waitForScriptWithTimeout($page, 'Number(window.__copiedTexts?.length ?? 0) >= 6', true, 6_000);

        $copiedTexts = $page->script('window.__copiedTexts');
        $crossSurahCopiedText = trim((string) ($copiedTexts[5] ?? ''));
        $splitterMatches = [];
        preg_match_all('/\(([0-9٠-٩]+)\)/u', $crossSurahCopiedText, $splitterMatches);
        $splitterValues = collect($splitterMatches[1] ?? [])
            ->map(static fn (string $token): int => (int) strtr(
                $token,
                ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'],
            ))
            ->all();
        $surahAffixMatches = [];
        preg_match_all('/~\s*\[([^\]]+)\]/u', $crossSurahCopiedText, $surahAffixMatches);
        $surahAffixes = collect($surahAffixMatches[1] ?? [])
            ->map(static fn (string $token): string => trim($token))
            ->filter(static fn (string $token): bool => $token !== '')
            ->values()
            ->all();
        $expectedSurahAffixesCount = collect([
            (int) ($crossSurahSelection['firstSurahNumber'] ?? 0),
            (int) ($crossSurahSelection['secondSurahNumber'] ?? 0),
        ])
            ->filter(static fn (int $surahNumber): bool => $surahNumber > 0)
            ->unique()
            ->count();
        $expectedSplitterValues = [
            (int) ($crossSurahSelection['firstAyahNumber'] ?? 0),
            (int) ($crossSurahSelection['secondAyahNumber'] ?? 0),
        ];

        expect($crossSurahCopiedText)->not->toBe('')
            ->and($crossSurahCopiedText)->not->toContain('۝')
            ->and($crossSurahCopiedText)->not->toMatch('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u')
            ->and($splitterValues)->toBe($expectedSplitterValues)
            ->and(count($surahAffixes))->toBe($expectedSurahAffixesCount);
    }

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

it('allows swipe navigation from quran line gaps but not from quran text lines', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $startingPageNumber = (int) $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));
    expect($startingPageNumber)->toBeGreaterThan(0);

    $didSwipeFromTextLine = $page->script(<<<'JS'
(() => {
  const wordButton = document.querySelector('.quran-page-lines .quran-word-button');

  if (!(wordButton instanceof HTMLButtonElement)) {
    return false;
  }

  const rect = wordButton.getBoundingClientRect();
  const startPoint = {
    x: rect.left + (rect.width / 2),
    y: rect.top + (rect.height / 2),
  };
  const endPoint = {
    x: startPoint.x + 170,
    y: startPoint.y,
  };

  wordButton.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 918,
      pointerType: 'mouse',
      clientX: startPoint.x,
      clientY: startPoint.y,
    }),
  );

  wordButton.dispatchEvent(
    new PointerEvent('pointerup', {
      bubbles: true,
      pointerId: 918,
      pointerType: 'mouse',
      clientX: endPoint.x,
      clientY: endPoint.y,
    }),
  );

  return true;
})()
JS);

    expect($didSwipeFromTextLine)->toBeTrue();
    waitForScriptWithTimeout($page, quranReaderDataScript('data.pageNumber'), $startingPageNumber, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $didSwipeFromLineGap = $page->script(<<<'JS'
(() => {
  const linesContainer = document.querySelector('.quran-page-lines');
  const lineEntries = Array.from(document.querySelectorAll('.quran-page-lines [data-quran-line]'))
    .filter((entry) => entry instanceof HTMLElement);

  if (!(linesContainer instanceof HTMLElement) || lineEntries.length < 2) {
    return false;
  }

  let gapPoint = null;

  for (let index = 0; index < lineEntries.length - 1; index += 1) {
    const currentRect = lineEntries[index].getBoundingClientRect();
    const nextRect = lineEntries[index + 1].getBoundingClientRect();
    const gapHeight = nextRect.top - currentRect.bottom;

    if (gapHeight > 4) {
      gapPoint = {
        x: currentRect.left + (currentRect.width / 2),
        y: currentRect.bottom + (gapHeight / 2),
      };

      break;
    }
  }

  if (!gapPoint) {
    const containerRect = linesContainer.getBoundingClientRect();
    gapPoint = {
      x: containerRect.left + (containerRect.width / 2),
      y: containerRect.top + 4,
    };
  }

  const endPoint = {
    x: gapPoint.x + 170,
    y: gapPoint.y,
  };

  linesContainer.dispatchEvent(
    new PointerEvent('pointerdown', {
      bubbles: true,
      pointerId: 919,
      pointerType: 'mouse',
      clientX: gapPoint.x,
      clientY: gapPoint.y,
    }),
  );

  linesContainer.dispatchEvent(
    new PointerEvent('pointerup', {
      bubbles: true,
      pointerId: 919,
      pointerType: 'mouse',
      clientX: endPoint.x,
      clientY: endPoint.y,
    }),
  );

  return true;
})()
JS);

    expect($didSwipeFromLineGap)->toBeTrue();
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            js_template(
                <<<'JS'
(() => {
  const startPage = Number({{startPage}});
  const currentPage = Number(data.pageNumber ?? 0);

  if (currentPage < 1 || startPage < 1) {
    return false;
  }

  return currentPage !== startPage && Math.abs(currentPage - startPage) === 1;
})()
JS,
                ['startPage' => $startingPageNumber],
            ),
        ),
        true,
        6_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    $page->assertNoJavaScriptErrors();
});

it('keeps quran text visible after rapid double swipe navigation', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 8_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data._pendingNavigationRequest === null && !data.isLoadingPage'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return lineTexts.length > 0;
})()
JS,
            true,
            $timeoutMs,
        );
    };

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    $startingPageNumber = (int) $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));
    $targetPageNumber = (int) $page->script(
        quranReaderDataScript(
            'Math.min(Math.max(1, Number(data.maxPage ?? 1)), Number(data.pageNumber ?? 1) + 2)',
        ),
    );

    expect($startingPageNumber)->toBeGreaterThan(0);
    expect($targetPageNumber)->toBeGreaterThanOrEqual($startingPageNumber);

    $didDoubleSwipeFromLineGap = $page->script(<<<'JS'
(() => {
  const linesContainer = document.querySelector('.quran-page-lines');
  const lineEntries = Array.from(document.querySelectorAll('.quran-page-lines [data-quran-line]'))
    .filter((entry) => entry instanceof HTMLElement);

  if (!(linesContainer instanceof HTMLElement) || lineEntries.length < 2) {
    return false;
  }

  let gapPoint = null;

  for (let index = 0; index < lineEntries.length - 1; index += 1) {
    const currentRect = lineEntries[index].getBoundingClientRect();
    const nextRect = lineEntries[index + 1].getBoundingClientRect();
    const gapHeight = nextRect.top - currentRect.bottom;

    if (gapHeight > 4) {
      gapPoint = {
        x: currentRect.left + (currentRect.width / 2),
        y: currentRect.bottom + (gapHeight / 2),
      };

      break;
    }
  }

  if (!gapPoint) {
    const containerRect = linesContainer.getBoundingClientRect();
    gapPoint = {
      x: containerRect.left + (containerRect.width / 2),
      y: containerRect.top + 4,
    };
  }

  const dispatchSwipe = (pointerId, startPoint) => {
    const endPoint = {
      x: startPoint.x + 170,
      y: startPoint.y,
    };

    linesContainer.dispatchEvent(
      new PointerEvent('pointerdown', {
        bubbles: true,
        pointerId,
        pointerType: 'mouse',
        clientX: startPoint.x,
        clientY: startPoint.y,
      }),
    );

    window.dispatchEvent(
      new PointerEvent('pointerup', {
        bubbles: true,
        pointerId,
        pointerType: 'mouse',
        clientX: endPoint.x,
        clientY: endPoint.y,
      }),
    );
  };

  dispatchSwipe(1021, gapPoint);
  dispatchSwipe(1022, gapPoint);

  return true;
})()
JS);

    expect($didDoubleSwipeFromLineGap)->toBeTrue();

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $targetPageNumber,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data._pendingNavigationRequest === null && !data._navigationRevealLocked'),
        true,
        8_000,
    );
    $assertReaderRenderable(10_000);
    $page->assertNoJavaScriptErrors();
});

it('applies surah-affix rules correctly in word-target drag mode', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

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
  window.__wordModeCopiedTexts = [];
  const clipboard = {
    writeText(value) {
      window.__wordModeCopiedTexts.push(String(value ?? ''));
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

    $page->script(quranReaderCommandScript(<<<'JS'
data.doesTargetWordsByDefault = true;
data.doesAppendSurahAffixOnMultiCopy = true;
data.doesAppendSurahAffixAlwaysOnCopy = false;
JS));

    $didDragWithinSingleAyah = $page->script(<<<'JS'
(() => {
  const words = Array.from(document.querySelectorAll('.quran-page-lines .quran-word-button'))
    .filter((button) => button instanceof HTMLButtonElement && !button.disabled)
    .map((button) => ({
      button,
      ayahIndex: Number(button.getAttribute('data-quran-ayah-index') ?? 0),
    }));

  const firstPair = words.find((entry, index) =>
    entry.ayahIndex > 0 && words[index + 1] && words[index + 1].ayahIndex === entry.ayahIndex
  );

  if (!firstPair) {
    return false;
  }

  const secondWord = words[words.indexOf(firstPair) + 1]?.button ?? null;

  if (!(secondWord instanceof HTMLButtonElement)) {
    return false;
  }

  const pointFor = (button) => {
    const rect = button.getBoundingClientRect();

    return {
      x: rect.left + (rect.width / 2),
      y: rect.top + (rect.height / 2),
    };
  };

  const startPoint = pointFor(firstPair.button);
  const endPoint = pointFor(secondWord);

  firstPair.button.dispatchEvent(new PointerEvent('pointerdown', {
    bubbles: true,
    pointerId: 920,
    pointerType: 'mouse',
    clientX: startPoint.x,
    clientY: startPoint.y,
  }));

  secondWord.dispatchEvent(new PointerEvent('pointermove', {
    bubbles: true,
    pointerId: 920,
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  secondWord.dispatchEvent(new PointerEvent('pointerup', {
    bubbles: true,
    pointerId: 920,
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  return true;
})()
JS);

    expect($didDragWithinSingleAyah)->toBeTrue();
    waitForScriptWithTimeout($page, 'Number(window.__wordModeCopiedTexts?.length ?? 0) >= 1', true, 6_000);

    $singleAyahWordModeCopy = trim((string) ($page->script('window.__wordModeCopiedTexts[0]') ?? ''));

    expect($singleAyahWordModeCopy)->not->toBe('')
        ->and($singleAyahWordModeCopy)->not->toContain('~ [');

    $didDragAcrossAyahs = $page->script(<<<'JS'
(() => {
  const words = Array.from(document.querySelectorAll('.quran-page-lines .quran-word-button'))
    .filter((button) => button instanceof HTMLButtonElement && !button.disabled)
    .map((button) => ({
      button,
      ayahIndex: Number(button.getAttribute('data-quran-ayah-index') ?? 0),
    }))
    .filter((entry) => entry.ayahIndex > 0);

  let startWord = null;
  let endWord = null;

  for (let index = 0; index < words.length - 1; index += 1) {
    if (words[index].ayahIndex === words[index + 1].ayahIndex) {
      continue;
    }

    startWord = words[index].button;
    endWord = words[index + 1].button;
    break;
  }

  if (!(startWord instanceof HTMLButtonElement) || !(endWord instanceof HTMLButtonElement)) {
    return false;
  }

  const pointFor = (button) => {
    const rect = button.getBoundingClientRect();

    return {
      x: rect.left + (rect.width / 2),
      y: rect.top + (rect.height / 2),
    };
  };

  const startPoint = pointFor(startWord);
  const endPoint = pointFor(endWord);

  startWord.dispatchEvent(new PointerEvent('pointerdown', {
    bubbles: true,
    pointerId: 921,
    pointerType: 'mouse',
    clientX: startPoint.x,
    clientY: startPoint.y,
  }));

  endWord.dispatchEvent(new PointerEvent('pointermove', {
    bubbles: true,
    pointerId: 921,
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  endWord.dispatchEvent(new PointerEvent('pointerup', {
    bubbles: true,
    pointerId: 921,
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  return true;
})()
JS);

    expect($didDragAcrossAyahs)->toBeTrue();
    waitForScriptWithTimeout($page, 'Number(window.__wordModeCopiedTexts?.length ?? 0) >= 2', true, 6_000);

    $multiAyahWordModeCopy = trim((string) ($page->script('window.__wordModeCopiedTexts[1]') ?? ''));

    expect($multiAyahWordModeCopy)->not->toBe('')
        ->and($multiAyahWordModeCopy)->toContain('~ [');

    $page->script(quranReaderCommandScript('data.doesAppendSurahAffixAlwaysOnCopy = true;'));

    $didDragSingleAyahWithAlwaysSetting = $page->script(<<<'JS'
(() => {
  const words = Array.from(document.querySelectorAll('.quran-page-lines .quran-word-button'))
    .filter((button) => button instanceof HTMLButtonElement && !button.disabled)
    .map((button) => ({
      button,
      ayahIndex: Number(button.getAttribute('data-quran-ayah-index') ?? 0),
    }))
    .filter((entry) => entry.ayahIndex > 0);

  const firstPair = words.find((entry, index) =>
    words[index + 1] && words[index + 1].ayahIndex === entry.ayahIndex
  );

  if (!firstPair) {
    return false;
  }

  const secondWord = words[words.indexOf(firstPair) + 1]?.button ?? null;

  if (!(secondWord instanceof HTMLButtonElement)) {
    return false;
  }

  const pointFor = (button) => {
    const rect = button.getBoundingClientRect();

    return {
      x: rect.left + (rect.width / 2),
      y: rect.top + (rect.height / 2),
    };
  };

  const startPoint = pointFor(firstPair.button);
  const endPoint = pointFor(secondWord);

  firstPair.button.dispatchEvent(new PointerEvent('pointerdown', {
    bubbles: true,
    pointerId: 922,
    pointerType: 'mouse',
    clientX: startPoint.x,
    clientY: startPoint.y,
  }));

  secondWord.dispatchEvent(new PointerEvent('pointermove', {
    bubbles: true,
    pointerId: 922,
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  secondWord.dispatchEvent(new PointerEvent('pointerup', {
    bubbles: true,
    pointerId: 922,
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  return true;
})()
JS);

    expect($didDragSingleAyahWithAlwaysSetting)->toBeTrue();
    waitForScriptWithTimeout($page, 'Number(window.__wordModeCopiedTexts?.length ?? 0) >= 3', true, 6_000);

    $alwaysAffixCopy = trim((string) ($page->script('window.__wordModeCopiedTexts[2]') ?? ''));

    expect($alwaysAffixCopy)->not->toBe('')
        ->and($alwaysAffixCopy)->toContain('~ [');

    $page->assertNoJavaScriptErrors();
});

it('adds extra spacing only under the al-fatiha surah header', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

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

it('keeps quran text fitted and visible across all reader navigation paths', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 8_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data._pendingNavigationRequest === null && !data.isLoadingPage'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return lineTexts.length > 0;
})()
JS,
            true,
            $timeoutMs,
        );
    };

    $currentPageNumber = fn (): int => (int) $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    $initialPage = $currentPageNumber();
    expect($initialPage)->toBeGreaterThan(0);

    $sourceProfiles = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => ({
  keyboard: String(data.navigationSourceProfile('keyboard') ?? ''),
  swipe: String(data.navigationSourceProfile('swipe') ?? ''),
  chevron: String(data.navigationSourceProfile('chevron') ?? ''),
}))()
JS,
        ),
    );
    expect($sourceProfiles)->toBeArray();
    expect((string) ($sourceProfiles['keyboard'] ?? ''))->toBe('keyboard');
    expect((string) ($sourceProfiles['swipe'] ?? ''))->toBe('swipe');
    expect((string) ($sourceProfiles['chevron'] ?? ''))->toBe('chevron');

    safeClick($page, '.quran-bottom-strip-nav-next');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $initialPage + 1,
        6_000,
    );
    $assertReaderRenderable();

    safeClick($page, '.quran-bottom-strip-nav-prev');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $initialPage,
        6_000,
    );
    $assertReaderRenderable();

    $page->script(<<<'JS'
(() => {
  window.dispatchEvent(new KeyboardEvent('keydown', {
    bubbles: true,
    cancelable: true,
    key: 'ArrowLeft',
  }));
})()
JS);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $initialPage + 1,
        6_000,
    );
    $assertReaderRenderable();

    $page->script(<<<'JS'
(() => {
  window.dispatchEvent(new KeyboardEvent('keydown', {
    bubbles: true,
    cancelable: true,
    key: 'ArrowRight',
  }));
})()
JS);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $initialPage,
        6_000,
    );
    $assertReaderRenderable();

    $didSwipeFromGap = $page->script(
        js_template(
            <<<'JS'
(() => {
  const linesContainer = document.querySelector('.quran-page-lines');
  const lineEntries = Array.from(document.querySelectorAll('.quran-page-lines [data-quran-line]'))
    .filter((entry) => entry instanceof HTMLElement);

  if (!(linesContainer instanceof HTMLElement) || lineEntries.length < 2) {
    return false;
  }

  let gapPoint = null;

  for (let index = 0; index < lineEntries.length - 1; index += 1) {
    const currentRect = lineEntries[index].getBoundingClientRect();
    const nextRect = lineEntries[index + 1].getBoundingClientRect();
    const gapHeight = nextRect.top - currentRect.bottom;

    if (gapHeight > 4) {
      gapPoint = {
        x: currentRect.left + (currentRect.width / 2),
        y: currentRect.bottom + (gapHeight / 2),
      };
      break;
    }
  }

  if (!gapPoint) {
    return false;
  }

  const endPoint = {
    x: gapPoint.x + Number({{deltaX}}),
    y: gapPoint.y,
  };

  linesContainer.dispatchEvent(new PointerEvent('pointerdown', {
    bubbles: true,
    pointerId: Number({{pointerId}}),
    pointerType: 'mouse',
    clientX: gapPoint.x,
    clientY: gapPoint.y,
  }));

  window.dispatchEvent(new PointerEvent('pointermove', {
    bubbles: true,
    pointerId: Number({{pointerId}}),
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  return true;
})()
JS,
            [
                'deltaX' => 170,
                'pointerId' => 951,
            ],
        ),
    );
    expect($didSwipeFromGap)->toBeTrue();
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $initialPage + 1,
        6_000,
    );
    $assertReaderRenderable();

    $didSwipeBackFromGap = $page->script(
        js_template(
            <<<'JS'
(() => {
  const linesContainer = document.querySelector('.quran-page-lines');
  const lineEntries = Array.from(document.querySelectorAll('.quran-page-lines [data-quran-line]'))
    .filter((entry) => entry instanceof HTMLElement);

  if (!(linesContainer instanceof HTMLElement) || lineEntries.length < 2) {
    return false;
  }

  let gapPoint = null;

  for (let index = 0; index < lineEntries.length - 1; index += 1) {
    const currentRect = lineEntries[index].getBoundingClientRect();
    const nextRect = lineEntries[index + 1].getBoundingClientRect();
    const gapHeight = nextRect.top - currentRect.bottom;

    if (gapHeight > 4) {
      gapPoint = {
        x: currentRect.left + (currentRect.width / 2),
        y: currentRect.bottom + (gapHeight / 2),
      };
      break;
    }
  }

  if (!gapPoint) {
    return false;
  }

  const endPoint = {
    x: gapPoint.x + Number({{deltaX}}),
    y: gapPoint.y,
  };

  linesContainer.dispatchEvent(new PointerEvent('pointerdown', {
    bubbles: true,
    pointerId: Number({{pointerId}}),
    pointerType: 'mouse',
    clientX: gapPoint.x,
    clientY: gapPoint.y,
  }));

  window.dispatchEvent(new PointerEvent('pointermove', {
    bubbles: true,
    pointerId: Number({{pointerId}}),
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  return true;
})()
JS,
            [
                'deltaX' => -170,
                'pointerId' => 952,
            ],
        ),
    );
    expect($didSwipeBackFromGap)->toBeTrue();
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $initialPage,
        6_000,
    );
    $assertReaderRenderable();

    $sliderTargetPage = (int) $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const slider = document.querySelector('.quran-page-slider');
  if (!(slider instanceof HTMLInputElement)) {
    return 0;
  }

  const currentPage = Number(data.pageInput ?? data.pageNumber ?? 1);
  const targetPage = Math.min(Number(data.maxPage ?? 1), currentPage + 3);

  slider.value = String(targetPage);
  slider.dispatchEvent(new Event('input', { bubbles: true }));
  slider.dispatchEvent(new Event('change', { bubbles: true }));

  return targetPage;
})()
JS,
        ),
    );
    expect($sliderTargetPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $sliderTargetPage,
        6_000,
    );
    $assertReaderRenderable();

    safeClick($page, '.quran-page-slider-chip');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector(".fi-modal-window"))', true, 5_000);
    $jumpTargetPage = (int) $page->script(
        quranReaderDataScript('Math.min(Number(data.maxPage ?? 1), Number(data.pageNumber ?? 1) + 4)'),
    );
    expect($jumpTargetPage)->toBeGreaterThan(0);
    $page->script(
        js_template(
            <<<'JS'
(() => {
  const input = document.querySelector('#quran-reader-page-counter-input');
  if (!(input instanceof HTMLInputElement)) {
    return false;
  }

  input.value = String({{target}});
  input.dispatchEvent(new Event('input', { bubbles: true }));

  const submitButton = input.closest('.fi-modal-window')?.querySelector('button[type="submit"]');
  if (!(submitButton instanceof HTMLButtonElement)) {
    return false;
  }

  submitButton.click();

  return true;
})()
JS,
            ['target' => $jumpTargetPage],
        ),
    );
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $jumpTargetPage,
        6_000,
    );
    $assertReaderRenderable();

    safeClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.wirdModeActive) || Boolean(document.querySelector("#support-unlock-modal"))',
        ),
        true,
        6_000,
    );
    $unlockModalVisible = (bool) $page->script('Boolean(document.querySelector("#support-unlock-modal"))');

    if ($unlockModalVisible) {
        $page->script(
            <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
        );
        waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('Boolean(data.isSupportLockActive())'),
            false,
            8_000,
        );
        safeClick($page, '[data-quran-wird-toggle]');
    }

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.wirdModeActive) || Boolean(document.querySelector("#support-unlock-modal"))',
        ),
        true,
        6_000,
    );
    $unlockModalVisible = (bool) $page->script('Boolean(document.querySelector("#support-unlock-modal"))');

    if ($unlockModalVisible) {
        $page->script(
            <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
        );
        waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('Boolean(data.isSupportLockActive())'),
            false,
            8_000,
        );
        $page->script(quranReaderCommandScript('void data.toggleWirdMode();'));
    }

    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 6_000);
    $assertReaderRenderable();

    $wirdSliderTarget = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const slider = document.querySelector('.quran-page-slider');
  if (!(slider instanceof HTMLInputElement)) {
    return null;
  }

  const min = Number(slider.min || '0');
  const max = Number(slider.max || '0');
  const currentStep = Number(slider.value || String(min));
  const targetStep = Math.max(min, Math.min(max, currentStep + 2));

  slider.value = String(targetStep);
  slider.dispatchEvent(new Event('input', { bubbles: true }));
  slider.dispatchEvent(new Event('change', { bubbles: true }));

  return {
    step: targetStep,
    page: Number(data.pageInput ?? data.pageNumber ?? 0),
  };
})()
JS,
        ),
    );
    expect($wirdSliderTarget)->toBeArray();
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0) > 0'), true, 6_000);
    $assertReaderRenderable();

    scriptClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), false, 8_000);
    $assertReaderRenderable();

    scriptClick($page, '[data-quran-open-history]');
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-history-modal"))',
        true,
        5_000,
    );
    safeClick($page, '.fi-modal-window .fi-modal-close-btn');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    $assertReaderRenderable();

    scriptClick($page, '[data-quran-bookmark-toggle]');
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.bookmarks.length ?? 0) >= 1'), true, 6_000);
    $bookmarkedPage = $currentPageNumber();

    $page->script(
        quranReaderCommandScript(
            "data.dispatchPageNavigationRequest(Math.min(Number(data.maxPage ?? 1), Number(data.pageNumber ?? 1) + 1), 'test-bookmark-manager-navigation');",
        ),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0) !== 0'), true, 6_000);
    $assertReaderRenderable();

    $page->script(quranReaderCommandScript('data.openBookmarksManager();'));
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-bookmarks-modal"))',
        true,
        5_000,
    );
    $page->script(
        quranReaderCommandScript(
            js_template(
                <<<'JS'
const bookmarkedPage = Number({{bookmarkedPage}});
const bookmark = (Array.isArray(data.bookmarks) ? data.bookmarks : [])
  .find((entry) => Number(entry?.page_number ?? 0) === bookmarkedPage);

if (!bookmark?.id || typeof data.handleBookmarksManagerGoEvent !== 'function') {
  return false;
}

void data.handleBookmarksManagerGoEvent({ id: bookmark.id });

return true;
JS,
                ['bookmarkedPage' => $bookmarkedPage],
            ),
        ),
    );
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $bookmarkedPage,
        6_000,
    );
    $assertReaderRenderable();

    scriptClick($page, '[data-quran-open-history]');
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#quran-reader-history-modal"))',
        true,
        5_000,
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.navigationHistory.length ?? 0) >= 1'), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript("String(data.navigationHistory[0]?.source ?? '')"),
        'bookmark-navigation',
        6_000,
    );
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
const entry = Array.isArray(data.navigationHistory) ? data.navigationHistory[0] ?? null : null;

if (!entry?.id || typeof data.handleHistoryManagerGoEvent !== 'function') {
  return false;
}

void data.handleHistoryManagerGoEvent({ id: entry.id });

return true;
JS,
        ),
    );
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    $assertReaderRenderable();
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

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);
    $quickSurahTargetPage = (int) $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const currentPage = Number(data.pageNumber ?? 1);
  const entries = Array.isArray(data.search?.surahDirectory) ? data.search.surahDirectory : [];
  const entry = entries.find((item) => Number(item?.page_number ?? 0) > currentPage) ?? entries[0] ?? null;
  if (!entry) {
    return 0;
  }

  const tiles = Array.from(document.querySelectorAll('#quran-reader-search-modal .quran-surah-tile'));
  const tile = tiles.find((item) => Number(item.getAttribute('data-surah-number') ?? 0) === Number(entry?.surah_number ?? 0));
  const targetPage = Number(entry?.page_number ?? 0);
  if (targetPage <= 0) {
    return 0;
  }

  if (tile instanceof HTMLButtonElement) {
    tile.click();
  }

  if (typeof data.dispatchPageNavigationRequest === 'function') {
    data.dispatchPageNavigationRequest(targetPage, 'test-surah-directory-fallback');
  }

  return targetPage;
})()
JS,
        ),
    );
    expect($quickSurahTargetPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $quickSurahTargetPage,
        6_000,
    );
    $assertReaderRenderable();

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
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.search.results.length ?? 0) > 0'), true, 12_000);

    $searchTargetPage = (int) $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const currentPage = Number(data.pageNumber ?? 0);
  const results = Array.isArray(data.search?.results) ? data.search.results : [];
  const targetResult = results.find((result) => {
    const page = Number(result?.page_number ?? 0);
    return page > 0 && page !== currentPage;
  }) ?? results[0] ?? null;

  if (!targetResult || typeof data.goToSearchResult !== 'function') {
    return 0;
  }

  const page = Number(targetResult?.page_number ?? 0);
  if (page <= 0) {
    return 0;
  }

  void data.goToSearchResult(targetResult);

  if (typeof data.dispatchPageNavigationRequest === 'function') {
    data.dispatchPageNavigationRequest(page, 'test-search-result-fallback');
  }

  return page;
})()
JS,
        ),
    );
    expect($searchTargetPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $searchTargetPage,
        6_000,
    );
    $assertReaderRenderable();

    $page->refresh();
    waitForAlpineReady($page);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    $page->assertNoJavaScriptErrors();
});

it('keeps the reader visible on 4xl when jumping from opening spread to dense pages', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 10_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data._pendingNavigationRequest === null && !data._navigationRevealLocked && !data.isLoadingPage'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.isFittingPage'),
            false,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
            'ready',
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(lines);
  const opacity = Number.parseFloat(styles.opacity || '0');
  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return String(lines.getAttribute('data-fit-state') ?? '') === 'ready'
    && styles.visibility !== 'hidden'
    && opacity > 0.35
    && lineTexts.length > 0;
})()
JS,
            true,
            $timeoutMs,
        );
    };

    resetBrowserState($page);
    safeBrowserResize($page, 2560, 1440);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    waitForScriptWithTimeout($page, 'window.innerWidth >= 2550', true, 5_000);
    waitForScriptWithTimeout($page, 'window.innerHeight >= 1430', true, 5_000);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 1, 8_000);

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(2, 'page-jump');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 2, 8_000);
    $assertReaderRenderable();

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(3, 'page-jump');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 3, 12_000);
    $assertReaderRenderable(12_000);

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(604, 'page-jump');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 604, 14_000);
    $assertReaderRenderable(14_000);

    $page->assertNoJavaScriptErrors();
});

it('keeps dense-to-headered jump-page navigation to a single visible reveal on mobile', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 10_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data._pendingNavigationRequest === null && !data._navigationRevealLocked && !data.isLoadingPage'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.isFittingPage'),
            false,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
            'ready',
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(lines);
  const opacity = Number.parseFloat(styles.opacity || '0');
  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return String(lines.getAttribute('data-fit-state') ?? '') === 'ready'
    && styles.visibility !== 'hidden'
    && opacity > 0.35
    && lineTexts.length > 0;
})()
JS,
            true,
            $timeoutMs,
        );
    };

    resetBrowserState($page, true);
    safeBrowserResize($page, 390, 844);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    waitForScriptWithTimeout($page, 'window.innerWidth <= 420', true, 5_000);
    waitForScriptWithTimeout($page, 'window.innerHeight >= 820', true, 5_000);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(3, 'test-dense-headered-setup');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 3, 8_000);
    $assertReaderRenderable();
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.isDenseFullLinePage())'), true, 6_000);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
window.__quranDenseHeaderedJumpRevealStats = (() => {
  const stats = {
    fittingRevealCount: 0,
    visibleRevealCount: 0,
    samples: [],
  };
  const targetPage = 50;
  const startedAt = Date.now();
  let lastIsFitting = Boolean(data.isFittingPage);
  let lastVisible = false;
  const timer = window.setInterval(() => {
    const lines = document.querySelector('.quran-page-lines');
    const visible = lines instanceof HTMLElement
      && window.getComputedStyle(lines).visibility !== 'hidden'
      && Number.parseFloat(window.getComputedStyle(lines).opacity || '0') > 0.35;
    const currentPage = Number(data.pageNumber ?? 0);

    if (currentPage === targetPage && lastIsFitting && !data.isFittingPage) {
      stats.fittingRevealCount += 1;
    }

    if (currentPage === targetPage && !lastVisible && visible) {
      stats.visibleRevealCount += 1;
    }

    stats.samples.push({
      t: Date.now() - startedAt,
      pageNumber: currentPage,
      isFittingPage: Boolean(data.isFittingPage),
      visible,
      fitState: typeof data.pageFitState === 'function' ? data.pageFitState() : null,
    });

    lastIsFitting = Boolean(data.isFittingPage);
    lastVisible = visible;

    if (Date.now() - startedAt >= 3200) {
      window.clearInterval(timer);
      stats.done = true;
    }
  }, 30);

  stats.stop = () => {
    window.clearInterval(timer);
    stats.done = true;

    return stats;
  };

  return stats;
})();

return true;
JS,
        ),
    );

    safeClick($page, '.quran-page-slider-chip');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector(".fi-modal-window"))', true, 5_000);
    $page->script(
        js_template(
            <<<'JS'
(() => {
  const input = document.querySelector('#quran-reader-page-counter-input');
  if (!(input instanceof HTMLInputElement)) {
    return false;
  }

  input.value = String({{target}});
  input.dispatchEvent(new Event('input', { bubbles: true }));

  const submitButton = input.closest('.fi-modal-window')?.querySelector('button[type="submit"]');
  if (!(submitButton instanceof HTMLButtonElement)) {
    return false;
  }

  submitButton.click();

  return true;
})()
JS,
            ['target' => 50],
        ),
    );

    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 50, 8_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.isSingleHeaderLongContentPage())'), true, 8_000);
    $assertReaderRenderable(12_000);

    $revealStats = $page->script(
        'window.__quranDenseHeaderedJumpRevealStats?.stop?.() ?? window.__quranDenseHeaderedJumpRevealStats ?? null;',
    );
    expect($revealStats)->toBeArray();
    expect((int) ($revealStats['fittingRevealCount'] ?? 0))->toBeLessThanOrEqual(1);
    expect((int) ($revealStats['visibleRevealCount'] ?? 0))->toBeLessThanOrEqual(1);

    $page->assertNoJavaScriptErrors();
});

it('keeps modal jump-page header and basmallah geometry aligned with a subsequent regular render on mobile', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 10_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data._pendingNavigationRequest === null && !data._navigationRevealLocked && !data.isLoadingPage'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.isFittingPage'),
            false,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
            'ready',
            $timeoutMs,
        );
    };

    $measureHeaderedGeometry = function () use ($page): array {
        $metrics = $page->script(
            <<<'JS'
(() => {
  const frame = document.querySelector('[x-ref="pageFrame"]');
  const lines = document.querySelector('.quran-page-lines');
  const header = document.querySelector('.quran-surah-header-line');
  const basmallah = document.querySelector('.quran-basmallah-line');

  if (!(frame instanceof HTMLElement) || !(lines instanceof HTMLElement) || !(header instanceof HTMLElement) || !(basmallah instanceof HTMLElement)) {
    return null;
  }

  const frameRect = frame.getBoundingClientRect();
  const linesRect = lines.getBoundingClientRect();
  const headerRect = header.getBoundingClientRect();
  const basmallahRect = basmallah.getBoundingClientRect();

  return {
    fillRatio: Number((linesRect.height / frameRect.height).toFixed(4)),
    gap: Number((basmallahRect.top - headerRect.bottom).toFixed(2)),
  };
})()
JS,
        );

        expect($metrics)->toBeArray();

        return $metrics;
    };

    resetBrowserState($page, true);
    safeBrowserResize($page, 390, 844);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    waitForScriptWithTimeout($page, 'window.innerWidth <= 420', true, 5_000);
    waitForScriptWithTimeout($page, 'window.innerHeight >= 820', true, 5_000);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    $page->script(quranReaderCommandScript("data.dispatchPageNavigationRequest(3, 'test-modal-jump-geometry-seed');"));
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 3, 8_000);
    $assertReaderRenderable();

    $page->script(quranReaderCommandScript("data.dispatchPageNavigationRequest(50, 'page-jump');"));
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 50, 10_000);
    $assertReaderRenderable(10_000);
    $modalMetrics = $measureHeaderedGeometry();

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(51, 'test-modal-jump-geometry-next');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 51, 8_000);
    $assertReaderRenderable();

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(50, 'test-modal-jump-geometry-return');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 50, 8_000);
    $assertReaderRenderable();
    $directMetrics = $measureHeaderedGeometry();

    expect(abs((float) $modalMetrics['gap'] - (float) $directMetrics['gap']))->toBeLessThan(1.6);
    expect(abs((float) $modalMetrics['fillRatio'] - (float) $directMetrics['fillRatio']))->toBeLessThan(0.03);

    $page->assertNoJavaScriptErrors();
});

it('keeps surah-directory modal navigation fitted like a subsequent regular render on mobile', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 10_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data._pendingNavigationRequest === null && !data._navigationRevealLocked && !data.isLoadingPage'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.isFittingPage'),
            false,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
            'ready',
            $timeoutMs,
        );
    };

    $measureHeaderOnlyFit = function () use ($page): array {
        $metrics = $page->script(
            <<<'JS'
(() => {
  const frame = document.querySelector('[x-ref="pageFrame"]');
  const lines = document.querySelector('.quran-page-lines');

  if (!(frame instanceof HTMLElement) || !(lines instanceof HTMLElement)) {
    return null;
  }

  const frameRect = frame.getBoundingClientRect();
  const linesRect = lines.getBoundingClientRect();

  return {
    fillRatio: Number((linesRect.height / frameRect.height).toFixed(4)),
    widthRatio: Number((linesRect.width / frameRect.width).toFixed(4)),
  };
})()
JS,
        );

        expect($metrics)->toBeArray();

        return $metrics;
    };

    resetBrowserState($page, true);
    safeBrowserResize($page, 390, 844);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    waitForScriptWithTimeout($page, 'window.innerWidth <= 420', true, 5_000);
    waitForScriptWithTimeout($page, 'window.innerHeight >= 820', true, 5_000);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    $page->script(quranReaderCommandScript("data.dispatchPageNavigationRequest(3, 'test-surah-directory-seed');"));
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 3, 8_000);
    $assertReaderRenderable();

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 5_000);
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
const entries = Array.isArray(data.search?.surahDirectory) ? data.search.surahDirectory : [];
const entry = entries.find((item) => Number(item?.page_number ?? 0) === 187) ?? null;

if (!entry || typeof data.goToSurahFromDirectory !== 'function') {
  return false;
}

void data.goToSurahFromDirectory(entry);

return true;
JS,
        ),
    );
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 187, 8_000);
    $assertReaderRenderable();
    $modalMetrics = $measureHeaderOnlyFit();

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(188, 'test-surah-directory-next');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 188, 8_000);
    $assertReaderRenderable();

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(187, 'test-surah-directory-return');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 187, 8_000);
    $assertReaderRenderable();
    $directMetrics = $measureHeaderOnlyFit();

    expect(abs((float) $modalMetrics['fillRatio'] - (float) $directMetrics['fillRatio']))->toBeLessThan(0.03);
    expect(abs((float) $modalMetrics['widthRatio'] - (float) $directMetrics['widthRatio']))->toBeLessThan(0.02);

    $page->assertNoJavaScriptErrors();
});

it('keeps the reader visible on 4xl after rapid swipe-next then slider jump to page 604', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 12_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.isFittingPage'),
            false,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
            'ready',
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(lines);
  const opacity = Number.parseFloat(styles.opacity || '0');
  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return String(lines.getAttribute('data-fit-state') ?? '') === 'ready'
    && styles.visibility !== 'hidden'
    && opacity > 0.35
    && lineTexts.length > 0;
})()
JS,
            true,
            $timeoutMs,
        );
    };

    resetBrowserState($page);
    safeBrowserResize($page, 2560, 1440);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 1, 8_000);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
void data.dispatchSwipeNavigation('next');
void data.dispatchSwipeNavigation('next');
JS,
        ),
    );

    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 3, 14_000);
    $assertReaderRenderable(14_000);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
const slider = document.querySelector('.quran-page-slider');

if (!(slider instanceof HTMLInputElement)) {
  return false;
}

slider.value = '604';
slider.dispatchEvent(new Event('input', { bubbles: true }));
slider.dispatchEvent(new Event('change', { bubbles: true }));

return true;
JS,
        ),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 604, 16_000);
    $assertReaderRenderable(16_000);

    $page->assertNoJavaScriptErrors();
});

it('keeps quran pages renderable after rapid bookmark modal closes and chevron burst navigation', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 8_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.isFittingPage'),
            false,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
            'ready',
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(lines);
  const opacity = Number.parseFloat(styles.opacity || '0');
  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return String(lines.getAttribute('data-fit-state') ?? '') === 'ready'
    && styles.visibility !== 'hidden'
    && opacity > 0.35
    && lineTexts.length > 0;
})()
JS,
            true,
            $timeoutMs,
        );
    };

    resetBrowserState($page, true);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    scriptClick($page, '[data-quran-bookmark-toggle]');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.bookmarks.length ?? 0) >= 1'),
        true,
        6_000,
    );

    for ($cycle = 0; $cycle < 3; $cycle += 1) {
        $page->script(quranReaderCommandScript('data.openBookmarksManager();'));

        waitForScriptWithTimeout(
            $page,
            'Boolean(document.querySelector("#quran-reader-bookmarks-modal"))',
            true,
            5_000,
        );
        safeClick($page, '.fi-modal-window .fi-modal-close-btn');
        waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
        $assertReaderRenderable();
    }

    $sliderTargetPage = (int) $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const slider = document.querySelector('.quran-page-slider');
  if (!(slider instanceof HTMLInputElement)) {
    return 0;
  }

  const current = Number(data.pageNumber ?? 1);
  const target = Math.min(Number(data.maxPage ?? 1), current + 2);
  slider.value = String(target);
  slider.dispatchEvent(new Event('input', { bubbles: true }));
  slider.dispatchEvent(new Event('change', { bubbles: true }));

  return target;
})()
JS,
        ),
    );

    expect($sliderTargetPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $sliderTargetPage,
        6_000,
    );
    $assertReaderRenderable();

    $page->script(<<<'JS'
(() => {
  window.dispatchEvent(new KeyboardEvent('keydown', {
    bubbles: true,
    cancelable: true,
    key: 'ArrowLeft',
  }));
})()
JS);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $sliderTargetPage + 1,
        6_000,
    );
    $assertReaderRenderable();

    $page->script(<<<'JS'
(() => {
  const next = document.querySelector('.quran-bottom-strip-nav-next');
  if (!(next instanceof HTMLButtonElement)) {
    return false;
  }

  next.click();
  next.click();

  return true;
})()
JS);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $sliderTargetPage + 3,
        6_000,
    );
    $assertReaderRenderable();

    safeClick($page, '.quran-bottom-strip-nav-prev');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $sliderTargetPage + 2,
        6_000,
    );
    $assertReaderRenderable();

    $page->assertNoJavaScriptErrors();
});

it('animates wird page counter morph and slider tween for chevron keyboard and swipe navigation', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page, true);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    safeClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.wirdModeActive) || Boolean(document.querySelector("#support-unlock-modal"))',
        ),
        true,
        6_000,
    );
    $unlockModalVisible = (bool) $page->script('Boolean(document.querySelector("#support-unlock-modal"))');

    if ($unlockModalVisible) {
        $page->script(
            <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
        );
        waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('Boolean(data.isSupportLockActive())'),
            false,
            8_000,
        );
        safeClick($page, '[data-quran-wird-toggle]');
    }

    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0) > 0'), true, 6_000);

    $wirdSourceProfiles = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => ({
  keyboard: String(data.wirdNavigationSourceProfile('keyboard') ?? ''),
  swipe: String(data.wirdNavigationSourceProfile('swipe') ?? ''),
  chevron: String(data.wirdNavigationSourceProfile('chevron') ?? ''),
}))()
JS,
        ),
    );

    expect($wirdSourceProfiles)->toBeArray();
    expect((string) ($wirdSourceProfiles['keyboard'] ?? ''))->toBe('keyboard');
    expect((string) ($wirdSourceProfiles['swipe'] ?? ''))->toBe('swipe');
    expect((string) ($wirdSourceProfiles['chevron'] ?? ''))->toBe('chevron');

    $startSliderTweenMonitor = function () use ($page): void {
        $page->script(
            quranReaderCommandScript(
                <<<'JS'
window.__sawFractionalWirdSlider = false;

if (typeof window.__stopWirdSliderTweenMonitor === 'function') {
  window.__stopWirdSliderTweenMonitor();
}

const monitorInterval = window.setInterval(() => {
  const visualStep = Number(data.wirdSliderVisualStep ?? NaN);

  if (!Number.isFinite(visualStep)) {
    return;
  }

  if (Math.abs(visualStep - Math.trunc(visualStep)) > 0.001) {
    window.__sawFractionalWirdSlider = true;
  }
}, 16);

window.__stopWirdSliderTweenMonitor = () => {
  window.clearInterval(monitorInterval);
};
JS,
            ),
        );
    };

    $resetPulseState = function () use ($page): void {
        $page->script(
            quranReaderCommandScript(
                <<<'JS'
data.pageCounterPulse.isActive = false;
data.pageCounterPulse.hasChanges = false;
data.pageCounterPulse.segments = [];
JS,
            ),
        );
    };

    $stopSliderTweenMonitor = function () use ($page): void {
        $page->script(<<<'JS'
(() => {
  if (typeof window.__stopWirdSliderTweenMonitor === 'function') {
    window.__stopWirdSliderTweenMonitor();
  }
})()
JS);
    };

    $baselinePage = (int) $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));
    expect($baselinePage)->toBeGreaterThan(0);

    $startSliderTweenMonitor();
    $resetPulseState();
    safeClick($page, '.quran-bottom-strip-nav-next');
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $baselinePage + 1, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.pageCounterPulse.hasChanges && Array.isArray(data.pageCounterPulse.segments) && data.pageCounterPulse.segments.some((segment) => Boolean(segment?.changed)))',
        ),
        true,
        2_000,
    );
    waitForScriptWithTimeout($page, 'Boolean(window.__sawFractionalWirdSlider)', true, 3_000);
    $stopSliderTweenMonitor();

    $afterChevronPage = (int) $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));

    $startSliderTweenMonitor();
    $resetPulseState();
    $page->script(<<<'JS'
(() => {
  window.dispatchEvent(new KeyboardEvent('keydown', {
    bubbles: true,
    cancelable: true,
    key: 'ArrowRight',
  }));
})()
JS);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $afterChevronPage - 1, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.pageCounterPulse.hasChanges && Array.isArray(data.pageCounterPulse.segments) && data.pageCounterPulse.segments.some((segment) => Boolean(segment?.changed)))',
        ),
        true,
        2_000,
    );
    waitForScriptWithTimeout($page, 'Boolean(window.__sawFractionalWirdSlider)', true, 3_000);
    $stopSliderTweenMonitor();

    $beforeSwipePage = (int) $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));
    $startSliderTweenMonitor();
    $resetPulseState();
    $didSwipeFromGap = $page->script(<<<'JS'
(() => {
  const linesContainer = document.querySelector('.quran-page-lines');
  const lineEntries = Array.from(document.querySelectorAll('.quran-page-lines [data-quran-line]'))
    .filter((entry) => entry instanceof HTMLElement);

  if (!(linesContainer instanceof HTMLElement) || lineEntries.length < 2) {
    return false;
  }

  let gapPoint = null;

  for (let index = 0; index < lineEntries.length - 1; index += 1) {
    const currentRect = lineEntries[index].getBoundingClientRect();
    const nextRect = lineEntries[index + 1].getBoundingClientRect();
    const gapHeight = nextRect.top - currentRect.bottom;

    if (gapHeight > 4) {
      gapPoint = {
        x: currentRect.left + (currentRect.width / 2),
        y: currentRect.bottom + (gapHeight / 2),
      };
      break;
    }
  }

  if (!gapPoint) {
    return false;
  }

  const endPoint = {
    x: gapPoint.x + 170,
    y: gapPoint.y,
  };

  linesContainer.dispatchEvent(new PointerEvent('pointerdown', {
    bubbles: true,
    pointerId: 981,
    pointerType: 'mouse',
    clientX: gapPoint.x,
    clientY: gapPoint.y,
  }));

  window.dispatchEvent(new PointerEvent('pointermove', {
    bubbles: true,
    pointerId: 981,
    pointerType: 'mouse',
    clientX: endPoint.x,
    clientY: endPoint.y,
  }));

  return true;
})()
JS);
    expect($didSwipeFromGap)->toBeTrue();
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $beforeSwipePage + 1, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.pageCounterPulse.hasChanges && Array.isArray(data.pageCounterPulse.segments) && data.pageCounterPulse.segments.some((segment) => Boolean(segment?.changed)))',
        ),
        true,
        2_000,
    );
    waitForScriptWithTimeout($page, 'Boolean(window.__sawFractionalWirdSlider)', true, 3_000);
    $stopSliderTweenMonitor();

    $page->assertNoJavaScriptErrors();
});

it('exits wird mode at boundaries for chevron keyboard and swipe navigation', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
localStorage.removeItem('quran-reader-wird-progress-v1');
data.wirdModeActive = false;
data.wirdBrowseStep = null;
data.wirdState = null;
data.wirdDailyRecord = null;
void data.ensureWirdDailyRecord({ forceRebuild: true });

return true;
JS,
        ),
    );

    $enterWirdMode = function () use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript(
                'Boolean(document.querySelector("[data-quran-wird-toggle]")) && !data.isLoadingPage && !data.isFittingPage && data._pendingNavigationRequest === null',
            ),
            true,
            8_000,
        );
        safeClick($page, '[data-quran-wird-toggle]');
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript(
                'Boolean(data.wirdModeActive) || Boolean(document.querySelector("#support-unlock-modal"))',
            ),
            true,
            6_000,
        );
        $unlockModalVisible = (bool) $page->script(
            'Boolean(document.querySelector("#support-unlock-modal"))',
        );

        if ($unlockModalVisible) {
            $page->script(
                <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
            );
            waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
            waitForScriptWithTimeout(
                $page,
                quranReaderDataScript('Boolean(data.isSupportLockActive())'),
                false,
                8_000,
            );
            safeClick($page, '[data-quran-wird-toggle]');
        }

        waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 6_000);
    };

    $setCompletedWirdBoundary = function () use ($page): int {
        return (int) $page->script(
            quranReaderDataScript(
                <<<'JS'
(() => {
  const record = data.ensureWirdDailyRecord();
  const range = data.wirdRangeState(record);
  const targetPage = Number(data.wirdTargetPageFromStep(range.maxStep, record) ?? 0);

  record.completed = true;
  record.currentStep = range.maxStep;
  record.progressStep = range.maxStep;
  record.updatedAt = Date.now();
  data.wirdDailyRecord = record;
  data.wirdBrowseStep = range.maxStep;
  data.syncWirdSliderVisualStep(record);

  return targetPage;
})()
JS,
            ),
        );
    };

    $setFirstWirdBoundary = function () use ($page): int {
        return (int) $page->script(
            quranReaderDataScript(
                <<<'JS'
(() => {
const record = data.ensureWirdDailyRecord();
const range = data.wirdRangeState(record);

record.completed = false;
record.currentStep = 0;
record.progressStep = 0;
record.updatedAt = Date.now();
data.wirdDailyRecord = record;
data.wirdBrowseStep = null;
data.syncWirdSliderVisualStep(record);

return Number(data.wirdTargetPageFromStep(0, record) ?? 0);
})()
JS,
            ),
        );
    };

    $enterWirdMode();
    $completedBoundaryPage = $setCompletedWirdBoundary();
    expect($completedBoundaryPage)->toBeGreaterThan(0);
    $page->script(
        quranReaderCommandScript(
            js_template(
                "void data.goToPageFromChevron({{targetPage}}, { source: 'test-wird-boundary-next', commitNow: true, settleDelayMs: 0 });",
                ['targetPage' => $completedBoundaryPage],
            ),
        ),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $completedBoundaryPage, 6_000);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
void data.goNextFromChevron('chevron');

return true;
JS,
        ),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), false, 8_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.isLoadingPage && !data.isFittingPage && data._pendingNavigationRequest === null'),
        true,
        8_000,
    );

    $enterWirdMode();

    $firstBoundaryPage = $setFirstWirdBoundary();
    expect($firstBoundaryPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $firstBoundaryPage, 6_000);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
void data.onGlobalArrowNavigate(
  'right',
  new KeyboardEvent('keydown', {
    bubbles: true,
    cancelable: true,
    key: 'ArrowRight',
  }),
);

return true;
JS,
        ),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), false, 8_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.isLoadingPage && !data.isFittingPage && data._pendingNavigationRequest === null'),
        true,
        8_000,
    );

    $enterWirdMode();

    $swipeBoundaryPage = $setFirstWirdBoundary();
    expect($swipeBoundaryPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $swipeBoundaryPage, 6_000);

    $didSwipe = (bool) $page->script(
        quranReaderCommandScript(
            <<<'JS'
void data.dispatchSwipeNavigation('prev');

return true;
JS,
        ),
    );
    expect($didSwipe)->toBeTrue();
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), false, 6_000);

    $page->assertNoJavaScriptErrors();
});

it('clears search results immediately after closing the search modal', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 6_000);

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

    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.search.results.length ?? 0) > 0'), true, 12_000);
    safeClick($page, '.fi-modal-window .fi-modal-close-btn');
    waitForScriptWithTimeout($page, modalClosedScript(), true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Boolean(!data.search.modalOpen && String(data.search.query ?? "") === "" && Number(data.search.results.length ?? 0) === 0)'),
        true,
        6_000,
    );

    scriptClick($page, '.quran-soorah-trigger');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector("#quran-reader-search-modal"))', true, 6_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            <<<'JS'
(() => {
  const activeTile = document.querySelector('#quran-reader-search-modal .quran-surah-tile--active');

  return String(data.search.query ?? '') === ''
    && Number(data.search.results.length ?? 0) === 0
    && activeTile instanceof HTMLElement;
})()
JS,
        ),
        true,
        6_000,
    );

    $page->assertNoJavaScriptErrors();
});

it('restores the prior page and keeps it rendered when exiting wird after rapid navigation', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
const currentPage = Number(data.pageNumber ?? 1);
const maxPage = Number(data.maxPage ?? currentPage);

data.dispatchPageNavigationRequest(
  Math.min(maxPage, currentPage + 2),
  'test-wird-race-pre-1',
);
data.dispatchPageNavigationRequest(
  Math.min(maxPage, currentPage + 4),
  'test-wird-race-pre-2',
);
JS,
        ),
    );

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data._pendingNavigationRequest === null && !data.isLoadingPage && !data.isFittingPage'),
        true,
        8_000,
    );
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
if (!data.wirdModeActive && !document.querySelector('#support-unlock-modal')) {
  if (typeof data.enterWirdMode === 'function') {
    void data.enterWirdMode();
  } else {
    void data.toggleWirdMode();
  }
}
JS,
        ),
    );

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.wirdModeActive) || Boolean(document.querySelector("#support-unlock-modal"))',
        ),
        true,
        6_000,
    );
    $unlockModalVisible = (bool) $page->script('Boolean(document.querySelector("#support-unlock-modal"))');

    if ($unlockModalVisible) {
        $page->script(
            <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
        );
        waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('Boolean(data.isSupportLockActive())'),
            false,
            8_000,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data._pendingNavigationRequest === null && !data.isLoadingPage && !data.isFittingPage'),
            true,
            8_000,
        );
        $page->script(
            quranReaderCommandScript(
                <<<'JS'
if (!data.wirdModeActive) {
  if (typeof data.enterWirdMode === 'function') {
    void data.enterWirdMode();
  } else {
    void data.toggleWirdMode();
  }
}
JS,
            ),
        );
    }

    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 6_000);
    $restoredPage = (int) $page->script(
        quranReaderDataScript('Number(data.wirdNormalPageBeforeMode ?? 0)'),
    );
    expect($restoredPage)->toBeGreaterThan(0);

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
const record = data.ensureWirdDailyRecord();
const range = data.wirdRangeState(record);

record.completed = true;
record.currentStep = range.maxStep;
record.progressStep = range.maxStep;
record.updatedAt = Date.now();
data.wirdDailyRecord = record;
data.wirdBrowseStep = range.maxStep;
data.syncWirdSliderVisualStep(record);

window.dispatchEvent(new CustomEvent('quran-go-next', {
  detail: { source: 'test-wird-race-next-1' },
}));
window.dispatchEvent(new CustomEvent('quran-go-next', {
  detail: { source: 'test-wird-race-next-2' },
}));
JS,
        ),
    );

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            '!data.wirdModeActive || Number(data.pageNumber ?? 0) === Number(data.wirdNormalPageBeforeMode ?? 0)',
        ),
        true,
        8_000,
    );
    $page->script(
        quranReaderCommandScript(
            <<<'JS'
if (data.wirdModeActive) {
  if (typeof data.exitWirdMode === 'function') {
    void data.exitWirdMode('test-wird-race-force-exit');
  } else {
    void data.toggleWirdMode();
  }
}
JS,
        ),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), false, 8_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $restoredPage, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 8_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
        'ready',
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(lines);
  const opacity = Number.parseFloat(styles.opacity || '0');
  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return String(lines.getAttribute('data-fit-state') ?? '') === 'ready'
    && styles.visibility !== 'hidden'
    && opacity > 0.35
    && lineTexts.length > 0;
})()
JS,
        true,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data._pendingNavigationRequest === null && !data._navigationRevealLocked'),
        true,
        6_000,
    );

    $page->assertNoJavaScriptErrors();
});

it('lands on the final wird slider page and keeps the re-entered completed page visible', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    $assertReaderRenderable = function (int $timeoutMs = 8_000) use ($page): void {
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.ready && data.mushafLines.length > 0'),
            true,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('data.isFittingPage'),
            false,
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript("typeof data.pageFitState === 'function' ? data.pageFitState() : 'ready'"),
            'ready',
            $timeoutMs,
        );
        waitForScriptWithTimeout(
            $page,
            <<<'JS'
(() => {
  const lines = document.querySelector('.quran-page-lines');
  if (!(lines instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(lines);
  const opacity = Number.parseFloat(styles.opacity || '0');
  const lineTexts = Array.from(lines.querySelectorAll('[data-quran-line-text]'))
    .map((line) => String(line.textContent ?? '').replace(/\s+/g, '').trim())
    .filter((text) => text.length > 0);

  return String(lines.getAttribute('data-fit-state') ?? '') === 'ready'
    && styles.visibility !== 'hidden'
    && opacity > 0.35
    && lineTexts.length > 0;
})()
JS,
            true,
            $timeoutMs,
        );
    };

    resetBrowserState($page, true);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    safeClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#support-unlock-modal"))',
        true,
        6_000,
    );
    $page->script(
        <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
    );
    waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Boolean(data.isSupportLockActive())'),
        false,
        8_000,
    );

    safeClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 8_000);

    $preSliderPage = (int) $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const record = data.ensureWirdDailyRecord();
  return Number(data.wirdTargetPageFromStep(2, record) ?? 0);
})()
JS,
        ),
    );
    expect($preSliderPage)->toBeGreaterThan(0);

    $page->script(
        quranReaderCommandScript("void data.navigateWirdToStep(2, 'test-pre-slider-step');"),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $preSliderPage,
        8_000,
    );
    $assertReaderRenderable();

    $sliderLanding = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const record = data.ensureWirdDailyRecord();
  const range = data.wirdRangeState(record);
  const slider = document.querySelector('.quran-page-slider');

  if (!(slider instanceof HTMLInputElement)) {
    return null;
  }

  const currentStep = Math.max(0, Number(data.wirdActiveStepForNavigation(record) ?? 0));
  const finalStep = Math.max(0, Number(range.maxStep ?? 0));

  for (let step = currentStep + 1; step <= finalStep; step += 1) {
    slider.value = String(step);
    slider.dispatchEvent(new Event('input', { bubbles: true }));
  }

  slider.value = String(finalStep);
  slider.dispatchEvent(new Event('input', { bubbles: true }));
  slider.dispatchEvent(new Event('change', { bubbles: true }));

  return {
    finalPage: Number(data.wirdTargetPageFromStep(finalStep, record) ?? 0),
    finalStep,
  };
})()
JS,
        ),
    );

    expect($sliderLanding)->toBeArray();
    $sliderLandingPage = (int) ($sliderLanding['finalPage'] ?? 0);
    expect($sliderLandingPage)->toBeGreaterThan(0);

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $sliderLandingPage,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageInput ?? 0)'),
        $sliderLandingPage,
        8_000,
    );
    $assertReaderRenderable();

    $completionState = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const record = data.ensureWirdDailyRecord();
  const range = data.wirdRangeState(record);

  return {
    completedPage: Number(data.wirdTargetPageFromStep(range.maxStep, record) ?? 0),
    finalStep: Number(range.maxStep ?? 0),
  };
})()
JS,
        ),
    );

    expect($completionState)->toBeArray();
    $completedPage = (int) ($completionState['completedPage'] ?? 0);
    $finalStep = (int) ($completionState['finalStep'] ?? 0);
    expect($completedPage)->toBeGreaterThan(0);

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $completedPage,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.wirdCurrentStep(data.ensureWirdDailyRecord()) ?? 0)'),
        $finalStep,
        8_000,
    );
    $assertReaderRenderable();

    $page->script(
        "window.dispatchEvent(new CustomEvent('quran-go-next', { detail: { source: 'test-complete-exit' } }));",
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), false, 8_000);
    $assertReaderRenderable();

    safeClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 8_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $completedPage,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Boolean(data.ensureWirdDailyRecord()?.completed)'),
        true,
        8_000,
    );
    $assertReaderRenderable();

    $duplicateCycleState = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const record = data.ensureWirdDailyRecord();
  const maxPage = Math.max(1, Number(data.resolveReaderMaxPage?.() ?? data.maxPage ?? 604));

  record.startAbsolutePage = 1;
  record.requiredPages = maxPage * 4;
  record.currentStep = Math.max(0, record.requiredPages - 1);
  record.progressStep = record.currentStep;
  record.completed = true;
  record.updatedAt = Date.now();
  data.wirdDailyRecord = record;
  data.wirdBrowseStep = record.currentStep;

  const repeatedPage = Number(data.wirdTargetPageFromStep(0, record) ?? 0);
  const repeatedCycleStep = maxPage;
  const repeatedCyclePage = Number(data.wirdTargetPageFromStep(repeatedCycleStep, record) ?? 0);

  return {
    repeatedPage,
    repeatedCyclePage,
    resolvedNearStart: Number(
      data.wirdStepForPage(repeatedPage, record, { preferredStep: 0 }) ?? -1,
    ),
    resolvedNearCycle: Number(
      data.wirdStepForPage(repeatedCyclePage, record, { preferredStep: repeatedCycleStep }) ?? -1,
    ),
    repeatedCycleStep,
  };
})()
JS,
        ),
    );

    expect($duplicateCycleState)->toBeArray();
    expect((int) ($duplicateCycleState['repeatedPage'] ?? 0))->toBeGreaterThan(0);
    expect((int) ($duplicateCycleState['repeatedPage'] ?? 0))
        ->toBe((int) ($duplicateCycleState['repeatedCyclePage'] ?? -1));
    expect((int) ($duplicateCycleState['resolvedNearStart'] ?? -1))->toBe(0);
    expect((int) ($duplicateCycleState['resolvedNearCycle'] ?? -1))
        ->toBe((int) ($duplicateCycleState['repeatedCycleStep'] ?? -1));

    $page->assertNoJavaScriptErrors();
});

it('shows logical wird counter values with morph animations for multi-khatma daily mode', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page, true);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    safeClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout(
        $page,
        'Boolean(document.querySelector("#support-unlock-modal"))',
        true,
        6_000,
    );
    $page->script(
        <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
    );
    waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Boolean(data.isSupportLockActive())'),
        false,
        8_000,
    );

    safeClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 8_000);
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const indicator = document.querySelector('[data-quran-mushaf-page-indicator]');
  if (!(indicator instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(indicator);
  return styles.display === 'none' || Number.parseFloat(styles.opacity || '1') === 0;
})()
JS,
        true,
        8_000,
    );

    $counterState = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  data.wirdFrequencyMode = data.normalizeWirdFrequencyMode(1, 1);
  data.wirdKhatmatTarget = data.normalizeWirdKhatmatTarget(2, 2, {
    frequencyMode: data.wirdFrequencyMode,
  });
  const record = data.ensureWirdDailyRecord({ forceRebuild: true });
  data.wirdDailyRecord = record;
  data.wirdBrowseStep = null;
  data.syncWirdSliderVisualStep(record);

  const slider = document.querySelector('.quran-page-slider');

  return {
    maxPage: Number(data.resolveReaderMaxPage?.() ?? data.maxPage ?? 0),
    requiredPages: Number(record?.requiredPages ?? 0),
    counterMax: Number(data.pageCounterMaxDisplayValue?.() ?? 0),
    counterCurrent: Number(data.pageCounterCurrentDisplayValue?.() ?? 0),
    counterDigits: Number(data.pageCounterDigitLength?.() ?? 0),
    sliderMax: Number(data.sliderMax?.() ?? slider?.max ?? 0),
  };
})()
JS,
        ),
    );

    expect($counterState)->toBeArray();
    $maxPage = (int) ($counterState['maxPage'] ?? 0);
    $requiredPages = (int) ($counterState['requiredPages'] ?? 0);
    expect($maxPage)->toBeGreaterThan(0);
    expect($requiredPages)->toBe($maxPage * 2);
    expect((int) ($counterState['counterMax'] ?? 0))->toBe($requiredPages);
    expect((int) ($counterState['counterCurrent'] ?? 0))->toBe(1);
    expect((int) ($counterState['counterDigits'] ?? 0))->toBeGreaterThanOrEqual(4);
    expect((int) ($counterState['sliderMax'] ?? -1))->toBe($requiredPages - 1);

    $page->script(quranReaderCommandScript("void data.stepWird('next', 'test-logical-counter-next');"));

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageCounterCurrentDisplayValue() ?? 0)'),
        2,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.pageCounterPulse?.hasChanges) && Number((data.pageCounterPulse?.segments ?? []).length || 0) >= 4',
        ),
        true,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        <<<'JS'
(() => {
  const indicator = document.querySelector('[data-quran-mushaf-page-indicator]');
  if (!(indicator instanceof HTMLElement)) {
    return false;
  }

  const styles = window.getComputedStyle(indicator);
  return styles.display !== 'none' && styles.visibility !== 'hidden' && Number.parseFloat(styles.opacity || '1') > 0;
})()
JS,
        true,
        8_000,
    );

    $page->assertNoJavaScriptErrors();
});

it('restores persisted wird settings from user overrides after refresh', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    safeBrowserResize($page, 375, 812);
    $page->script(
        <<<'JS'
(() => {
  window.__disableJsErrorReporting = true;
  localStorage.clear();
  sessionStorage.clear();
  window.history.replaceState({}, document.title, window.location.pathname + window.location.search);
})()
JS,
    );
    $page->refresh();
    waitForAlpineReady($page);
    applyTestSpeedups($page);
    $page->script('window.__disableJsErrorReporting = true;');
    enableMobileContext($page);

    if ($page->script('window.location.hash') !== '#main-menu') {
        setHashOnly($page, '#main-menu', true, true);
    }

    if ($page->script(homeDataScript('data.activeView')) !== 'main-menu') {
        forceHomeView($page, 'main-menu');
    }

    if ($page->script('JSON.parse(localStorage.getItem("app-active-view"))') !== 'main-menu') {
        $page->script('localStorage.setItem("app-active-view", JSON.stringify("main-menu"));');
    }

    waitForScript($page, 'window.location.hash', '#main-menu');
    waitForScript($page, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    $page->script(
        <<<'JS'
localStorage.setItem('athkar-settings-user-overrides-v1', JSON.stringify({
  quran_wird_frequency_mode: 1,
  quran_wird_khatmat_target: 2,
}));
JS,
    );
    $persistedOverridesBeforeRefresh = $page->script(
        <<<'JS'
JSON.parse(localStorage.getItem('athkar-settings-user-overrides-v1') ?? 'null')
JS,
    );

    expect($persistedOverridesBeforeRefresh)->toBeArray();
    expect((int) ($persistedOverridesBeforeRefresh['quran_wird_frequency_mode'] ?? -1))->toBe(1);
    expect((int) ($persistedOverridesBeforeRefresh['quran_wird_khatmat_target'] ?? -1))->toBe(2);

    $seededProgressBeforeRefresh = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  data.wirdFrequencyMode = data.normalizeWirdFrequencyMode(1, 1);
  data.wirdKhatmatTarget = data.normalizeWirdKhatmatTarget(2, 2, {
    frequencyMode: data.wirdFrequencyMode,
  });
  const record = data.ensureWirdDailyRecord({ forceRebuild: true });
  const targetStep = Math.max(0, Math.min(Number(record?.requiredPages ?? 1) - 1, 7));

  record.currentStep = targetStep;
  record.progressStep = targetStep;
  record.completed = false;
  record.updatedAt = Date.now();
  data.wirdDailyRecord = record;
  data.reconcileWirdNextAbsolutePage(record);
  data.persistWirdState();

  return {
    currentStep: Number(record.currentStep ?? -1),
    progressStep: Number(record.progressStep ?? -1),
  };
})()
JS,
        ),
    );

    expect($seededProgressBeforeRefresh)->toBeArray();
    expect((int) ($seededProgressBeforeRefresh['currentStep'] ?? -1))->toBe(7);
    expect((int) ($seededProgressBeforeRefresh['progressStep'] ?? -1))->toBe(7);

    $page->refresh();
    waitForAlpineReady($page);
    $persistedOverridesAfterRefresh = $page->script(
        <<<'JS'
JSON.parse(localStorage.getItem('athkar-settings-user-overrides-v1') ?? 'null')
JS,
    );
    expect($persistedOverridesAfterRefresh)->toBeArray();
    expect((int) ($persistedOverridesAfterRefresh['quran_wird_frequency_mode'] ?? -1))->toBe(1);
    expect((int) ($persistedOverridesAfterRefresh['quran_wird_khatmat_target'] ?? -1))->toBe(2);

    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 8_000);

    $restoredState = $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const record = data.ensureWirdDailyRecord({ forceRebuild: true });
  const maxPage = Number(data.resolveReaderMaxPage?.() ?? data.maxPage ?? 0);

  return {
    frequencyMode: Number(data.wirdFrequencyMode ?? -1),
    khatmatTarget: Number(data.wirdKhatmatTarget ?? -1),
    requiredPages: Number(record?.requiredPages ?? 0),
    maxPage,
    currentStep: Number(record?.currentStep ?? -1),
    progressStep: Number(record?.progressStep ?? -1),
  };
})()
JS,
        ),
    );

    expect($restoredState)->toBeArray();
    expect((int) ($restoredState['frequencyMode'] ?? -1))->toBe(1);
    expect((int) ($restoredState['khatmatTarget'] ?? -1))->toBe(2);
    expect((int) ($restoredState['maxPage'] ?? 0))->toBeGreaterThan(0);
    expect((int) ($restoredState['requiredPages'] ?? 0))
        ->toBe((int) ($restoredState['maxPage'] ?? 0) * 2);
    expect((int) ($restoredState['currentStep'] ?? -1))->toBe(7);
    expect((int) ($restoredState['progressStep'] ?? -1))->toBe(7);

    $page->assertNoJavaScriptErrors();
});

it('keeps wird committed progress monotonic and completion badge sticky while browsing back', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);

    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForScript($page, 'window.location.hash', '#quran-app-tilawa');
    waitForQuranReaderVisible($page);
    waitForScript($page, quranReaderDataScript('data.ready && data.mushafLines.length > 0'), true);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.isFittingPage'), false, 6_000);

    scriptClick($page, '[data-quran-wird-toggle]');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript(
            'Boolean(data.wirdModeActive) || Boolean(document.querySelector("#support-unlock-modal"))',
        ),
        true,
        6_000,
    );
    $unlockModalVisible = (bool) $page->script('Boolean(document.querySelector("#support-unlock-modal"))');

    if ($unlockModalVisible) {
        $page->script(
            <<<'JS'
(() => {
  const buttons = Array.from(document.querySelectorAll('#support-unlock-modal button'));
  const bypassButton = buttons.find((button) =>
    String(button.textContent ?? '').includes('أشهد الله أني لا أستطيع دعمكم الآن')
  );

  if (!(bypassButton instanceof HTMLButtonElement)) {
    return false;
  }

  bypassButton.click();

  return true;
})()
JS,
        );
        waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
        waitForScriptWithTimeout(
            $page,
            quranReaderDataScript('Boolean(data.isSupportLockActive())'),
            false,
            8_000,
        );
        $page->script(
            quranReaderCommandScript(
                <<<'JS'
if (!data.wirdModeActive) {
  if (typeof data.enterWirdMode === 'function') {
    void data.enterWirdMode();
  } else {
    void data.toggleWirdMode();
  }
}
JS,
            ),
        );
    }

    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.wirdModeActive)'), true, 6_000);

    $initialPage = (int) $page->script(quranReaderDataScript('Number(data.pageNumber ?? 0)'));
    $initialCommittedPercent = (int) $page->script(quranReaderDataScript('Number(data.wirdProgressPercent() ?? 0)'));

    safeClick($page, '.quran-bottom-strip-nav-next');
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $initialPage + 1, 6_000);

    $committedAfterNext = (int) $page->script(quranReaderDataScript('Number(data.wirdProgressPercent() ?? 0)'));
    expect($committedAfterNext)->toBeGreaterThan($initialCommittedPercent);

    safeClick($page, '.quran-bottom-strip-nav-prev');
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), $initialPage, 6_000);

    $committedAfterPrev = (int) $page->script(quranReaderDataScript('Number(data.wirdProgressPercent() ?? 0)'));
    expect($committedAfterPrev)->toBe($committedAfterNext);

    $completionPage = (int) $page->script(
        quranReaderDataScript(
            <<<'JS'
(() => {
  const record = data.ensureWirdDailyRecord();
  const range = data.wirdRangeState(record);
  const completedStep = Number(range.maxStep ?? 0);

  data.markWirdAsCompleted(record);
  data.wirdModeActive = true;
  data.wirdBrowseStep = completedStep;
  data.syncWirdSliderVisualStep(record);

  const targetPage = Number(data.wirdTargetPageFromStep(completedStep, record) ?? 0);
  if (targetPage > 0 && typeof data.goToPageFromChevron === 'function') {
    void data.goToPageFromChevron(targetPage, {
      source: 'test-wird-monotonic-complete-page',
      commitNow: true,
      settleDelayMs: 0,
    });
  } else if (targetPage > 0 && typeof data.dispatchPageNavigationRequest === 'function') {
    data.dispatchPageNavigationRequest(targetPage, 'test-wird-monotonic-complete-page');
  }

  return targetPage;
})()
JS,
        ),
    );
    expect($completionPage)->toBeGreaterThan(0);
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $completionPage,
        8_000,
    );

    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.ensureWirdDailyRecord()?.completed)'), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.wirdProgressPercentLabel()'), 'مكتمل', 6_000);

    $committedAtCompletion = (int) $page->script(quranReaderDataScript('Number(data.wirdProgressPercent() ?? 0)'));
    $browseBeforeBack = (int) $page->script(quranReaderDataScript('Number(data.wirdBrowsePercent() ?? 0)'));
    $expectedPageAfterBack = (int) $page->script(quranReaderDataScript(<<<'JS'
(() => {
    const record = data.ensureWirdDailyRecord();
    const range = data.wirdRangeState(record);
    const nextBrowseStep = Math.max(0, data.wirdBrowseStepValue(record) - 1);

    return Number(data.absolutePageToPageNumber(range.startAbsolutePage + nextBrowseStep) ?? 0);
})()
JS));

    safeClick($page, '.quran-bottom-strip-nav-prev');
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data.pageNumber ?? 0)'),
        $expectedPageAfterBack,
        6_000,
    );

    waitForScriptWithTimeout($page, quranReaderDataScript('Boolean(data.ensureWirdDailyRecord()?.completed)'), true, 6_000);
    waitForScriptWithTimeout($page, quranReaderDataScript('data.wirdProgressPercentLabel()'), 'مكتمل', 6_000);

    $committedAfterBackWhileComplete = (int) $page->script(quranReaderDataScript('Number(data.wirdProgressPercent() ?? 0)'));
    $browseAfterBack = (int) $page->script(quranReaderDataScript('Number(data.wirdBrowsePercent() ?? 0)'));

    expect($committedAfterBackWhileComplete)->toBe($committedAtCompletion)
        ->and($browseAfterBack)->toBeLessThan($browseBeforeBack);

    $page->assertNoJavaScriptErrors();
});
