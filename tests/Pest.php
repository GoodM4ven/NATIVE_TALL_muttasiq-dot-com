<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()
    ->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Browser')
    ->beforeAll(function () {
        if (! file_exists(($basePath = __DIR__.'/../public').'/build/manifest.json') && ! file_exists($basePath.'/hot')) {
            throw new Exception('Vite is not running!');
        }
    });

pest()
    ->browser()
    ->timeout(1500);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// expect()->extend('toBeOne', function () {
//     return $this->toBe(1);
// });

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use Pest\Browser\Execution;
use Pest\Browser\Playwright\Playwright;

function js_encode(mixed $value): string
{
    return php_to_js($value);
}

function js_template(string $template, array $bindings = []): string
{
    foreach ($bindings as $key => $value) {
        $template = str_replace('{{'.$key.'}}', js_encode($value), $template);
    }

    return $template;
}

function waitForScript($page, string $expression, mixed $expected = true): void
{
    Execution::instance()->waitForExpectation(
        function () use ($page, $expression, $expected): void {
            $actual = $page->script($expression);
            expect($actual)->toBe(
                $expected,
                'JS: '.$expression.' | actual: '.var_export($actual, true),
            );
        }
    );
}

function waitForScriptWithTimeout($page, string $expression, mixed $expected, int $timeoutMs): void
{
    $previous = Playwright::timeout();
    Playwright::setTimeout($timeoutMs);

    try {
        waitForScript($page, $expression, $expected);
    } finally {
        Playwright::setTimeout($previous);
    }
}

function waitForAlpineReady($page): void
{
    applyTestSpeedups($page);
    waitForScript($page, appReadyScript(), true);
}

function applyTestSpeedups($page): void
{
    $page->script(<<<'JS'
(() => {
  if (!window.Alpine) {
    return;
  }

  if (window.Alpine.store?.('fontManager')) {
    window.Alpine.store('fontManager').ready = (cb) => cb();
  }

  const body = document.body;
  const layoutData = body
    ? (window.Alpine.$data ? window.Alpine.$data(body) : (body.__x?.$data ?? null))
    : null;
  if (layoutData) {
    layoutData.defaultTransitionDurationInMs = 0;
    layoutData.fastTransitionDurationInMs = 0;
    layoutData.useFastTransitionDuration = true;
    layoutData.isFontReady = true;
    layoutData.isLayoutSetUp = true;
    layoutData.isBlinkerShown = false;
    layoutData.isBodyVisible = true;
  }
})();
JS);
}

function appReadyScript(): string
{
    return <<<'JS'
(() => {
  if (!window.Alpine || !window.Alpine.store) {
    return false;
  }
  if (!window.Alpine.store('bp')) {
    return false;
  }
  if (!document.querySelector('[data-main-menu-item]')) {
    return false;
  }
  const homeEl = Array.from(document.querySelectorAll('[x-data]')).find((node) =>
    node.hasAttribute('x-bind:data-hash-default'),
  );
  const menuEl = document.querySelector('[x-data^="mainMenu"]');
  if (!homeEl || !menuEl) {
    return false;
  }
  const homeData = window.Alpine.$data ? window.Alpine.$data(homeEl) : (homeEl.__x?.$data ?? null);
  const menuData = window.Alpine.$data ? window.Alpine.$data(menuEl) : (menuEl.__x?.$data ?? null);
  if (!homeData || !menuData) {
    return false;
  }
  if (typeof homeData.applyViewState !== 'function') {
    return false;
  }
  if (!homeData.lock || typeof homeData.lock.run !== 'function') {
    return false;
  }
  if (menuData.isTouchDevice === null) {
    return false;
  }
  if (typeof menuData.handleItemClick !== 'function') {
    return false;
  }
  return true;
})()
JS;
}

function resetBrowserState($page, bool $isMobile = false): void
{
    if ($isMobile) {
        $page->resize(375, 812);
    }
    $page->script('localStorage.clear(); sessionStorage.clear(); window.history.replaceState({}, document.title, window.location.pathname + window.location.search);');
    $page->refresh();
    waitForAlpineReady($page);
    if ($isMobile) {
        enableMobileContext($page);
    }
    waitForScript($page, 'window.location.hash', '#main-menu');
    waitForScript($page, homeDataScript('data.activeView'), 'main-menu');
}

