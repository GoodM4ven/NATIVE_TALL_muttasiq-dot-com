<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Thikr;
use App\Services\Enums\ThikrTime;

uses()->group('browser-flaky');

it('keeps progress pinned to the same thikr id after add/remove/reorder overrides and reload', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ]);

    $activeCount = (int) ($page->script(athkarReaderDataScript('data.activeList.length')) ?? 0);
    if ($activeCount <= 2) {
        $this->markTestSkipped('Athkar active list did not initialize with enough items in current browser runtime.');
    }

    $targetIndex = $page->script(athkarReaderDataScript('Math.min(2, data.activeList.length - 1)'));
    expect($targetIndex)->toBeGreaterThanOrEqual(0);

    $targetId = $page->script(athkarReaderDataScript('data.activeList['.$targetIndex.']?.id ?? null'));
    expect($targetId)->not->toBeNull();

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$targetIndex}); data.setCount({$targetIndex}, 2, { allowOvercount: true });",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);

    $result = $page->script(js_template(<<<'JS'
(() => {
  const el = document.querySelector('[x-data^="athkarAppReader"]');
  if (!el || !window.Alpine) {
    return null;
  }
  const data = window.Alpine.$data ? window.Alpine.$data(el) : (el.__x?.$data ?? null);
  if (!data) {
    return null;
  }

  const targetId = Number({{targetId}});
  const list = Array.isArray(data.activeList) ? data.activeList : [];
  const deleteCandidate = list.find((item) => Number(item?.id ?? 0) !== targetId);
  const maxExistingId = list.reduce((max, item) => Math.max(max, Number(item?.id ?? 0)), 0);
  const customId = maxExistingId + 1000;

  const overrides = [
    {
      thikr_id: targetId,
      order: 1,
    },
    {
      thikr_id: customId,
      order: 2,
      time: 'shared',
      type: 'supplication',
      text: 'ذكر مخصص لاختبار الاستعادة',
      origin: null,
      count: 1,
      is_aayah: false,
      is_deleted: false,
      is_custom: true,
    },
  ];

  if (deleteCandidate) {
    overrides.push({
      thikr_id: Number(deleteCandidate.id),
      is_deleted: true,
    });
  }

  data.applyAthkarOverrides(overrides, { persist: true });

  return {
    deletedId: deleteCandidate ? Number(deleteCandidate.id) : null,
    customId,
  };
})()
JS, ['targetId' => $targetId]));

    expect($result)->toBeArray();
    $deletedId = $result['deletedId'] ?? null;
    $customId = $result['customId'] ?? null;

    $targetIdExpression = js_encode($targetId);
    waitForScriptWithTimeout(
        $page,
        athkarReaderDataScript(
            "String(data.activeList[data.activeIndex]?.id ?? '') === String({$targetIdExpression})",
        ),
        true,
        4_000,
    );
    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);

    if ($deletedId !== null) {
        $deletedIdExpression = js_encode($deletedId);
        waitForScriptWithTimeout(
            $page,
            athkarReaderDataScript(
                "data.activeList.every((item) => String(item?.id ?? '') !== String({$deletedIdExpression}))",
            ),
            true,
            4_000,
        );
    }

    if ($customId !== null) {
        $customIdExpression = js_encode($customId);
        waitForScriptWithTimeout(
            $page,
            athkarReaderDataScript(
                "data.activeList.some((item) => String(item?.id ?? '') === String({$customIdExpression}))",
            ),
            true,
            4_000,
        );
    }

    forceHomeView($page, 'athkar-app-sabah');
    setHashOnly($page, '#athkar-app-sabah', true, true);
    $page->script(homeDataCommandScript(<<<'JS'
views['athkar-app-gate'].isReaderVisible = true;
JS));
    setLocalStorageValue($page, 'athkar-active-mode', 'sabah');
    setLocalStorageValue($page, 'athkar-reader-visible', true);
    setLocalStorageValue($page, 'app-active-view', 'athkar-app-sabah');
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-sabah');
    waitForReaderVisible($page);
    waitForScript($page, 'JSON.parse(localStorage.getItem("athkar-active-mode"))', 'sabah');
    waitForScript($page, 'JSON.parse(localStorage.getItem("app-active-view"))', 'athkar-app-sabah');
    waitForScript($page, 'window.location.hash', '#athkar-app-sabah');

    $todayKey = $page->script(<<<'JS'
(() => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
})()
JS);
    $page->script(athkarReaderCommandScript('data.lastSeenDay = data.todayKey();'));
    setLocalStorageValue($page, 'athkar-last-day', $todayKey);
    waitForScript($page, 'JSON.parse(localStorage.getItem("athkar-last-day"))', $todayKey);

    $page->refresh();

    waitForAlpineReady($page);
    ensureAthkarReaderMode($page, 'sabah');
    waitForScriptWithTimeout(
        $page,
        athkarReaderDataScript(
            "String(data.activeList[data.activeIndex]?.id ?? '') === String({$targetIdExpression})",
        ),
        true,
        4_000,
    );
    waitForScriptWithTimeout(
        $page,
        js_template(<<<'JS'
(() => {
  const targetId = String({{targetId}});
  const progress = JSON.parse(localStorage.getItem('athkar-progress-v1') ?? '{}');
  const ids = progress?.sabah?.ids ?? [];
  const counts = progress?.sabah?.counts ?? [];
  const targetIndex = ids.findIndex((id) => String(id ?? '') === targetId);
  if (targetIndex < 0) {
    return null;
  }
  return Number(counts[targetIndex] ?? 0);
})()
JS, ['targetId' => $targetId]),
        2,
        4_000,
    );
});

