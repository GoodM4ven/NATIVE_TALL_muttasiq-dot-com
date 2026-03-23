<?php

declare(strict_types=1);

use App\Models\Setting;

function openControlPanelModal($page): void
{
    waitForScript($page, 'Boolean(document.querySelector(\'[data-testid="control-panel-button"]\'))');
    waitForScript($page, 'Boolean(window.Livewire)');
    scriptClick($page, '[data-stack-item][x-data] [data-testid="control-panel-button"]');

    $isOpen = $page->script('Boolean(document.querySelector(".fi-modal-window"))');

    if (! $isOpen) {
        $page->script('window.dispatchEvent(new CustomEvent("open-control-panel-modal"));');
    }

    waitForScript($page, 'Boolean(document.querySelector(".fi-modal-window"))');
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
    if (isFastBrowserMode()) {
        waitForScript($page, homeDataScript('typeof data.applyViewState === "function"'), true);
        ensureAthkarGateView($page);
        waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-gate');
        waitForScript($page, 'window.location.hash', '#athkar-app-gate');
        waitForGateVisible($page);

        return;
    }

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

    $page->script(athkarReaderCommandScript(
        'data.settings = { ...(data.settings ?? {}), '.Setting::DOES_SKIP_GUIDANCE_PANELS.': false };',
    ));

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
    if (isFastBrowserMode()) {
        openAthkarReaderFast($page, $mode, $isMobile);

        return;
    }

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

function openAthkarReaderFast($page, string $mode, bool $isMobile): void
{
    openAthkarGate($page, $isMobile);

    $view = $mode === 'sabah' ? 'athkar-app-sabah' : 'athkar-app-masaa';
    $hash = '#'.$view;

    if ($page->script(homeDataScript('data.activeView')) !== $view) {
        forceHomeView($page, $view);
    }

    if ($page->script('window.location.hash') !== $hash) {
        setHashOnly($page, $hash, true, true);
    }

    $page->script(homeDataCommandScript(<<<'JS'
views['athkar-app-gate'].isReaderVisible = true;
JS));
    $page->script(athkarReaderCommandScript(js_template(<<<'JS'
const mode = {{mode}};

if (typeof data.restoreMode === 'function') {
  data.restoreMode(mode);
}

if (data.isNoticeVisible && typeof data.confirmNotice === 'function') {
  data.confirmNotice();
}

if (data.views?.['athkar-app-gate']) {
  data.views['athkar-app-gate'].isReaderVisible = true;
}
JS, ['mode' => $mode])));

    setLocalStorageValue($page, 'athkar-active-mode', $mode);
    setLocalStorageValue($page, 'athkar-reader-visible', true);
    setLocalStorageValue($page, 'app-active-view', $view);

    waitForScript($page, athkarReaderDataScript('data.activeMode'), $mode);
    waitForScript($page, athkarReaderDataScript('data.activeList.length > 0'), true);
    waitForScript($page, homeDataScript('data.activeView'), $view);
    waitForScript($page, 'window.location.hash', $hash);
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

function ensureAthkarReaderMode($page, string $mode): void
{
    try {
        waitForScriptWithTimeout($page, athkarReaderDataScript('data.activeMode'), $mode, 2_200);

        return;
    } catch (Throwable) {
        //
    }

    $view = $mode === 'sabah' ? 'athkar-app-sabah' : 'athkar-app-masaa';
    $hash = '#'.$view;

    if ($page->script(homeDataScript('data.activeView')) !== $view) {
        forceHomeView($page, $view);
    }

    if ($page->script('window.location.hash') !== $hash) {
        setHashOnly($page, $hash, true, true);
    }

    $page->script(homeDataCommandScript(<<<'JS'
views['athkar-app-gate'].isReaderVisible = true;
JS));

    $page->script(js_template(<<<'JS'
(() => {
  const view = {{view}};
  window.dispatchEvent(new CustomEvent('switch-view', {
    detail: {
      to: view,
      restoring: true,
    },
  }));
})();
JS, ['view' => $view]));

    if ($page->script(athkarReaderDataScript('data.activeMode')) !== $mode) {
        $page->script(athkarReaderCommandScript("data.restoreMode('{$mode}');"));
    }

    if ($page->script(athkarReaderDataScript('data.isNoticeVisible'))) {
        $page->script(athkarReaderCommandScript('data.confirmNotice();'));
    }

    waitForScriptWithTimeout($page, athkarReaderDataScript('data.activeMode'), $mode, 3_000);
    waitForReaderVisible($page);
}

function waitForGateVisible($page): void
{
    waitForScript($page, gateVisibleScript());
}

function waitForQuranGateVisible($page): void
{
    waitForScript($page, quranGateVisibleScript());
}

function waitForQuranReaderVisible($page): void
{
    waitForScript($page, quranReaderVisibleScript());
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

function quranGateVisibleScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('.quran-app-gate-shell');
  if (!el) {
    return false;
  }
  return getComputedStyle(el).display !== 'none';
})()
JS;
}

function quranReaderVisibleScript(): string
{
    return <<<'JS'
(() => {
  const el = document.querySelector('[x-data^="quranAppReader"]');
  if (!el) {
    return false;
  }
  return getComputedStyle(el).display !== 'none';
})()
JS;
}

