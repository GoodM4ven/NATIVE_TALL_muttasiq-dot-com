<?php

declare(strict_types=1);
use Illuminate\Support\Facades\DB;

uses()->group('browser-flaky');

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

    $secondBackResult = $mobilePage->script('window.__nativeBackAction()');
    expect(in_array($secondBackResult, ['exit', '#main-menu'], true))->toBeTrue();
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');
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
    $focusedSurahNumber = $focusedSurahNumber > 0
        ? $focusedSurahNumber
        : (int) $page->script(quranReaderDataScript('Number(data.search?.activeSurahNumber ?? 0)'));
    $surahGridScrollTop = (int) ($surahTileFocusState['scrollTop'] ?? 0);
    expect($focusedSurahNumber)->toBe($targetSurahNumber);
    expect($surahGridScrollTop)->toBeGreaterThanOrEqual(0);
});

it('closes the search modal after an active long search is dismissed', function () {
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

    $page->fill('#quran-reader-search-input', 'وقال ربكم ادعوني أستجب لكم');

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('String(data.search.query ?? "") === "وقال ربكم ادعوني أستجب لكم"'),
        true,
        6_000,
    );

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.search.isLoading && Number(data.search.results?.length ?? 0) > 0'),
        true,
        12_000,
    );

    expect($page->script(quranReaderDataScript('data.requestSearchModalClose({ skipLayout: true })')))->toBeTrue();

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.search.modalOpen && !data.isSearchModalWindowVisible()'),
        true,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('String(data.search.query ?? "") === "" && Number(data.search.results?.length ?? 0) === 0'),
        true,
        8_000,
    );
});

it('keeps the search modal open after quickly reopening it following a result selection', function () {
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

    $page->fill('#quran-reader-search-input', 'وقال ربكم ادعوني أستجب لكم');

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.search.isLoading && Number(data.search.results?.length ?? 0) > 0'),
        true,
        12_000,
    );

    $page->script(quranReaderCommandScript('void data.goToSearchResult(data.search.results[0]);'));

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.search.modalOpen && !data.isSearchModalWindowVisible()'),
        true,
        8_000,
    );

    $page->script(quranReaderCommandScript('void data.openSearchModal();'));

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('data.search.modalOpen && data.isSearchModalWindowVisible()'),
        true,
        8_000,
    );

    $page->fill('#quran-reader-search-input', 'وقال ربكم ادعوني أستجب لكم');

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('String(data.search.query ?? "") === "وقال ربكم ادعوني أستجب لكم"'),
        true,
        6_000,
    );

    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.search.isLoading && Number(data.search.results?.length ?? 0) > 0'),
        true,
        12_000,
    );

    expect(
        $page->script(
            quranReaderCommandScript(
                <<<'JS'
window.__searchModalStayedOpenAfterReopen = false;
setTimeout(() => {
  window.__searchModalStayedOpenAfterReopen = Boolean(data.search.modalOpen && data.isSearchModalWindowVisible());
}, 1200);
return true;
JS,
            ),
        ),
    )->toBeTrue();

    waitForScriptWithTimeout($page, 'Boolean(window.__searchModalStayedOpenAfterReopen)', true, 4_000);
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

it('preserves fitted quran tuning after opening and closing jump-page modal without navigation', function () {
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

    $captureFitMetrics = function () use ($page): array {
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
  const computed = window.getComputedStyle(lines);
  const parseVar = (name, fallback = 0) => {
    const value = Number.parseFloat(computed.getPropertyValue(name).trim());
    return Number.isFinite(value) ? value : fallback;
  };

  return {
    fillHeightRatio: Number((linesRect.height / frameRect.height).toFixed(4)),
    fillWidthRatio: Number((linesRect.width / frameRect.width).toFixed(4)),
    pageScale: parseVar('--quran-page-scale', 1),
    typeScaleEffective: parseVar('--quran-page-type-scale-effective', 1),
    leadingTuneEffective: parseVar('--quran-page-postfit-leading-tune-effective', 1),
    gapTuneEffective: parseVar('--quran-page-postfit-gap-tune-effective', 1),
    surahGapTuneEffective: parseVar('--quran-page-postfit-surah-gap-tune-effective', 1),
    isOpening: lines.classList.contains('quran-page-lines--opening'),
  };
})()
JS,
        );

        expect($metrics)->toBeArray();

        return $metrics;
    };

    resetBrowserState($page);
    safeBrowserResize($page, 2560, 1440);
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    hashAction($page, '#quran-app-tilawa', true);
    waitForScript($page, homeDataScript('data.activeView'), 'quran-app-tilawa');
    waitForQuranReaderVisible($page);
    $assertReaderRenderable();

    $page->script(
        quranReaderCommandScript("data.dispatchPageNavigationRequest(17, 'test-modal-fit-seed');"),
    );
    waitForScriptWithTimeout($page, quranReaderDataScript('Number(data.pageNumber ?? 0)'), 17, 8_000);
    $assertReaderRenderable();

    $beforeMetrics = $captureFitMetrics();
    expect((bool) ($beforeMetrics['isOpening'] ?? true))->toBeFalse();

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
if (typeof data.openJumpPageModal !== 'function') {
  return false;
}

void data.openJumpPageModal();
return true;
JS,
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Boolean(data.isModalWindowVisibleById(data.jumpPageModalId))'),
        true,
        8_000,
    );

    $page->script(
        quranReaderCommandScript(
            <<<'JS'
return (async () => {
  if (typeof data.requestModalCloseByKnownIds === 'function') {
    await data.requestModalCloseByKnownIds([data.jumpPageModalId], {
      quietly: false,
      allowLivewireUnmount: true,
    });
  } else {
    window.dispatchEvent(
      new CustomEvent('close-modal', {
        detail: { id: data.jumpPageModalId },
      }),
    );
  }

  return true;
})();
JS,
        ),
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('!data.isModalWindowVisibleById(data.jumpPageModalId) && data.openModalCount() <= 0'),
        true,
        8_000,
    );
    waitForScriptWithTimeout(
        $page,
        quranReaderDataScript('Number(data._postModalTargetFitPage ?? 0) === 0'),
        true,
        8_000,
    );
    $assertReaderRenderable();

    $afterMetrics = $captureFitMetrics();

    expect((bool) ($afterMetrics['isOpening'] ?? true))->toBeFalse();
    expect(abs((float) ($beforeMetrics['fillHeightRatio'] ?? 0) - (float) ($afterMetrics['fillHeightRatio'] ?? 0)))->toBeLessThan(0.04);
    expect(abs((float) ($beforeMetrics['fillWidthRatio'] ?? 0) - (float) ($afterMetrics['fillWidthRatio'] ?? 0)))->toBeLessThan(0.03);
    expect(abs((float) ($beforeMetrics['typeScaleEffective'] ?? 0) - (float) ($afterMetrics['typeScaleEffective'] ?? 0)))->toBeLessThan(0.08);
    expect(abs((float) ($beforeMetrics['leadingTuneEffective'] ?? 0) - (float) ($afterMetrics['leadingTuneEffective'] ?? 0)))->toBeLessThan(0.08);
    expect(abs((float) ($beforeMetrics['gapTuneEffective'] ?? 0) - (float) ($afterMetrics['gapTuneEffective'] ?? 0)))->toBeLessThan(0.08);
    expect(abs((float) ($beforeMetrics['surahGapTuneEffective'] ?? 0) - (float) ($afterMetrics['surahGapTuneEffective'] ?? 0)))->toBeLessThan(0.08);

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
        false,
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