it('fits origin text independently and keeps the text box clear of mobile top controls', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    enableMobileContext($page);
    waitForReaderVisible($page);

    $originIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item) => String(item?.origin ?? "").trim().length > 0 || Boolean(item?.is_original))',
        ),
    );

    expect($originIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript(js_template(<<<'JS'
data.setActiveIndex({{index}});
const activeIndex = data.activeIndex;

if (!data.activeList?.[activeIndex]) {
  return;
}

data.activeList[activeIndex].text = 'لا إله إلا الله';
data.activeList[activeIndex].origin = 'حدثنا عبد الله بن مسلمة عن مالك عن سمي عن أبي صالح عن أبي هريرة رضي الله عنه أن رسول الله صلى الله عليه وسلم قال من قال لا إله إلا الله وحده لا شريك له له الملك وله الحمد وهو على كل شيء قدير في يوم مائة مرة كانت له عدل عشر رقاب وكتبت له مائة حسنة ومحيت عنه مائة سيئة وكانت له حرزا من الشيطان يومه ذلك حتى يمسي';
data.activeList[activeIndex].count = 55;

if (Array.isArray(data.progress?.[data.activeMode]?.counts)) {
  data.progress[data.activeMode].counts[activeIndex] = 0;
}

data.originToggle = { mode: data.activeMode, index: activeIndex };
data.queueReaderTextFit();
JS, ['index' => $originIndex])));

    waitForScript($page, athkarReaderDataScript('data.isOriginVisible(data.activeIndex)'), true);
    waitForScript(
        $page,
        <<<'JS'
(() => {
  const origin = document.querySelector('[data-athkar-slide][data-active="true"] [data-athkar-origin-text]');
  if (!origin) {
    return false;
  }

  return String(origin.textContent ?? '').trim().length > 150;
})()
JS,
        true,
    );
    $page->script(athkarReaderCommandScript('data.queueReaderTextFit();'));
    waitForScript(
        $page,
        <<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  if (!slide) {
    return false;
  }

  const text = slide.querySelector('[data-athkar-text]');
  const origin = slide.querySelector('[data-athkar-origin-text]');
  if (!text || !origin) {
    return false;
  }

  return text.classList.contains('is-fit') && origin.classList.contains('is-fit');
})()
JS,
        true,
    );

    $fontSizes = $page->script(<<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  if (!slide) {
    return null;
  }

  const text = slide.querySelector('[data-athkar-text]');
  const origin = slide.querySelector('[data-athkar-origin-text]');
  if (!text || !origin) {
    return null;
  }

  return {
    text: Number.parseFloat(getComputedStyle(text).fontSize),
    origin: Number.parseFloat(getComputedStyle(origin).fontSize),
  };
})()
JS);

    expect($fontSizes)->toBeArray()
        ->and($fontSizes['origin'])->toBeLessThanOrEqual($fontSizes['text']);

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const originIcon = document.querySelector('[data-athkar-mobile-top-ui] .athkar-origin-indicator--mobile .athkar-origin-indicator__icon');
  if (!originIcon) {
    return true;
  }

  return originIcon.classList.contains('athkar-origin-indicator__icon');
})()
JS,
        true,
    );

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  if (!slide) {
    return false;
  }

  const box = slide.querySelector('[data-athkar-text-box]');
  const counter = document.querySelector('[data-athkar-mobile-counter] button[aria-label="العدد"]');
  const originToggle = document.querySelector('[data-athkar-mobile-top-ui] .athkar-origin-indicator--mobile');

  if (!box || !counter || !originToggle) {
    return true;
  }

  const controlsBottom = Math.max(
    counter.getBoundingClientRect().bottom,
    originToggle.getBoundingClientRect().bottom,
  );
  const boxRect = box.getBoundingClientRect();
  const paddingTop = Number.parseFloat(getComputedStyle(box).paddingTop);
  const contentTop = boxRect.top + (Number.isFinite(paddingTop) ? paddingTop : 0);

  return Number.isFinite(contentTop) && Number.isFinite(controlsBottom);
})()
JS,
        true,
    );
});

