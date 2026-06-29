<?php

declare(strict_types=1);
use App\Models\User;

use function Pest\Laravel\actingAs;

it('renders the home shell, validates core controls, and persists color scheme behavior', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);

    $page->assertScript("document.querySelectorAll('[data-main-menu-item]').length", 9);

    waitForScript($page, mainMenuDataScript('data.isTouchDevice !== null'), true);
    $isTouch = $page->script(mainMenuDataScript('data.isTouchDevice'));

    $captions = [
        'الأذكار',
        'الأدعية',
        'المعروف',
        'السنن',
        'الكتاب',
        'الآثار',
        'التعلم',
        'الدواء',
        'المحفوظات',
    ];

    foreach ($captions as $caption) {
        $selector = "[data-main-menu-item][data-caption=\"{$caption}\"]";

        if ($isTouch) {
            tapMainMenuItem($page, $caption);
        } else {
            $page->hover($selector);
        }

        waitForScript($page, mainMenuDataScript('data.currentCaption'), $caption);
    }

    $page
        ->assertVisible('[data-stack-item][x-data] [data-testid="control-panel-button"]')
        ->assertVisible('[data-testid="color-scheme-switch-button"]');

    waitForScript($page, <<<'JS'
(() => {
  const lightLayer = document.querySelector('[data-testid="main-menu-bg-light-layer"]');
  const darkLayer = document.querySelector('[data-testid="main-menu-bg-dark-layer"]');

  if (!lightLayer || !darkLayer) {
    return false;
  }

  const hasRuntimeBlurUtility =
    lightLayer.innerHTML.includes('blur-md') ||
    darkLayer.innerHTML.includes('blur-md');

  const usesPreBlurredAssets =
    lightLayer.innerHTML.includes('morning-blurred.webp') &&
    darkLayer.innerHTML.includes('night-blurred.webp');

  return usesPreBlurredAssets && !hasRuntimeBlurUtility;
})()
JS, true);

    waitForScript($page, 'Boolean(window.Livewire)', true);
    $page->script("window.Livewire.dispatchTo('color-scheme-switcher', 'color-scheme-toggled', { isDarkModeOn: false });");

    hashAction($page, '#toggle-color-scheme', false);
    waitForScript($page, "Boolean(window.Alpine?.store?.('colorScheme')?.isDarkModeOn)", true);
    waitForScriptWithTimeout($page, "document.documentElement.classList.contains('color-scheme-switching')", false, 2500);

    openControlPanelModal($page);

    waitForScript($page, <<<'JS'
(() => {
  const icon = document.querySelector('.fi-modal-window img[alt="Muttasiq application icono"]');
  const src = icon?.getAttribute('src');
  return Boolean(src && src.includes('icon-dark.png'));
})()
JS, true);

    waitForScript($page, 'Boolean(window.Alpine && window.Alpine.store("colorScheme"))');
    waitForScript($page, homeDataScript('data.lock !== null'), true);

    $isDarkScript = 'window.Alpine.store("colorScheme").isDarkModeOn';

    $page->script('window.Alpine.store("colorScheme").isDark = false;');
    waitForScript($page, $isDarkScript, false);
    waitForScript($page, 'JSON.parse(localStorage.getItem("colorScheme_darkMode"))', false);

    $page->script('window.Alpine.store("colorScheme").toggle();');

    waitForScript($page, $isDarkScript, true);

    $page
        ->assertScript($isDarkScript, true)
        ->assertScript('JSON.parse(localStorage.getItem("colorScheme_darkMode"))', true);

    $page->script('window.location.reload();');
    waitForScript($page, 'Boolean(window.Alpine && window.Alpine.store("colorScheme"))');
    waitForScript($page, homeDataScript('data.lock !== null'), true);

    waitForScript($page, $isDarkScript, true);

    $page
        ->assertScript($isDarkScript, true)
        ->assertScript('JSON.parse(localStorage.getItem("colorScheme_darkMode"))', true);

    $page->script('window.Alpine.store("colorScheme").toggle();');

    waitForScript($page, $isDarkScript, false);

    $page
        ->assertScript($isDarkScript, false)
        ->assertScript('JSON.parse(localStorage.getItem("colorScheme_darkMode"))', false);

    $page->script('window.location.reload();');
    waitForScript($page, 'Boolean(window.Alpine && window.Alpine.store("colorScheme"))');
    waitForScript($page, homeDataScript('data.lock !== null'), true);

    waitForScript($page, $isDarkScript, false);

    $page->assertScript($isDarkScript, false);
});

it('migrates legacy raw active view storage before alpine boots', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);

    $page->script('localStorage.setItem("app-active-view", "main-menu");');
    $page->script('window.location.reload();');

    waitForScript($page, 'JSON.parse(localStorage.getItem("app-active-view"))', 'main-menu');
    waitForScript($page, homeDataScript('data.activeView'), 'main-menu');

    expect($page->script('JSON.parse(localStorage.getItem("app-active-view"))'))->toBe('main-menu');
});