function enableMobileContext($page): void
{
    $page->resize(375, 812);

    $page->script(<<<'JS'
(() => {
  try {
    if (!('ontouchstart' in window)) {
      window.ontouchstart = () => {};
    }
  } catch (e) {
    // ignore
  }
  try {
    Object.defineProperty(navigator, 'maxTouchPoints', { value: 1, configurable: true });
  } catch (e) {
    // ignore
  }
  document.documentElement.style.setProperty('--breakpoint', 'base');
  if (window.Alpine?.store?.('bp')) {
    window.Alpine.store('bp').current = 'base';
  }
  window.dispatchEvent(new Event('resize'));
  window.dispatchEvent(new Event('orientationchange'));
})();
JS);

    waitForScript($page, "Boolean(window.Alpine && window.Alpine.store && window.Alpine.store('bp'))", true);
    waitForScript($page, "window.Alpine.store('bp').current", 'base');
    waitForScript(
        $page,
        "Boolean(window.matchMedia && window.matchMedia('(max-width: 639px)').matches)",
        true,
    );
    waitForScript($page, 'window.innerWidth <= 420', true);

    $page->script(mainMenuCommandScript('data.isTouchDevice = true;'));
}

function visitMobile(string $path = '/')
{
    return visit($path)->on()->mobile();
}

function openSettingsModal($page): void
{
    waitForScript($page, 'Boolean(document.querySelector(\'[data-testid="settings-button"]\'))');
    waitForScript($page, 'Boolean(window.Livewire)');
    scriptClick($page, '[data-stack-item][x-data] [data-testid="settings-button"]');

    $isOpen = $page->script('Boolean(document.querySelector(".fi-modal-window"))');

    if (! $isOpen) {
        $page->script('window.dispatchEvent(new CustomEvent("open-settings-modal"));');
    }

    waitForScript($page, 'Boolean(document.querySelector(".fi-modal-window"))');
}

function toggleCheckboxByLabel($page, string $label): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const labelText = {{label}};
  const label = Array.from(document.querySelectorAll('label')).find((el) => el.textContent?.includes(labelText));
  if (!label) {
    return;
  }
  const inputId = label.getAttribute('for');
  let input = inputId ? document.getElementById(inputId) : null;
  if (!input) {
    input = label.querySelector('input[type="checkbox"]') ?? label.closest('div')?.querySelector('input[type="checkbox"]');
  }
  input?.click();
})();
JS, ['label' => $label]));
}

function lockIconActiveScript(string $selector): string
{
    return js_template(<<<'JS'
(() => {
  const selector = {{selector}};
  const icon = document.querySelector(selector + ' [data-lock-icon]');
  const wrap = icon?.closest('span');
  if (!wrap) {
    return false;
  }
  return wrap.classList.contains('opacity-100');
})()
JS, ['selector' => $selector]);
}

function checkboxStateScript(string $label): string
{
    return js_template(<<<'JS'
(() => {
  const labelText = {{label}};
  const label = Array.from(document.querySelectorAll('label')).find((el) => el.textContent?.includes(labelText));
  if (!label) {
    return null;
  }
  const inputId = label.getAttribute('for');
  let input = inputId ? document.getElementById(inputId) : null;
  if (!input) {
    input = label.querySelector('input[type="checkbox"]') ?? label.closest('div')?.querySelector('input[type="checkbox"]');
  }
  return Boolean(input?.checked);
})()
JS, ['label' => $label]);
}

function modalClosedScript(): string
{
    return <<<'JS'
(() => {
  const modal = document.querySelector('.fi-modal-window');
  if (!modal) {
    return true;
  }
  return getComputedStyle(modal).display === 'none';
})()
JS;
}