it('keeps the athkar font size slider on one shared value and refits immediately', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    waitForReaderVisible($page);

    $initialFontSizes = $page->script(<<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  if (!slide) {
    return null;
  }

  const text = slide.querySelector('[data-athkar-text]');
  const origin = slide.querySelector('[data-athkar-origin-text]');
  if (!text || !origin) {
    return null;
  }

  return {
    text: Number.parseFloat(getComputedStyle(text).fontSize),
    origin: Number.parseFloat(getComputedStyle(origin).fontSize),
  };
})()
JS);

    expect($initialFontSizes)->toBeArray();
    $page->script('window.__athkarFontSizeBefore = '.js_encode($initialFontSizes['text']).';');

    $page->script(athkarReaderCommandScript('data.handleMainTextSizeInput({ target: { value: 14 } });'));

    waitForScript(
        $page,
        athkarReaderDataScript(
            'data.settings.minimum_main_text_size === 14 && data.settings.maximum_main_text_size === 14 && data.mainTextSizeValue() === 14',
        ),
        true,
    );

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const text = document.querySelector('[data-athkar-slide][data-active="true"] [data-athkar-text]');
  if (!text) {
    return false;
  }

  const before = Number(window.__athkarFontSizeBefore ?? 0);
  const next = Number.parseFloat(getComputedStyle(text).fontSize);

  return Number.isFinite(before) && Number.isFinite(next) && next < before;
})()
JS,
        true,
    );
});

it('bypasses hint popups but still requires confirmation for single-thikr completion when setting 4 is enabled', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        Setting::DOES_SKIP_GUIDANCE_PANELS => true,
        Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION => false,
        Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $multiIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item) => Number(item.count ?? 1) > 1)',
        ),
    );

    expect($multiIndex)->toBeGreaterThanOrEqual(0);
    $targetItemId = (int) $page->script(
        athkarReaderDataScript("Number(data.activeList[{$multiIndex}]?.id ?? 0)"),
    );
    expect($targetItemId)->toBeGreaterThan(0);

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$multiIndex}); data.setCount({$multiIndex}, 0);",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $multiIndex);

    $page->script(athkarReaderCommandScript("data.toggleHint({$multiIndex});"));
    waitForScript($page, athkarReaderDataScript('data.hintIndex'), null);

    $desktopCompleteSelector = '[data-athkar-desktop-counter-row] button[aria-label="إتمام الذكر"]';
    $page->hover('[data-athkar-desktop-counter]');
    waitForScript(
        $page,
        js_template('Boolean(document.querySelector({{selector}}))', ['selector' => $desktopCompleteSelector]),
        true,
    );
    scriptClick($page, $desktopCompleteSelector);

    waitForScript($page, 'Boolean(document.querySelector(".fi-modal-window"))', true);
    $submittedSingleCompletion = clickModalAction($page, 'نعم، أكملت قراءته');

    if (! $submittedSingleCompletion) {
        $page->script(athkarReaderCommandScript("data.completeThikr({$multiIndex});"));
    }

    waitForScriptWithTimeout(
        $page,
        athkarReaderDataScript(
            js_template(
                <<<'JS'
(() => {
  const targetItemId = Number({{targetItemId}});
  const index = data.activeList.findIndex((item) => Number(item?.id ?? 0) === targetItemId);

  if (index < 0) {
    return true;
  }

  return Number(data.countAt(index) ?? 0) >= Number(data.requiredCount(index) ?? 1);
})()
JS,
                ['targetItemId' => $targetItemId],
            ),
        ),
        true,
        8_000,
    );
});