it('updates athkar progress live when synced storage changes arrive', function () {
    actingAs(User::factory()->create());
    session()->put('auth.web_login_confirmed', true);

    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);

    waitForScript($page, 'Boolean(document.querySelector("[data-athkar-app-reader-root]"))', true);
    waitForScript($page, 'Boolean(document.querySelector("[data-testid=\\"main-menu-insights-trigger\\"]"))', true);

    $didUpdate = $page->script(<<<'JS'
(() => {
  const readerRoot = document.querySelector('[data-athkar-app-reader-root]');
  const reader = readerRoot && window.Alpine && window.Alpine.$data ? window.Alpine.$data(readerRoot) : null;

  if (!reader || typeof reader.athkarFor !== 'function') {
    return false;
  }

  const sabahList = reader.athkarFor('sabah');
  const nextProgress = JSON.parse(JSON.stringify(reader.progress || {}));
  if (!nextProgress.sabah || typeof nextProgress.sabah !== 'object') {
    nextProgress.sabah = { index: 0, counts: [], ids: [], activeId: null };
  }
  if (!nextProgress.masaa || typeof nextProgress.masaa !== 'object') {
    nextProgress.masaa = { index: 0, counts: [], ids: [], activeId: null };
  }
  nextProgress.sabah.ids = sabahList.map((item) => item && Object.prototype.hasOwnProperty.call(item, 'id') ? item.id : null);
  nextProgress.sabah.counts = Array.from(nextProgress.sabah.counts || []);
  nextProgress.sabah.counts[0] = Number(sabahList[0] && sabahList[0].count ? sabahList[0].count : 1);
  nextProgress.sabah.index = 0;
  nextProgress.sabah.activeId = nextProgress.sabah.ids.length > 0 ? nextProgress.sabah.ids[0] : null;

  const serialized = JSON.stringify(nextProgress);
  window.muttasiqDataBranch.applyUserBundle({
    'athkar-progress-v1': serialized,
  });

  return Number(reader.progress && reader.progress.sabah && reader.progress.sabah.counts ? reader.progress.sabah.counts[0] : 0) > 0;
})()
JS);

    expect($didUpdate)->toBeBool();

    waitForScript($page, <<<'JS'
(() => {
  const readerRoot = document.querySelector('[data-athkar-app-reader-root]');
  const reader = readerRoot && window.Alpine && window.Alpine.$data ? window.Alpine.$data(readerRoot) : null;

  return Number(reader && reader.progress && reader.progress.sabah && reader.progress.sabah.counts ? reader.progress.sabah.counts[0] : 0) > 0;
})()
JS, true);

    expect($page->script(<<<'JS'
(() => {
  const readerRoot = document.querySelector('[data-athkar-app-reader-root]');
  const reader = readerRoot && window.Alpine && window.Alpine.$data ? window.Alpine.$data(readerRoot) : null;

  return Number(reader && reader.progress && reader.progress.sabah && reader.progress.sabah.counts ? reader.progress.sabah.counts[0] : 0);
})()
JS))->toBeGreaterThan(0);
});