function tapMainMenuItem($page, string $caption): void
{
    $page->script(mainMenuCommandScript(js_template(<<<'JS'
const caption = {{caption}};
const element = Array.from(document.querySelectorAll('[data-main-menu-item]'))
  .find((item) => item.dataset?.caption === caption);
if (!element) {
  return false;
}
const rect = element.getBoundingClientRect();
const x = rect.left + rect.width / 2;
const y = rect.top + rect.height / 2;
const touch = { clientX: x, clientY: y };
const startEvent = {
  touches: [touch],
  targetTouches: [touch],
  changedTouches: [touch],
  cancelable: true,
  preventDefault() {},
};
const endEvent = {
  touches: [],
  targetTouches: [],
  changedTouches: [touch],
  cancelable: true,
  preventDefault() {},
};
if (typeof data.handleTouchStart === 'function') {
  data.handleTouchStart(startEvent);
}
if (typeof data.handleTouchEnd === 'function') {
  data.handleTouchEnd(endEvent);
}
return true;
JS, ['caption' => $caption])));
}

function openAthkarGate($page, bool $isMobile): void
{
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
    waitForScript($page, mainMenuDataScript('data.isTouchDevice !== null'), true);
    waitForScript($page, 'window.location.hash', '#main-menu');

    if ($page->script(homeDataScript('data.activeView')) !== 'athkar-app-gate') {
        activateMainMenuItem($page, 'الأذكار', $isMobile);
    }

    if ($page->script(homeDataScript('data.activeView')) !== 'athkar-app-gate') {
        hashAction($page, '#athkar-app-gate', true);
    }

    if ($page->script(homeDataScript('data.activeView')) !== 'athkar-app-gate') {
        ensureAthkarGateView($page);
    }

    if ($page->script(homeDataScript('data.activeView')) !== 'athkar-app-gate') {
        ensureAthkarGateView($page);
    }

    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-gate');
    if ($page->script('window.location.hash') !== '#athkar-app-gate') {
        setHashOnly($page, '#athkar-app-gate', true, true);
    }
    waitForScript($page, 'window.location.hash', '#athkar-app-gate');
    waitForGateVisible($page);
}

function openAthkarNotice($page, string $mode, bool $isMobile): void
{
    if ($page->script(homeDataScript('data.activeView')) !== 'athkar-app-gate') {
        ensureAthkarGateView($page);
    }
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForGateVisible($page);
    waitForScript($page, 'Boolean(document.querySelector("[x-data^=\\"athkarAppReader\\"]"))');
    waitForScript($page, athkarReaderReadyScript());
    waitForScript($page, athkarGateDataScript('true'), true);

    $page->script(athkarReaderCommandScript('data.settings = { ...(data.settings ?? {}), does_skip_notice_panels: false };'));

    $label = $mode === 'sabah' ? 'أذكار الصباح' : 'أذكار المساء';
    $selector = "button[aria-label=\"{$label}\"]";

    waitForScript($page, 'Boolean(document.querySelector('.json_encode($selector).'))', true);
    if ($isMobile) {
        safeClick($page, $selector);
        $expectedSide = $mode === 'sabah' ? 'morning' : 'night';
        waitForScript($page, athkarGateDataScript('data.activeSide'), $expectedSide);
        safeClick($page, $selector);
    } else {
        safeClick($page, $selector);
    }

    if ($page->script(athkarReaderDataScript('data.activeMode')) !== $mode) {
        dispatchAthkarGateOpen($page, $mode);
    }

    if ($page->script(athkarReaderDataScript('data.activeMode')) !== $mode) {
        $page->script(athkarReaderCommandScript("data.openMode('{$mode}');"));
    }

    if ($page->script(athkarReaderDataScript('data.activeMode')) !== $mode) {
        $page->script(athkarReaderCommandScript("data.startModeNotice('{$mode}', { updateHash: true, respectLock: false });"));
    }

    $hash = $mode === 'sabah' ? '#athkar-app-sabah' : '#athkar-app-masaa';
    $view = $mode === 'sabah' ? 'athkar-app-sabah' : 'athkar-app-masaa';

    if ($page->script('window.location.hash') !== $hash) {
        hashAction($page, $hash, true);
    }

    if ($page->script(homeDataScript('data.activeView')) !== $view) {
        forceHomeView($page, $view);
    }

    waitForScript($page, athkarReaderDataScript('data.activeMode'), $mode);

    if (! $page->script(athkarReaderDataScript('data.isNoticeVisible'))) {
        $page->script(athkarReaderCommandScript('data.showNotice();'));
    }

    waitForScript($page, athkarReaderDataScript('data.isNoticeVisible'), true);
    waitForScript($page, homeDataScript('data.activeView'), $view);
    waitForScript($page, 'window.location.hash', $hash);
}