it('suppresses helper tippies by default when guidance panels are skipped, but allows explicit opt-out tooltips', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        Setting::DOES_SKIP_GUIDANCE_PANELS => true,
        Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script('window.hideAllTippies?.({ duration: 0, suppressMs: 0 });');
    $page->hover('[data-athkar-open-manager]');

    waitForScriptWithTimeout($page, <<<'JS'
(() => {
  const tooltip = [...document.querySelectorAll('.tippy-box')]
    .find((el) => (el.textContent ?? '').includes('إدارة الأذكار'));

  if (!tooltip) {
    return true;
  }

  return tooltip.getAttribute('data-state') !== 'visible' || getComputedStyle(tooltip).visibility === 'hidden';
})()
JS, true, 600);

    $prepared = (bool) $page->script(<<<'JS'
(() => {
  const root = document.querySelector('[data-athkar-app-reader-root]');

  if (!root || !window.Alpine?.initTree) {
    return false;
  }

  const host = document.createElement('div');

  host.innerHTML = `
    <button
      data-testid="guidance-tippy-opt-out"
      type="button"
      x-on:mouseenter="$tippy('تلميح استثنائي', 'bottom', 2000, { showWhenGuidancePanelsSkipped: true })"
      x-on:mouseleave="$tippy.hide()"
      x-on:focus="$tippy('تلميح استثنائي', 'bottom', 2000, { showWhenGuidancePanelsSkipped: true })"
      x-on:blur="$tippy.hide()"
    >x</button>
  `;

  root.appendChild(host);
  window.Alpine.initTree(host);

  return true;
})()
JS);

    expect($prepared)->toBeTrue();

    $page->hover('[data-testid="guidance-tippy-opt-out"]');

    waitForScriptWithTimeout($page, <<<'JS'
(() => {
  const tooltip = [...document.querySelectorAll('.tippy-box')]
    .find((el) => (el.textContent ?? '').includes('تلميح استثنائي'));

  if (!tooltip) {
    return false;
  }

  return getComputedStyle(tooltip).visibility !== 'hidden';
})()
JS, true, 1000);
});

it('hides the mobile single-count counter unless overcounting or manual passing is enabled', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    enableMobileContext($page);
    waitForReaderVisible($page);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);

    $settings = [
        Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR => true,
        Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO => true,
        Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$singleIndex}); data.setCount({$singleIndex}, 0, { allowOvercount: true }); data.closeHint();",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);
    waitForScript(
        $page,
        <<<'JS'
(() => {
  const counter = document.querySelector('[data-athkar-mobile-counter]');
  return Boolean(counter) && getComputedStyle(counter).display === 'none';
})()
JS,
        true,
    );

    $page->script(
        athkarReaderCommandScript("data.setCount({$singleIndex}, 2, { allowOvercount: true });"),
    );
    waitForScript(
        $page,
        <<<'JS'
(() => {
  const counter = document.querySelector('[data-athkar-mobile-counter]');
  return Boolean(counter) && getComputedStyle(counter).display !== 'none';
})()
JS,
        true,
    );

    $manualPassingSettings = [
        Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR => false,
        Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO => false,
        Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION => false,
    ];
    setAthkarSettings($page, $manualPassingSettings);
    waitForAthkarSettings($page, $manualPassingSettings);

    $page->script(
        athkarReaderCommandScript("data.setCount({$singleIndex}, 0, { allowOvercount: true });"),
    );
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);
    waitForScript(
        $page,
        <<<'JS'
(() => {
  const counter = document.querySelector('[data-athkar-mobile-counter]');
  return Boolean(counter) && getComputedStyle(counter).display !== 'none';
})()
JS,
        true,
    );
});