function quickStackDataScript(string $expression): string
{
    return js_template(<<<'JS'
(() => {
  const root = document.querySelector('[x-ref="stack"]')?.parentElement ?? null;
  if (!root || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(root) : (root.__x?.$data ?? null);
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

function quickStackLayoutReadyScript(): string
{
    return <<<'JS'
(() => {
  const root = document.querySelector('[x-ref="stack"]')?.parentElement ?? null;
  if (!root || !window.Alpine) {
    return false;
  }

  const data = window.Alpine.$data ? window.Alpine.$data(root) : (root.__x?.$data ?? null);
  if (!data || data.respectingStack !== true) {
    return false;
  }

  const visibleItems = Array.from(document.querySelectorAll('[data-stack-item]')).filter((item) => {
    const styles = getComputedStyle(item);
    return !item.hidden && styles.display !== 'none' && styles.visibility !== 'hidden';
  });

  if (visibleItems.length < 2) {
    return false;
  }

  return visibleItems.every((item) => {
    const transform = String(item.style.transform ?? '');

    return item.style.position === 'absolute'
      && /^translateX\((-?\d+(?:\.\d+)?)rem\)$/.test(transform);
  });
})()
JS;
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

function setAthkarSettings($page, array $settings): void
{
    $page->script(js_template(<<<'JS'
(() => {
  const settings = {{settings}};
  window.dispatchEvent(new CustomEvent('control-panel-updated', { detail: { controlPanel: settings } }));
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
    $expressions = [];

    foreach ($settings as $key => $value) {
        if (is_bool($value)) {
            $expected = $value ? 'true' : 'false';
            $expressions[] = "Boolean(data.settings?.{$key}) === {$expected}";
        } elseif (is_numeric($value)) {
            $expressions[] = 'Number(data.settings?.'.$key.') === '.(int) $value;
        } else {
            $expected = js_encode($value);
            $expressions[] = "data.settings?.{$key} === {$expected}";
        }
    }

    if ($expressions === []) {
        return;
    }

    waitForScript($page, athkarReaderDataScript(implode(' && ', $expressions)), true);
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

function quranReaderDataScript(string $expression): string
{
    return js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data^="quranAppReader"]');
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

function quranReaderCommandScript(string $statement): string
{
    return js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data^="quranAppReader"]');
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
  const centerX = rect.left + width / 2;
  const centerY = rect.top + height / 2;
  const horizontalDirection = direction === 'forward' || direction === 'back';
  const verticalDirection = direction === 'up' || direction === 'down';
  const horizontalDistance = Math.min(width * 0.8, Math.max(80, width * 0.4));
  const verticalDistance = Math.min(height * 0.8, Math.max(80, height * 0.4));

  let startX = centerX;
  let endX = centerX;
  let startY = centerY;
  let endY = centerY;

  if (horizontalDirection) {
    const isForward = direction === 'forward';
    const halfDistance = horizontalDistance / 2;
    startX = centerX + (isForward ? -halfDistance : halfDistance);
    endX = centerX + (isForward ? halfDistance : -halfDistance);
  }

  if (verticalDirection) {
    const isDown = direction === 'down';
    const halfDistance = verticalDistance / 2;
    startY = centerY + (isDown ? -halfDistance : halfDistance);
    endY = centerY + (isDown ? halfDistance : -halfDistance);
  }
  const reader = document.querySelector('[x-data^="athkarAppReader"]');
  const data = reader && window.Alpine ? (window.Alpine.$data ? window.Alpine.$data(reader) : (reader.__x?.$data ?? null)) : null;

  if (data && typeof data.swipeStart === 'function' && typeof data.swipeEnd === 'function') {
    if (typeof data.swipeCancel === 'function') {
      data.swipeCancel();
    }
    const touchPoint = { clientX: startX, clientY: startY };
    const endTouchPoint = { clientX: endX, clientY: endY };
    const startEvent = {
      type: pointerType === 'touch' ? 'touchstart' : 'pointerdown',
      pointerType,
      clientX: startX,
      clientY: startY,
      button: 0,
      target: el,
      touches: pointerType === 'touch' ? [touchPoint] : undefined,
      changedTouches: pointerType === 'touch' ? [touchPoint] : undefined,
    };
    const endEvent = {
      type: pointerType === 'touch' ? 'touchend' : 'pointerup',
      pointerType,
      clientX: endX,
      clientY: endY,
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
    clientY: startY,
    pointerType,
    pointerId: 1,
    button: 0,
  });
  el.dispatchEvent(down);

  const move = new PointerEvent('pointermove', {
    bubbles: true,
    cancelable: true,
    clientX: endX,
    clientY: endY,
    pointerType,
    pointerId: 1,
    button: 0,
  });
  el.dispatchEvent(move);

  const up = new PointerEvent('pointerup', {
    bubbles: true,
    cancelable: true,
    clientX: endX,
    clientY: endY,
    pointerType,
    pointerId: 1,
    button: 0,
  });
  el.dispatchEvent(up);
  return true;
})();
JS, ['selector' => $selector, 'direction' => $direction, 'pointerType' => $pointerType]));
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