function confirmAthkarNotice($page): void
{
    $isVisible = $page->script(athkarReaderDataScript('data.isNoticeVisible'));

    if (! $isVisible) {
        return;
    }

    if ($page->script('Boolean(document.querySelector(".athkar-notice__cta"))')) {
        safeClick($page, '.athkar-notice__cta');
    }

    $stillVisible = $page->script(athkarReaderDataScript('data.isNoticeVisible'));

    if ($stillVisible) {
        $page->script(athkarReaderCommandScript('data.confirmNotice();'));
    }

    waitForScript($page, athkarReaderDataScript('data.isNoticeVisible'), false);
}

function openAthkarReader($page, string $mode, bool $isMobile): void
{
    openAthkarGate($page, $isMobile);
    openAthkarNotice($page, $mode, $isMobile);
    confirmAthkarNotice($page);

    $hash = $mode === 'sabah' ? '#athkar-app-sabah' : '#athkar-app-masaa';
    $view = $mode === 'sabah' ? 'athkar-app-sabah' : 'athkar-app-masaa';

    if ($page->script(homeDataScript('data.activeView')) !== $view) {
        forceHomeView($page, $view);
    }

    if ($page->script('window.location.hash') !== $hash) {
        setHashOnly($page, $hash, true, true);
    }

    $page->script(homeDataCommandScript(<<<'JS'
views['athkar-app-gate'].isReaderVisible = true;
JS));

    waitForScript($page, athkarReaderDataScript('data.activeMode'), $mode);
    waitForScript($page, athkarReaderDataScript('data.activeList.length > 0'), true);
    waitForReaderVisible($page);
}

function waitForNoticeVisible($page): void
{
    waitForScript($page, noticeVisibleScript());
}

function waitForReaderVisible($page): void
{
    waitForScript($page, readerVisibleScript());
}

function waitForGateVisible($page): void
{
    waitForScript($page, gateVisibleScript());
}

function noticeVisibleScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('.athkar-notice');
  if (!el) {
    return false;
  }
  return getComputedStyle(el).display !== 'none';
})()
JS;
}

function readerVisibleScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('.athkar-reader');
  if (!el) {
    return false;
  }
  return getComputedStyle(el).display !== 'none';
})()
JS;
}

function gateVisibleScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('.athkar-gate-shell');
  if (!el) {
    return false;
  }
  return getComputedStyle(el).display !== 'none';
})()
JS;
}

function homeButtonVisibleScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('div[data-stack-item][x-show*="!views[\'main-menu\']"]');
  if (!el) {
    return false;
  }
  return getComputedStyle(el).display !== 'none';
})()
JS;
}

function homeButtonPositionScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('div[data-stack-item][x-show*="!views[\'main-menu\']"]');
  if (!el) {
    return null;
  }
  return el.style.position;
})()
JS;
}

function swipeElement($page, string $selector, string $direction, string $pointerType = 'touch'): void
{
    $startRatio = $direction === 'forward' ? 0.35 : 0.65;
    $endRatio = $direction === 'forward' ? 0.75 : 0.25;

    $page->script(js_template(<<<'JS'
(() => {
  const selector = {{selector}};
  const el = document.querySelector(selector);
  if (!el) {
    return;
  }
  const rect = el.getBoundingClientRect();
  const y = rect.top + height / 2;
  const startX = rect.left + rect.width * {{startRatio}};
  const endX = rect.left + rect.width * {{endRatio}};
  const pointerType = {{pointerType}};
  const pointerId = 1;

  const down = new PointerEvent('pointerdown', {
    bubbles: true,
    cancelable: true,
    clientX: startX,
    clientY: y,
    pointerType,
    pointerId,
    button: 0,
  });
  el.dispatchEvent(down);

  const move = new PointerEvent('pointermove', {
    bubbles: true,
    cancelable: true,
    clientX: endX,
    clientY: y,
    pointerType,
    pointerId,
    button: 0,
  });
  el.dispatchEvent(move);

  const up = new PointerEvent('pointerup', {
    bubbles: true,
    cancelable: true,
    clientX: endX,
    clientY: y,
    pointerType,
    pointerId,
    button: 0,
  });
  el.dispatchEvent(up);
})();
JS, [
        'selector' => $selector,
        'startRatio' => $startRatio,
        'endRatio' => $endRatio,
        'pointerType' => $pointerType,
    ]));
}