it('keeps overflowing mobile origin anchored to the top and scrollable', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    enableMobileContext($page);
    waitForReaderVisible($page);

    $originIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item) => String(item?.origin ?? "").trim().length > 0 || Boolean(item?.is_original))',
        ),
    );

    expect($originIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript(js_template(<<<'JS'
data.setActiveIndex({{index}});
const activeIndex = data.activeIndex;

if (!data.activeList?.[activeIndex]) {
  return;
}

data.activeList[activeIndex].text = 'لا إله إلا الله وحده لا شريك له له الملك وله الحمد';
data.activeList[activeIndex].origin = Array.from(
  { length: 140 },
  () => 'حدثنا عبد الله بن مسلمة عن مالك عن سمي عن أبي صالح عن أبي هريرة رضي الله عنه'
).join(' ');
data.originToggle = { mode: data.activeMode, index: activeIndex };
data.queueReaderTextFit();
JS, ['index' => $originIndex])));

    waitForScript($page, athkarReaderDataScript('data.isOriginVisible(data.activeIndex)'), true);

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  const box = slide?.querySelector('[data-athkar-text-box]');
  const origin = slide?.querySelector('[data-athkar-origin-text]');

  if (!box || !origin) {
    return false;
  }

  if (String(origin.textContent ?? '').trim().length < 200) {
    return false;
  }

  const styles = getComputedStyle(box);

  if (
    box.dataset.athkarOriginOverflow !== 'true' ||
    !box.classList.contains('athkar-text-box--touch-scroll') ||
    !box.classList.contains('athkar-text-box--origin-scroll') ||
    styles.overflowY !== 'auto'
  ) {
    return false;
  }

  box.scrollTop = 0;
  const boxRect = box.getBoundingClientRect();
  const paddingTop = Number.parseFloat(styles.paddingTop) || 0;
  const contentTop = boxRect.top + paddingTop;
  const originRect = origin.getBoundingClientRect();

  if (originRect.top < contentTop - 2) {
    return false;
  }

  box.scrollTop = Math.max(0, box.scrollHeight - box.clientHeight - 8);
  if (box.scrollTop <= 0) {
    return false;
  }

  box.scrollTop = 0;

  return box.scrollTop === 0;
})()
JS,
        true,
    );
});

it('shows single-thikr completion button on touch tablets without hover', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    enableTabletContext($page);
    waitForReaderVisible($page);

    setAthkarSettings($page, [
        'does_prevent_switching_athkar_until_completion' => false,
    ]);

    $multiIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item) => Number(item.count ?? 1) > 1)',
        ),
    );

    expect($multiIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript("data.setActiveIndex({$multiIndex});"));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $multiIndex);
    $targetItemId = (int) $page->script(
        athkarReaderDataScript("Number(data.activeList[{$multiIndex}]?.id ?? 0)"),
    );
    expect($targetItemId)->toBeGreaterThan(0);

    $selector = '[data-athkar-desktop-counter-row] button[aria-label="إتمام الذكر"]';
    waitForScript(
        $page,
        js_template(<<<'JS'
(() => {
  const button = document.querySelector({{selector}});
  const bp = window.Alpine?.store?.('bp');

  if (!button || !bp) {
    return false;
  }

  const styles = getComputedStyle(button);

  return (
    bp.isTouch?.() === true &&
    bp.is?.('sm+') === true &&
    styles.opacity === '1' &&
    styles.pointerEvents !== 'none'
  );
})()
JS,
            ['selector' => $selector],
        ),
        true,
    );

    /** @var array<string, mixed> $buttonState */
    $buttonState = $page->script(js_template(<<<'JS'
(() => {
  const button = document.querySelector({{selector}});
  const bp = window.Alpine?.store?.('bp');

  if (!button) {
    return {
      exists: false,
      width: window.innerWidth,
      isTouch: bp?.isTouch?.() ?? null,
      isSmPlus: bp?.is?.('sm+') ?? null,
    };
  }

  const styles = getComputedStyle(button);

  return {
    exists: true,
    width: window.innerWidth,
    isTouch: bp?.isTouch?.() ?? null,
    isSmPlus: bp?.is?.('sm+') ?? null,
    className: button.className,
    styleAttr: button.getAttribute('style'),
    display: styles.display,
    opacity: styles.opacity,
    pointerEvents: styles.pointerEvents,
  };
})()
JS, ['selector' => $selector]));

    expect($buttonState['exists'] ?? false)->toBeTrue('Button state: '.var_export($buttonState, true));
    expect($buttonState['opacity'] ?? null)->toBe('1', 'Button state: '.var_export($buttonState, true));
    expect($buttonState['pointerEvents'] ?? null)->not->toBe('none', 'Button state: '.var_export($buttonState, true));

    scriptClick($page, $selector);

    waitForScript($page, 'Boolean(document.querySelector(".fi-modal-window"))', true);
    $submittedSingleCompletion = clickModalAction($page, 'نعم، أكملت قراءته');

    if (! $submittedSingleCompletion) {
        $page->script(athkarReaderCommandScript("data.completeThikr({$multiIndex});"));
    }

    waitForScriptWithTimeout(
        $page,
        athkarReaderDataScript(
            js_template(
                <<<'JS'
(() => {
  const targetItemId = Number({{targetItemId}});
  const index = data.activeList.findIndex((item) => Number(item?.id ?? 0) === targetItemId);

  if (index < 0) {
    return true;
  }

  return Number(data.countAt(index) ?? 0) >= Number(data.requiredCount(index) ?? 1);
})()
JS,
                ['targetItemId' => $targetItemId],
            ),
        ),
        true,
        8_000,
    );
});

