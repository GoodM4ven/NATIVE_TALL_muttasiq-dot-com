<?php

declare(strict_types=1);

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

    $page->refresh();
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

    $page->refresh();
    waitForScript($page, 'Boolean(window.Alpine && window.Alpine.store("colorScheme"))');
    waitForScript($page, homeDataScript('data.lock !== null'), true);

    waitForScript($page, $isDarkScript, false);

    $page->assertScript($isDarkScript, false);
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

    expect($keptVisible)->toBeTrue();

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

it('maintains quick stack layout and navigation resilience across reload, modal, and color-scheme flows', function () {
    $desktopPage = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($desktopPage);

    waitForScript($desktopPage, 'window.location.hash', '#main-menu');
    waitForScript($desktopPage, 'JSON.parse(localStorage.getItem("app-active-view"))', 'main-menu');

    $snapshot = null;
    $lastResult = null;

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        /** @var array<string, mixed>|null $result */
        $result = $desktopPage->script(<<<'JS'
(() => {
  const bp = window.Alpine?.store?.('bp');
  const stackRoot = document.querySelector('[x-ref="stack"]')?.parentElement ?? null;

  if (!bp || !stackRoot) {
    return { ready: false, reason: !bp ? 'missing-bp' : 'missing-stack-root' };
  }

  document.documentElement.style.setProperty('--breakpoint', 'base');
  bp.current = 'base';
  stackRoot.dataset.respectingStack = 'true';
  window.dispatchEvent(new Event('resize'));

  const allItems = Array.from(document.querySelectorAll('[data-stack-item]'));
  if (allItems.length < 3) {
    return { ready: false, reason: 'not-enough-items', allCount: allItems.length };
  }

  allItems[0].hidden = true;

  const stackData = window.Alpine?.$data
    ? window.Alpine.$data(stackRoot)
    : (stackRoot.__x?.$data ?? null);

  if (!stackData || typeof stackData.setRespectingStack !== 'function' || typeof stackData.updateLayout !== 'function') {
    return { ready: false, reason: 'missing-stack-data' };
  }

  stackData.setRespectingStack();

  if (!stackData.respectingStack) {
    return { ready: false, reason: 'respecting-stack-disabled' };
  }

  stackData.updateLayout();

  const visibleItems = allItems.filter((item) => !item.hidden && getComputedStyle(item).display !== 'none');

  if (visibleItems.length < 2) {
    return { ready: false, reason: 'not-enough-visible-items', visibleCount: visibleItems.length };
  }

  const transforms = visibleItems.map((item) => {
    const value = String(item.style.transform ?? '');
    const match = value.match(/translateX\((-?\d+(?:\.\d+)?)rem\)/);

    return match ? Number(match[1]) : null;
  });

  if (transforms.some((value) => value === null)) {
    return false;
  }

  const maxOffset = Math.max(...transforms.map((value) => Math.abs(value)));
  const expectedMaxOffset = (visibleItems.length - 1) * 1.2;

  return {
    ready: true,
    allCount: allItems.length,
    visibleCount: visibleItems.length,
    hasNullTransforms: transforms.some((value) => value === null),
    maxOffset,
    expectedMaxOffset,
  };
})()
JS);

        $lastResult = $result;

        if (is_array($result) && ($result['ready'] ?? false) === true) {
            $snapshot = $result;

            break;
        }

        usleep(testRetrySleepMicroseconds());
    }

    expect($snapshot)->toBeArray('Last snapshot payload: '.var_export($lastResult, true));
    expect((bool) ($snapshot['ready'] ?? false))->toBeTrue();
    expect((int) ($snapshot['allCount'] ?? 0))->toBeGreaterThan((int) ($snapshot['visibleCount'] ?? 0));
    expect((bool) ($snapshot['hasNullTransforms'] ?? true))->toBeFalse();
    expect((float) ($snapshot['maxOffset'] ?? 0.0))
        ->toBeLessThanOrEqual((float) ($snapshot['expectedMaxOffset'] ?? 0.0) + 0.15);

    $mobilePage = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($mobilePage, true);
    openAthkarGate($mobilePage, true);

    $mobilePage->refresh();

    waitForAlpineReady($mobilePage);
    applyTestSpeedups($mobilePage);
    enableMobileContext($mobilePage);
    waitForGateVisible($mobilePage);
    waitForScriptWithTimeout($mobilePage, quickStackLayoutReadyScript(), true, 3_000);

    openAthkarReader($mobilePage, 'sabah', true);

    $mobilePage->refresh();

    waitForAlpineReady($mobilePage);
    applyTestSpeedups($mobilePage);
    enableMobileContext($mobilePage);
    waitForReaderVisible($mobilePage);
    waitForScriptWithTimeout($mobilePage, quickStackLayoutReadyScript(), true, 3_000);

    waitForScript($mobilePage, "Boolean(window.Alpine?.store?.('colorScheme'))", true);
    waitForScript($mobilePage, "window.Alpine.store('colorScheme').isDarkModeOn", false);

    $tapSnapshot = $mobilePage->script(<<<'JS'
(() => {
  const button = document.querySelector('[data-testid="color-scheme-switch-button"]');
  const root = document.querySelector('[x-ref="stack"]')?.parentElement ?? null;

  if (!button || !root || !window.Alpine) {
    return null;
  }

  const click = () => {
    button.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
  };

  click();
  click();
  click();
  click();

  const data = window.Alpine.$data ? window.Alpine.$data(root) : (root.__x?.$data ?? null);

  return {
    isDarkModeOn: window.Alpine.store('colorScheme').isDarkModeOn,
    isQuickStackOpen: data?.isQuickStackOpen ?? null,
    isInteractionLocked: data?.isInteractionLocked ?? null,
  };
})()
JS);

    expect($tapSnapshot)->toBeArray();
    expect($tapSnapshot['isDarkModeOn'] ?? null)->toBeTrue();
    expect($tapSnapshot['isQuickStackOpen'] ?? null)->toBeTrue();
    expect($tapSnapshot['isInteractionLocked'] ?? null)->toBeTrue();

    waitForScriptWithTimeout($mobilePage, quickStackDataScript('data.isInteractionLocked'), false, 1_500);
    waitForScript($mobilePage, quickStackDataScript('data.isQuickStackOpen'), true);
    waitForScript($mobilePage, "window.Alpine.store('colorScheme').isDarkModeOn", true);

    openControlPanelModal($mobilePage);
    $closed = (bool) $mobilePage->script(<<<'JS'
(() => {
  const closeButton = document.querySelector('.fi-modal-window [aria-label="Close"], .fi-modal-window [aria-label="إغلاق"], .fi-modal-close-btn');
  closeButton?.click();
  return Boolean(closeButton);
})()
JS);
    expect($closed)->toBeTrue();
    waitForScriptWithTimeout($mobilePage, modalClosedScript(), true, 3_000);
    waitForScriptWithTimeout($mobilePage, quickStackLayoutReadyScript(), true, 3_000);

    openAthkarGate($mobilePage, true);
    hashAction($mobilePage, '#toggle-color-scheme', false);

    waitForScript($mobilePage, 'window.location.hash === "" || window.location.hash === "#"', true);
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'athkar-app-gate');

    $clicked = (bool) $mobilePage->script(<<<'JS'
(() => {
  const button = document.querySelector("div[data-stack-item][x-show*=\"!views['main-menu']\"] button");
  if (!button) {
    return false;
  }

  button.click();
  button.click();

  return true;
})()
JS);

    expect($clicked)->toBeTrue();

    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');
});