it('handles copyright panel visibility and opens updates tab from desktop and touch interactions', function () {
    $desktopPage = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($desktopPage);

    waitForScript($desktopPage, <<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;
  return Boolean(shell && data && data.isVisible === false);
})()
JS, true);

    $prepared = (bool) $desktopPage->script(<<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;
  const bp = window.Alpine?.store?.('bp');
  if (!data || !bp) {
    return false;
  }
  bp.hasTouch = false;
  data.waitDuration = 120;
  data.visibleDuration = 80;
  data.queueNextReveal(20);
  return true;
})()
JS);

    expect($prepared)->toBeTrue();

    waitForScript($desktopPage, <<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;
  return Boolean(data?.isVisible === true);
})()
JS, true);

    waitForScript($desktopPage, <<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;
  return Boolean(data?.isVisible === false);
})()
JS, true);

    $desktopPage->script(<<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  window.__copyrightHoverAt = Date.now();
  shell?.dispatchEvent(new Event('mouseenter', { bubbles: true }));
  return true;
})()
JS);

    waitForScript($desktopPage, <<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;
  return Boolean(data?.isVisible === true && (Date.now() - (window.__copyrightHoverAt ?? 0)) >= 170);
})()
JS, true);

    $desktopPage->script(<<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  shell?.dispatchEvent(new Event('mouseleave', { bubbles: true }));
  return true;
})()
JS);

    waitForScript($desktopPage, <<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;
  return Boolean(data?.isVisible === false);
})()
JS, true);

    waitForScript($desktopPage, 'Boolean(window.Livewire)', true);
    $desktopPage->script('window.dispatchEvent(new CustomEvent("open-control-panel-modal", { detail: { tab: "updates" } }));');

    waitForScript($desktopPage, 'Boolean(document.querySelector(".fi-modal-window"))', true);
    waitForScript($desktopPage, <<<'JS'
(() => {
  const activeTab = document.querySelector('.fi-modal-window .fi-tabs .fi-tabs-item.fi-active .fi-tabs-item-label');
  if (!activeTab) {
    return false;
  }
  const label = (activeTab.textContent ?? '').replace(/\s+/g, ' ').trim();
  return label.includes('تحديثات');
})()
JS, true);

    $mobilePage = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($mobilePage, true);

    waitForScript($mobilePage, 'Boolean(window.Livewire)', true);

    $keptVisible = (bool) $mobilePage->script(<<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;
  if (!shell || !data) {
    return false;
  }

  data.clearLoopTimers();
  data.isVisible = false;
  data.isTouching = false;
  data.visibleDuration = 300;

  shell.dispatchEvent(new Event('touchstart', { bubbles: true, cancelable: true }));
  shell.dispatchEvent(new Event('touchend', { bubbles: true, cancelable: true }));

  return data.isVisible === true;
})()
JS);

    expect($keptVisible)->toBeBool();

    $mobilePage->click('[data-testid="copyright-version-button"]');

    waitForScript($mobilePage, 'Boolean(document.querySelector(".fi-modal-window"))', true);
    waitForScript($mobilePage, <<<'JS'
(() => {
  const activeTab = document.querySelector('.fi-modal-window .fi-tabs .fi-tabs-item.fi-active .fi-tabs-item-label');
  if (!activeTab) {
    return false;
  }
  const label = (activeTab.textContent ?? '').replace(/\s+/g, ' ').trim();
  return label.includes('تحديثات');
})()
JS, true);

    waitForScript($mobilePage, 'Boolean(document.querySelector(\'[data-testid="copyright-version-panel"]\'))', true);

    /** @var array<string, float|int>|null $snapshot */
    $snapshot = $mobilePage->script(<<<'JS'
(() => {
  const shell = document.querySelector('[data-testid="copyright-version-shell"]');
  const panel = document.querySelector('[data-testid="copyright-version-panel"]');
  const data = shell && window.Alpine?.$data ? window.Alpine.$data(shell) : null;

  if (!panel || !data) {
    return null;
  }

  data.clearLoopTimers();
  data.isVisible = true;

  const rect = panel.getBoundingClientRect();
  const style = getComputedStyle(panel);

  return {
    leftInset: rect.left,
    rightInset: window.innerWidth - rect.right,
    width: rect.width,
    viewportWidth: window.innerWidth,
    fontSize: Number.parseFloat(style.fontSize),
    scrollWidth: panel.scrollWidth,
    clientWidth: panel.clientWidth,
  };
})()
JS);

    expect($snapshot)->toBeArray();
    expect((float) ($snapshot['fontSize'] ?? 0.0))->toBeLessThanOrEqual(12.8);
    expect((float) ($snapshot['width'] ?? 0.0))
        ->toBeLessThanOrEqual(((float) ($snapshot['viewportWidth'] ?? 0.0) * 0.9) + 1.0);
    expect((float) ($snapshot['leftInset'] ?? 0.0))
        ->toBeGreaterThanOrEqual(((float) ($snapshot['viewportWidth'] ?? 0.0) * 0.04) - 1.0);
    expect((float) ($snapshot['rightInset'] ?? 0.0))
        ->toBeGreaterThanOrEqual(((float) ($snapshot['viewportWidth'] ?? 0.0) * 0.04) - 1.0);
    expect((int) ($snapshot['scrollWidth'] ?? 0))
        ->toBeLessThanOrEqual((int) ($snapshot['clientWidth'] ?? 0) + 1);
});

it('keeps patch version updates on the last active view but resets major and minor updates to the main menu', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);

    $cases = [
        [
            'currentVersion' => '2.4.2',
            'storedVersion' => '2.4.1',
            'expectedView' => 'quran-app-tilawa',
        ],
        [
            'currentVersion' => '2.5.0',
            'storedVersion' => '2.4.2',
            'expectedView' => 'main-menu',
        ],
    ];

    foreach ($cases as $case) {
        setLocalStorageValue($page, 'muttasiq-app-version-last-seen-v1', $case['storedVersion']);

        $shouldResetStartupView = (bool) $page->script(js_template(<<<'JS'
(() => {
  return Boolean(
    window.appVersionRouting?.syncStoredAppVersion({{currentVersion}})?.shouldResetStartupView,
  );
})()
JS, ['currentVersion' => $case['currentVersion']]));

        forceHomeView(
            $page,
            $shouldResetStartupView ? 'main-menu' : 'quran-app-tilawa',
            false,
        );

        waitForScript($page, homeDataScript('data.activeView'), $case['expectedView']);

        expect($page->script('JSON.parse(localStorage.getItem("app-active-view"))'))
            ->toBe($case['expectedView']);
        expect($page->script('JSON.parse(localStorage.getItem("muttasiq-app-version-last-seen-v1"))'))
            ->toBe($case['currentVersion']);
    }
});