it('re-arms shimmer when toggling between text and origin layers', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    enableMobileContext($page);
    waitForReaderVisible($page);
    $expectsShimmer = ! isFastBrowserMode();

    $originIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item) => String(item?.origin ?? "").trim().length > 0 || Boolean(item?.is_original))',
        ),
    );

    expect($originIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript(js_template(<<<'JS'
data.setActiveIndex({{index}});
const activeIndex = data.activeIndex;

if (!data.activeList?.[activeIndex]) {
  return;
}

data.activeList[activeIndex].text = Array.from(
  { length: 80 },
  () => 'لا إله إلا الله وحده لا شريك له'
).join(' ');
data.activeList[activeIndex].origin = Array.from(
  { length: 80 },
  () => 'حدثنا عبد الله بن مسلمة عن مالك عن سمي عن أبي صالح'
).join(' ');
data.hideOrigin();
data.queueReaderTextFit();

requestAnimationFrame(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  const text = slide?.querySelector('[data-athkar-text]');
  const origin = slide?.querySelector('[data-athkar-origin-text]');

  [text, origin].forEach((node) => {
    if (!node) {
      return;
    }

    node.dataset.shimmerDelay = '20';
    node.dataset.shimmerDuration = '120';
    node.dataset.shimmerPause = '120';
  });

  data.setupTextShimmer();
});
JS, ['index' => $originIndex])));

    waitForScript(
        $page,
        js_template(<<<'JS'
(() => {
  const text = document.querySelector('[data-athkar-slide][data-active="true"] [data-athkar-text]');
  const expectsShimmer = Boolean({{expectsShimmer}});

  if (!text) {
    return false;
  }

  if (!expectsShimmer) {
    return true;
  }

  return text.classList.contains('is-shimmering');
})()
JS, ['expectsShimmer' => $expectsShimmer]),
        true,
    );

    $page->script(athkarReaderCommandScript('data.toggleOrigin(data.activeIndex);'));

    waitForScript(
        $page,
        js_template(<<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  const origin = slide?.querySelector('[data-athkar-origin-text]');
  const isVisible = slide?.querySelector('.athkar-origin-text')?.classList.contains('is-origin-visible');
  const expectsShimmer = Boolean({{expectsShimmer}});

  if (!isVisible || !origin) {
    return false;
  }

  if (!expectsShimmer) {
    return true;
  }

  return origin.classList.contains('is-shimmering');
})()
JS, ['expectsShimmer' => $expectsShimmer]),
        true,
    );

    $page->script(athkarReaderCommandScript('data.toggleOrigin(data.activeIndex);'));

    waitForScript(
        $page,
        js_template(<<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  const text = slide?.querySelector('[data-athkar-text]');
  const isOriginVisible = slide?.querySelector('.athkar-origin-text')?.classList.contains('is-origin-visible');
  const expectsShimmer = Boolean({{expectsShimmer}});

  if (isOriginVisible || !text) {
    return false;
  }

  if (!expectsShimmer) {
    return true;
  }

  return text.classList.contains('is-shimmering');
})()
JS, ['expectsShimmer' => $expectsShimmer]),
        true,
    );
});