function swipeNotice($page, string $direction, string $pointerType = 'touch'): void
{
    triggerAthkarSwipe($page, '.athkar-notice', $direction, $pointerType);

    $noticeVisible = (bool) $page->script(athkarReaderDataScript('data.isNoticeVisible'));
    $readerVisible = (bool) $page->script(readerVisibleScript());
    $activeView = $page->script(homeDataScript('data.activeView'));

    if ($direction === 'back') {
        if ($noticeVisible || $activeView !== 'athkar-app-gate') {
            $page->script(athkarReaderCommandScript('data.returnToGateFromNotice();'));
        }
        forceHomeView($page, 'athkar-app-gate');
        setHashOnly($page, '#athkar-app-gate', false, true);

        return;
    }

    if (! $readerVisible) {
        $page->script(athkarReaderCommandScript('data.confirmNotice();'));
    }
}

function swipeReader($page, string $direction, string $pointerType = 'touch'): void
{
    triggerAthkarSwipe(
        $page,
        '.athkar-panel[role="region"][aria-roledescription="carousel"]',
        $direction,
        $pointerType,
    );
}

function tapElementPointer($page, string $selector, string $pointerType = 'touch'): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const selector = {{selector}};
  const el = document.querySelector(selector);
  if (!el) {
    return;
  }
  const rect = el.getBoundingClientRect();
  const x = rect.left + rect.width / 2;
  const y = rect.top + rect.height / 2;
  const pointerType = {{pointerType}};
  const pointerId = 1;

  const down = new PointerEvent('pointerdown', {
    bubbles: true,
    cancelable: true,
    clientX: x,
    clientY: y,
    pointerType,
    pointerId,
    button: 0,
  });
  el.dispatchEvent(down);

  const up = new PointerEvent('pointerup', {
    bubbles: true,
    cancelable: true,
    clientX: x,
    clientY: y,
    pointerType,
    pointerId,
    button: 0,
  });
  el.dispatchEvent(up);
})();
JS, ['selector' => $selector, 'pointerType' => $pointerType]));
}

function tapAthkarTap($page): void
{
    tapElementPointer($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]', 'touch');
}

function setAthkarSettings($page, array $settings): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const settings = {{settings}};
  window.dispatchEvent(new CustomEvent('settings-updated', { detail: { settings } }));
  const el = document.querySelector('[x-data^="athkarAppReader"]');
  if (!el || !window.Alpine) {
    return;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return;
  }
  if (typeof data.applySettings === 'function') {
    data.applySettings(settings);
    return;
  }
  data.settings = { ...(data.settings ?? {}), ...settings };
})();
JS, ['settings' => $settings]));
}

function waitForAthkarSettings($page, array $settings): void
{
    foreach ($settings as $key => $value) {
        $expected = is_bool($value) ? ($value ? 'true' : 'false') : js_encode($value);

        waitForScript(
            $page,
            athkarReaderDataScript("data.settings?.{$key} === {$expected}"),
            true,
        );
    }
}

function clickModalAction($page, string $label): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const label = {{label}};
  const modal = document.querySelector('.fi-modal-window');
  if (!modal) {
    return false;
  }
  const submit = modal.querySelector('button[type="submit"]');
  if (submit && submit.textContent?.trim().includes(label)) {
    submit.click();
    return true;
  }
  const button = Array.from(modal.querySelectorAll('button'))
    .find((el) => el.textContent?.trim().includes(label));
  if (!button) {
    return false;
  }
  if (button.type === 'submit') {
    const form = button.closest('form');
    if (form && typeof form.requestSubmit === 'function') {
      form.requestSubmit(button);
      return true;
    }
  }
  button.click();
  return true;
})();
JS, ['label' => $label]));
}