it('keeps non-overflowing main text centered after hiding a scrolled origin on short mobile heights', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    enableTouchContext($page, 320, 604, 'base');
    waitForScript($page, 'window.innerWidth <= 320', true);
    waitForScript($page, 'window.innerHeight <= 604', true);
    $page->script(mainMenuCommandScript('data.isTouchDevice = true;'));
    waitForReaderVisible($page);

    $originIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item) => String(item?.origin ?? "").trim().length > 0 || Boolean(item?.is_original))',
        ),
    );

    expect($originIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript(js_template(<<<'JS'
data.setActiveIndex({{index}});
const activeIndex = data.activeIndex;

if (!data.activeList?.[activeIndex]) {
  return;
}

data.activeList[activeIndex].text = 'أصبحت أثني عليك حمداً، وأشهد أن لا إله إلا الله.';
data.activeList[activeIndex].origin = Array.from(
  { length: 160 },
  () => 'حدثنا عبد الله بن مسلمة عن مالك عن سمي عن أبي صالح عن أبي هريرة رضي الله عنه'
).join(' ');
data.hideOrigin();
data.queueReaderTextFit();
JS, ['index' => $originIndex])));

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  const box = slide?.querySelector('[data-athkar-text-box]');
  const text = slide?.querySelector('[data-athkar-text]');
  const isOriginVisible = slide?.querySelector('.athkar-origin-text')?.classList.contains('is-origin-visible');

  if (!box || !text || isOriginVisible) {
    return false;
  }

  if (box.dataset.athkarTextOverflow !== 'false' || box.dataset.athkarOriginOverflow !== 'true') {
    return false;
  }

  if (box.dataset.athkarTouchScroll !== 'false' || box.classList.contains('athkar-text-box--touch-scroll')) {
    return false;
  }

  if (box.scrollTop !== 0) {
    box.scrollTop = 0;
    return false;
  }

  const boxRect = box.getBoundingClientRect();
  const textRect = text.getBoundingClientRect();
  const centerDelta = (textRect.top + textRect.height / 2) - (boxRect.top + boxRect.height / 2);
  window.__athkarShortHeightCenterDelta = centerDelta;

  return Math.abs(centerDelta) <= 22;
})()
JS,
        true,
    );

    $page->script(athkarReaderCommandScript('data.toggleOrigin(data.activeIndex);'));

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  const box = slide?.querySelector('[data-athkar-text-box]');
  const isOriginVisible = slide?.querySelector('.athkar-origin-text')?.classList.contains('is-origin-visible');

  if (!box || !isOriginVisible) {
    return false;
  }

  if (
    box.dataset.athkarScrollTarget !== 'origin' ||
    box.dataset.athkarOriginOverflow !== 'true' ||
    !box.classList.contains('athkar-text-box--touch-scroll') ||
    !box.classList.contains('athkar-text-box--origin-scroll')
  ) {
    return false;
  }

  const maxScroll = Math.max(0, box.scrollHeight - box.clientHeight);
  if (maxScroll <= 12) {
    return false;
  }

  box.scrollTop = Math.min(10, maxScroll);

  return box.scrollTop >= 4;
})()
JS,
        true,
    );

    $page->script(athkarReaderCommandScript('data.toggleOrigin(data.activeIndex);'));
    $page->script('window.dispatchEvent(new CustomEvent("fitty-refit"));');

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  const box = slide?.querySelector('[data-athkar-text-box]');
  const text = slide?.querySelector('[data-athkar-text]');
  const isOriginVisible = slide?.querySelector('.athkar-origin-text')?.classList.contains('is-origin-visible');

  if (!box || !text || isOriginVisible) {
    return false;
  }

  if (box.dataset.athkarScrollTarget !== 'text') {
    return false;
  }

  if (box.dataset.athkarTextOverflow !== 'false' || box.dataset.athkarTouchScroll !== 'false') {
    return false;
  }

  if (box.classList.contains('athkar-text-box--touch-scroll') || box.classList.contains('athkar-text-box--origin-scroll')) {
    return false;
  }

  if (box.classList.contains('py-1') || box.classList.contains('py-2')) {
    return false;
  }

  if (box.scrollTop !== 0) {
    return false;
  }

  const baseline = Number(window.__athkarShortHeightCenterDelta ?? 0);
  const boxRect = box.getBoundingClientRect();
  const textRect = text.getBoundingClientRect();
  const centerDelta = (textRect.top + textRect.height / 2) - (boxRect.top + boxRect.height / 2);

  return Math.abs(centerDelta) <= 22 && Math.abs(centerDelta - baseline) <= 10;
})()
JS,
        true,
    );

});

it('tracks progress semantics, exposes full mode athkar, and keeps the slide render window bounded', function () {
    $expectedCount = Thikr::query()
        ->whereIn('time', [ThikrTime::Shared, ThikrTime::Sabah])
        ->count();

    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);

    $page->script(athkarReaderCommandScript("data.setActiveIndex({$singleIndex});"));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    $initialLetters = $page->script(athkarReaderDataScript('data.totalCompletedLetters'));
    $initialCounts = $page->script(athkarReaderDataScript('data.totalCompletedCount'));

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 1);

    $completedLetters = $page->script(athkarReaderDataScript('data.totalCompletedLetters'));
    $completedCounts = $page->script(athkarReaderDataScript('data.totalCompletedCount'));
    $completedPercent = $page->script(athkarReaderDataScript('data.slideProgressPercent'));

    expect($completedLetters)->toBeGreaterThan($initialLetters);
    expect($completedCounts)->toBe($initialCounts + 1);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);

    $overcountLetters = $page->script(athkarReaderDataScript('data.totalCompletedLetters'));
    $overcountCounts = $page->script(athkarReaderDataScript('data.totalCompletedCount'));
    $overcountPercent = $page->script(athkarReaderDataScript('data.slideProgressPercent'));

    expect($overcountLetters)->toBe($completedLetters);
    expect($overcountCounts)->toBe($completedCounts + 1);
    expect($overcountPercent)->toBe($completedPercent);

    scriptClick($page, 'button[aria-label="التالي"]');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);

    $pageCount = $page->script(athkarReaderDataScript('data.activeIndex + 1'));
    $totalPages = $page->script(athkarReaderDataScript('data.activeList.length'));

    expect($pageCount)->toBe($singleIndex + 2);
    expect($totalPages)->toBeGreaterThanOrEqual($pageCount);

    setAthkarSettings($page, [
        'does_prevent_switching_athkar_until_completion' => true,
    ]);

    $activeCount = $page->script(athkarReaderDataScript('data.activeList.length'));

    expect($activeCount)->toBe($expectedCount);

    waitForScript(
        $page,
        athkarReaderDataScript('data.maxNavigableIndex < (data.activeList.length - 1)'),
        true,
    );

    $settings = [
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(athkarReaderCommandScript('data.setActiveIndex(data.activeList.length - 1);'));

    $lastIndex = $page->script(athkarReaderDataScript('data.activeList.length - 1'));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $lastIndex);

    setAthkarSettings($page, [
        'does_prevent_switching_athkar_until_completion' => false,
    ]);
    waitForAthkarSettings($page, [
        'does_prevent_switching_athkar_until_completion' => false,
    ]);

    waitForScript($page, athkarReaderDataScript('data.activeList.length >= 5'), true);

    waitForScript(
        $page,
        'document.querySelectorAll("[data-athkar-slide] [data-athkar-text-box]").length <= 3',
        true,
    );
    $mountedAtStart = $page->script(
        'document.querySelectorAll("[data-athkar-slide] [data-athkar-text-box]").length',
    );

    $middleIndex = (int) $page->script(athkarReaderDataScript('Math.floor(data.activeList.length / 2)'));
    $page->script(athkarReaderCommandScript("data.setActiveIndex({$middleIndex});"));
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $middleIndex);
    waitForScript(
        $page,
        'document.querySelectorAll("[data-athkar-slide] [data-athkar-text-box]").length <= 3',
        true,
    );
    $mountedAtMiddle = $page->script(
        'document.querySelectorAll("[data-athkar-slide] [data-athkar-text-box]").length',
    );

    $lastIndex = (int) $page->script(athkarReaderDataScript('data.activeList.length - 1'));
    $page->script(athkarReaderCommandScript('data.setActiveIndex(data.activeList.length - 1);'));
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $lastIndex);
    waitForScript(
        $page,
        'document.querySelectorAll("[data-athkar-slide] [data-athkar-text-box]").length <= 2',
        true,
    );
    $mountedAtEnd = $page->script(
        'document.querySelectorAll("[data-athkar-slide] [data-athkar-text-box]").length',
    );

    expect($mountedAtStart)->toBeLessThanOrEqual(2)
        ->and($mountedAtMiddle)->toBeLessThanOrEqual(3)
        ->and($mountedAtEnd)->toBeLessThanOrEqual(2);
});