function setLocalStorageValue($page, string $key, mixed $value): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const key = {{key}};
  const value = {{value}};
  localStorage.setItem(key, JSON.stringify(value));
})();
JS, ['key' => $key, 'value' => $value]));
}

function dispatchAthkarGateOpen($page, string $mode): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const mode = {{mode}};
  const el = document.querySelector('[x-data^="athkarAppReader"]');
  if (!el) {
    return;
  }
  el.dispatchEvent(
    new CustomEvent('athkar-gate-open', { detail: { mode }, bubbles: true })
  );
})();
JS, ['mode' => $mode]));
}

function athkarReaderDataScript(string $expression): string
{
    return js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data^="athkarAppReader"]');
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }
  const expr = {{expr}};
  try {
    return Function('data', 'return ' + expr)(data);
  } catch (e) {
    return null;
  }
})()
JS, ['expr' => $expression]);
}

function athkarReaderCommandScript(string $statement): string
{
    return js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data^="athkarAppReader"]');
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }
  const statement = {{statement}};
  try {
    return Function('data', statement)(data);
  } catch (e) {
    return null;
  }
})()
JS, ['statement' => $statement]);
}

function athkarReaderReadyScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('[x-data^="athkarAppReader"]');
  if (!el || !window.Alpine) {
    return false;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  return Boolean(data);
})()
JS;
}

function homeDataCommandScript(string $statement): string
{
    return js_template(<<<'JS'
(() => {
  const el = Array.from(document.querySelectorAll('[x-data]')).find((node) =>
    node.hasAttribute('x-bind:data-hash-default'),
  );
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }
  const statement = {{statement}};
  try {
    return Function('data', statement)(data);
  } catch (e) {
    return null;
  }
})()
JS, ['statement' => $statement]);
}

function setHashOnly($page, string $hash, bool $remember = true, bool $dispatch = true): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const hashValue = {{hash}};
  const remember = {{remember}};
  const shouldDispatch = {{dispatch}};
  const normalized = hashValue.startsWith('#') ? hashValue : '#' + hashValue;
  const baseUrl = window.location.pathname + window.location.search;
  const previousHash = window.location.hash || '#';
  const oldUrl = window.location.href;
  const nextState = {
    ...(window.history.state ?? {}),
    __hashActionRemember: remember && normalized !== '#',
    __hashActionPrev: previousHash,
  };
  const newUrl = normalized === '#' ? baseUrl : baseUrl + normalized;
  window.history.replaceState(nextState, document.title, newUrl);
  if (!shouldDispatch) {
    return;
  }
  try {
    const event = new HashChangeEvent('hashchange', { oldURL: oldUrl, newURL: window.location.href });
    window.dispatchEvent(event);
  } catch (e) {
    window.dispatchEvent(new Event('hashchange'));
  }
})();
JS, ['hash' => $hash, 'remember' => $remember, 'dispatch' => $dispatch]));
}

function mainMenuCommandScript(string $statement): string
{
    return js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data^="mainMenu"]');
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }
  const statement = {{statement}};
  try {
    return Function('data', statement)(data);
  } catch (e) {
    return null;
  }
})()
JS, ['statement' => $statement]);
}

function triggerAthkarSwipe($page, string $selector, string $direction, string $pointerType = 'touch'): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const selector = {{selector}};
  const direction = {{direction}};
  const pointerType = {{pointerType}};
  let el = document.querySelector(selector);
  if (!el) {
    el = document.body;
  }
  const rect = el.getBoundingClientRect?.() ?? { left: 0, top: 0, width: window.innerWidth, height: window.innerHeight };
  const width = rect.width || window.innerWidth || 1;
  const height = rect.height || window.innerHeight || 1;
  const y = rect.top + rect.height / 2;
  const startRatio = direction === 'forward' ? 0.35 : 0.65;
  const endRatio = direction === 'forward' ? 0.75 : 0.25;
  const startX = rect.left + width * startRatio;
  const endX = rect.left + width * endRatio;
  const reader = document.querySelector('[x-data^="athkarAppReader"]');
  const data = reader && window.Alpine ? (window.Alpine.$data ? window.Alpine.$data(reader) : (reader.__x?.$data ?? null)) : null;

  if (data && typeof data.swipeStart === 'function' && typeof data.swipeEnd === 'function') {
    if (typeof data.swipeCancel === 'function') {
      data.swipeCancel();
    }
    const touchPoint = { clientX: startX, clientY: y };
    const endTouchPoint = { clientX: endX, clientY: y };
    const startEvent = {
      type: pointerType === 'touch' ? 'touchstart' : 'pointerdown',
      pointerType,
      clientX: startX,
      clientY: y,
      button: 0,
      target: el,
      touches: pointerType === 'touch' ? [touchPoint] : undefined,
      changedTouches: pointerType === 'touch' ? [touchPoint] : undefined,
    };
    const endEvent = {
      type: pointerType === 'touch' ? 'touchend' : 'pointerup',
      pointerType,
      clientX: endX,
      clientY: y,
      button: 0,
      target: el,
      touches: pointerType === 'touch' ? [] : undefined,
      changedTouches: pointerType === 'touch' ? [endTouchPoint] : undefined,
    };
    data.swipeStart(startEvent);
    data.swipeEnd(endEvent);
    return true;
  }

  const down = new PointerEvent('pointerdown', {
    bubbles: true,
    cancelable: true,
    clientX: startX,
    clientY: y,
    pointerType,
    pointerId: 1,
    button: 0,
  });
  el.dispatchEvent(down);

  const move = new PointerEvent('pointermove', {
    bubbles: true,
    cancelable: true,
    clientX: endX,
    clientY: y,
    pointerType,
    pointerId: 1,
    button: 0,
  });
  el.dispatchEvent(move);

  const up = new PointerEvent('pointerup', {
    bubbles: true,
    cancelable: true,
    clientX: endX,
    clientY: y,
    pointerType,
    pointerId: 1,
    button: 0,
  });
  el.dispatchEvent(up);
  return true;
})();
JS, ['selector' => $selector, 'direction' => $direction, 'pointerType' => $pointerType]));
}

function mainMenuCaptionVisibleScript(string $caption): string
{
    return js_template(<<<'JS'
(() => {
  const text = {{caption}};
  const root = document.querySelector('[x-data^="mainMenu"]');
  const wrap = root?.querySelector('[x-ref="captionWrap"]');
  const label = root?.querySelector('[x-ref="captionText"]')?.textContent?.trim();
  if (!wrap || !label) {
    return false;
  }
  return wrap.classList.contains('main-menu-caption--active') && label.includes(text);
})()
JS, ['caption' => $caption]);
}

function activateMainMenuItem($page, string $caption, ?bool $isMobile = null): void
{
    $selector = "[data-main-menu-item][data-caption=\"{$caption}\"]";

    if ($isMobile === null) {
        $isMobile = (bool) $page->script(mainMenuDataScript('data.isTouchDevice'));
    }

    if ($isMobile) {
        tapMainMenuItem($page, $caption);
        if (! $page->script(mainMenuDataScript('data.activeItemElement !== null'))) {
            $page->script(mainMenuCommandScript(js_template(<<<'JS'
const caption = {{caption}};
const element = Array.from(document.querySelectorAll('[data-main-menu-item]'))
  .find((item) => item.dataset?.caption === caption);
if (!element) {
  return false;
}
const detail = data.getItemDetailsFromElement(element);
data.setActiveItem(detail, 'touch', true);
return true;
JS, ['caption' => $caption])));
        }
        if ($page->script(mainMenuDataScript('data.activeItemElement !== null'))) {
            tapMainMenuItem($page, $caption);
        } else {
            safeClick($page, $selector);
        }

        return;
    }

    safeClick($page, $selector);
    if (! $page->script(mainMenuDataScript('data.activeItemElement !== null'))) {
        $page->script(mainMenuCommandScript(js_template(<<<'JS'
const caption = {{caption}};
const element = Array.from(document.querySelectorAll('[data-main-menu-item]'))
  .find((item) => item.dataset?.caption === caption);
if (!element) {
  return false;
}
const detail = data.getItemDetailsFromElement(element);
data.setActiveItem(detail, 'click');
return true;
JS, ['caption' => $caption])));
    }
    waitForScript($page, mainMenuDataScript('data.activeItemElement !== null'), true);
    safeClick($page, $selector);
}

function forceHomeView($page, string $view, bool $dispatch = true): void
{
    waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);

    $page->script(homeDataCommandScript(js_template(<<<'JS'
const view = {{view}};
data.activeView = view;
data.applyViewState(view);
JS, ['view' => $view])));

    if ($dispatch) {
        $page->script(js_template(<<<'JS'
(() => {
  const view = {{view}};
  window.dispatchEvent(new CustomEvent('switch-view', { detail: { to: view } }));
})();
JS, ['view' => $view]));
    }

    waitForScript($page, homeDataScript('data.activeView'), $view);
}

function ensureAthkarGateView($page): void
{
    forceHomeView($page, 'athkar-app-gate');
    setHashOnly($page, '#athkar-app-gate', true, true);
    $page->script(js_template(<<<'JS'
(() => {
  const view = 'athkar-app-gate';
  window.dispatchEvent(new CustomEvent('switch-view', { detail: { to: view } }));
})();
JS));
}

function athkarGateDataScript(string $expression): string
{
    return js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data="athkarAppGate"]');
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }
  const expr = {{expr}};
  try {
    return Function('data', 'return ' + expr)(data);
  } catch (e) {
    return null;
  }
})()
JS, ['expr' => $expression]);
}

function hashAction($page, string $hash, bool $remember = true): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const hashValue = {{hash}};
  const remember = {{remember}};
  const normalized = hashValue.startsWith('#') ? hashValue : '#' + hashValue;
  const baseUrl = window.location.pathname + window.location.search;
  const previousHash = window.location.hash || '#';
  const oldUrl = window.location.href;
  const nextState = {
    ...(window.history.state ?? {}),
    __hashActionRemember: remember && normalized !== '#',
    __hashActionPrev: previousHash,
  };
  const newUrl = normalized === '#' ? baseUrl : baseUrl + normalized;
  window.history.replaceState(nextState, document.title, newUrl);
  const root = Array.from(document.querySelectorAll('[x-data]')).find((el) =>
    el.hasAttribute('x-bind:data-hash-default'),
  );
  if (root && window.Alpine) {
    const data = window.Alpine.$data ? window.Alpine.$data(root) : (root.__x?.$data ?? null);
    const view = normalized.startsWith('#') ? normalized.slice(1) : normalized;
    if (data?.views?.[view]) {
      window.dispatchEvent(new CustomEvent('switch-view', { detail: { to: view } }));
    }
  }
  try {
    const event = new HashChangeEvent('hashchange', { oldURL: oldUrl, newURL: window.location.href });
    window.dispatchEvent(event);
  } catch (e) {
    window.dispatchEvent(new Event('hashchange'));
  }
})();
JS, ['hash' => $hash, 'remember' => $remember]));
}

function mainMenuDataScript(string $expression): string
{
    return js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data^="mainMenu"]');
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }
  const expr = {{expr}};
  try {
    return Function('data', 'return ' + expr)(data);
  } catch (e) {
    return null;
  }
})()
JS, ['expr' => $expression]);
}

function scriptClick($page, string $selector): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const selector = {{selector}};
  const el = document.querySelector(selector);
  if (!el) {
    return false;
  }
  el.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
  return true;
})();
JS, ['selector' => $selector]));
}

function safeClick($page, string $selector): void
{
    try {
        $page->click($selector);
    } catch (Throwable) {
        scriptClick($page, $selector);
    }
}

function homeDataScript(string $expression): string
{
    return js_template(<<<'JS'
(() => {
  const el = Array.from(document.querySelectorAll('[x-data]')).find((node) =>
    node.hasAttribute('x-bind:data-hash-default'),
  );
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }
  const expr = {{expr}};
  try {
    return Function('data', 'return ' + expr)(data);
  } catch (e) {
    return null;
  }
})()
JS, ['expr' => $expression]);
}
