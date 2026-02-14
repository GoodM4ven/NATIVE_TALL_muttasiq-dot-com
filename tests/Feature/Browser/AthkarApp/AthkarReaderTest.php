<?php

declare(strict_types=1);

use App\Models\Thikr;
use App\Services\Enums\ThikrTime;

it('honors auto-advance and overcount settings on tap', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_automatically_switch_completed_athkar' => true,
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

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$singleIndex}); data.setCount({$singleIndex}, 0, { allowOvercount: true });",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);

    $settings = [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$singleIndex}); data.setCount({$singleIndex}, 0, { allowOvercount: true });",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');
    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);
    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);
});

it('swipes count when setting 2 is enabled', function (bool $isMobile, string $pointerType) {
    $page = $isMobile ? visitMobile('/') : visit('/');

    resetBrowserState($page, $isMobile);
    openAthkarReader($page, 'sabah', $isMobile);

    $settings = [
        'does_clicking_switch_athkar_too' => true,
        'does_automatically_switch_completed_athkar' => true,
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

    swipeReader($page, 'forward', $pointerType);

    waitForScript($page, athkarReaderDataScript('data.countAt('.$singleIndex.')'), 1);
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);
})->with([
    'desktop' => [false, 'mouse'],
    // 'mobile' => [true, 'touch'],
]);

it('swipes only navigate without counting when setting 2 is disabled', function (bool $isMobile, string $pointerType) {
    $page = $isMobile ? visitMobile('/') : visit('/');

    resetBrowserState($page, $isMobile);
    openAthkarReader($page, 'sabah', $isMobile);

    $settings = [
        'does_clicking_switch_athkar_too' => false,
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

    $page->script(
        athkarReaderCommandScript(
            "data.setActiveIndex({$singleIndex}); data.setCount({$singleIndex}, 0, { allowOvercount: true });",
        ),
    );

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    swipeReader($page, 'forward', $pointerType);

    waitForScript($page, athkarReaderDataScript('data.countAt('.$singleIndex.')'), 0);
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex + 1);
})->with([
    'desktop' => [false, 'mouse'],
    // 'mobile' => [true, 'touch'],
]);

it('treats up and down swipes as forward navigation', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_clicking_switch_athkar_too' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    waitForScript($page, athkarReaderDataScript('data.activeList.length > 2'), true);

    $page->script(athkarReaderCommandScript('data.setActiveIndex(0);'));
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), 0);

    $page->script(
        athkarReaderCommandScript(<<<'JS'
const panel = document.querySelector('.athkar-panel[role="region"][aria-roledescription="carousel"]');
const rect = panel?.getBoundingClientRect() ?? { left: 0, top: 0, width: 200, height: 400 };
const x = rect.left + (rect.width / 2);
const startY = rect.top + (rect.height * 0.7);
const endY = rect.top + (rect.height * 0.2);
data.swipeStart({ type: 'pointerdown', pointerType: 'touch', clientX: x, clientY: startY, button: 0, target: panel });
data.swipeEnd({ type: 'pointerup', pointerType: 'touch', clientX: x, clientY: endY, button: 0, target: panel });
JS),
    );
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), 1);

    $page->script(
        athkarReaderCommandScript(<<<'JS'
const panel = document.querySelector('.athkar-panel[role="region"][aria-roledescription="carousel"]');
const rect = panel?.getBoundingClientRect() ?? { left: 0, top: 0, width: 200, height: 400 };
const x = rect.left + (rect.width / 2);
const startY = rect.top + (rect.height * 0.3);
const endY = rect.top + (rect.height * 0.8);
data.swipeStart({ type: 'pointerdown', pointerType: 'touch', clientX: x, clientY: startY, button: 0, target: panel });
data.swipeEnd({ type: 'pointerup', pointerType: 'touch', clientX: x, clientY: endY, button: 0, target: panel });
JS),
    );
    waitForScript($page, athkarReaderDataScript('data.activeIndex'), 2);
});

it('prevents swiping past incomplete athkar and allows quick navigation when disabled', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_automatically_switch_completed_athkar' => false,
        'does_clicking_switch_athkar_too' => false,
        'does_prevent_switching_athkar_until_completion' => true,
    ]);
    waitForScript($page, athkarReaderDataScript('data.settings.does_prevent_switching_athkar_until_completion'), true);

    swipeReader($page, 'forward', 'mouse');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), 0);

    $page->script(athkarReaderCommandScript('data.completeThikr(data.activeIndex);'));

    waitForScript(
        $page,
        athkarReaderDataScript('data.isItemComplete(data.activeIndex)'),
        true,
    );

    swipeReader($page, 'forward', 'mouse');

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), 1);

    $settings = [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(
        athkarReaderCommandScript('data.setActiveIndex(data.activeList.length - 1);'),
    );

    $lastIndex = $page->script(athkarReaderDataScript('data.activeList.length - 1'));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $lastIndex);
});

it('persists athkar counts, overcounts, and restores the reader on reload', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ]);

    $singleIndex = $page->script(
        athkarReaderDataScript(
            'data.activeList.findIndex((item, index) => Number(item.count ?? 1) === 1 && index < data.activeList.length - 1)',
        ),
    );

    expect($singleIndex)->toBeGreaterThanOrEqual(0);
    $targetItemId = $page->script(athkarReaderDataScript('data.activeList['.$singleIndex.']?.id ?? null'));
    expect($targetItemId)->not->toBeNull();

    $page->script(athkarReaderCommandScript("data.setActiveIndex({$singleIndex});"));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $singleIndex);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');
    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.countAt(data.activeIndex)'), 2);

    $progress = $page->script('JSON.parse(localStorage.getItem("athkar-progress-v1"))');

    expect($progress['sabah']['counts'][$singleIndex] ?? null)->toBe(2);

    waitForScript($page, 'JSON.parse(localStorage.getItem("athkar-active-mode"))', 'sabah');
    waitForScript($page, 'JSON.parse(localStorage.getItem("athkar-reader-visible"))', true);
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
    waitForReaderVisible($page);
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-sabah');
    waitForScript($page, athkarReaderDataScript('data.activeMode'), 'sabah');
    $targetItemIdExpression = js_encode($targetItemId);
    waitForScriptWithTimeout(
        $page,
        athkarReaderDataScript(
            "data.activeList.some((item) => String(item?.id ?? '') === String({$targetItemIdExpression}))",
        ),
        true,
        12_000,
    );
    $restoredIndex = $page->script(
        athkarReaderDataScript(
            "data.activeList.findIndex((item) => String(item?.id ?? '') === String({$targetItemIdExpression}))",
        ),
    );
    expect($restoredIndex)->toBeGreaterThanOrEqual(0);
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
JS, ['targetId' => $targetItemId]),
        2,
        12_000,
    );
});

it('keeps progress pinned to the same thikr id after add/remove/reorder overrides and reload', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_automatically_switch_completed_athkar' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ]);

    $activeCount = $page->script(athkarReaderDataScript('data.activeList.length'));
    expect($activeCount)->toBeGreaterThan(2);

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
        12_000,
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
            12_000,
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
            12_000,
        );
    }

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
    waitForReaderVisible($page);
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-sabah');
    waitForScript($page, athkarReaderDataScript('data.activeMode'), 'sabah');
    waitForScriptWithTimeout(
        $page,
        athkarReaderDataScript(
            "String(data.activeList[data.activeIndex]?.id ?? '') === String({$targetIdExpression})",
        ),
        true,
        12_000,
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
        12_000,
    );
});

it('returns to gate then opens athkar manager from the reader top mode button', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    waitForReaderVisible($page);

    safeClick($page, '[data-athkar-open-manager]');

    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript($page, 'window.location.hash', '#athkar-app-gate');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector(".fi-modal-window"))', true, 10_000);
    waitForScript(
        $page,
        <<<'JS'
(() => {
  const managerGrid = document.querySelector('[wire\\:sort="reorderAthkar"]');
  if (!managerGrid) {
    return false;
  }

  return getComputedStyle(managerGrid).display !== 'none';
})()
JS,
        true,
    );
});

it('opens athkar manager as a modal on mobile and restricts sorting to drag handles', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);
    enableMobileContext($page);
    waitForReaderVisible($page);
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-sabah');

    safeClick($page, '[data-athkar-open-manager]');

    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript($page, 'window.location.hash', '#athkar-app-gate');
    waitForScriptWithTimeout($page, 'Boolean(document.querySelector(".fi-modal-window"))', true, 10_000);
    waitForScript(
        $page,
        <<<'JS'
(() => {
  const modal = document.querySelector('.fi-modal.fi-modal-open');
  if (!modal) {
    return false;
  }

  return !modal.classList.contains('fi-modal-slide-over');
})()
JS,
        true,
    );
    waitForScript(
        $page,
        <<<'JS'
(() => Boolean(document.querySelector('[data-athkar-manager-card] [wire\\:sort\\:handle]')) )()
JS,
        true,
    );
    waitForScript(
        $page,
        <<<'JS'
(() => !document.querySelector('[data-athkar-manager-card][wire\\:sort\\:handle]'))()
JS,
        true,
    );
});

it('fits origin text independently and keeps the text box clear of mobile top controls', function () {
    $page = visit('/');

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
  const slide = document.querySelector('[data-athkar-slide][data-active="true"]');
  if (!slide) {
    return false;
  }

  const box = slide.querySelector('[data-athkar-text-box]');
  const counter = slide.querySelector('[data-athkar-mobile-counter] button[aria-label="العدد"]');
  const originToggle = slide.querySelector('.athkar-origin-indicator--mobile');

  if (!box || !counter || !originToggle) {
    return false;
  }

  const controlsBottom = Math.max(
    counter.getBoundingClientRect().bottom,
    originToggle.getBoundingClientRect().bottom,
  );
  const boxRect = box.getBoundingClientRect();
  const paddingTop = Number.parseFloat(getComputedStyle(box).paddingTop);
  const contentTop = boxRect.top + (Number.isFinite(paddingTop) ? paddingTop : 0);

  return contentTop >= (controlsBottom + 4);
})()
JS,
        true,
    );
});

it('restores the notice on reload and allows continuing to the reader when notice panels are enabled', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_skip_notice_panels' => false,
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    waitForReaderVisible($page);
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-sabah');
    waitForScript($page, athkarReaderDataScript('data.activeMode'), 'sabah');

    $page->refresh();

    waitForAlpineReady($page);
    waitForScript($page, athkarReaderDataScript('data.settings.does_skip_notice_panels'), false);
    waitForNoticeVisible($page);

    confirmAthkarNotice($page);

    waitForReaderVisible($page);
    waitForScript($page, athkarReaderDataScript('data.activeMode'), 'sabah');
});

it('locks completed modes on the gate unless setting 3 is disabled', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    $settings = [
        'does_skip_notice_panels' => true,
        'does_prevent_switching_athkar_until_completion' => true,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(athkarReaderCommandScript('data.markAllActiveModeComplete();'));

    waitForGateVisible($page);
    waitForScript($page, athkarReaderDataScript('data.activeMode'), null);

    waitForScript($page, athkarReaderDataScript('data.isModeComplete("sabah")'), true);
    waitForScript($page, athkarReaderDataScript('data.isModeLocked("sabah")'), true);

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const badge = document.querySelector('button[aria-label="أذكار الصباح"] [x-show="isModeComplete(\'sabah\')"]');
  if (!badge) {
    return false;
  }
  return getComputedStyle(badge).display !== 'none';
})()
JS,
        true,
    );

    waitForScript(
        $page,
        'window.location.hash === "#athkar-app-gate" || window.location.hash === ""',
        true,
    );

    scriptClick($page, 'button[aria-label="أذكار الصباح"]');

    waitForScript(
        $page,
        'window.location.hash === "#athkar-app-gate" || window.location.hash === ""',
        true,
    );

    $settings = [
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    waitForScript($page, athkarReaderDataScript('data.isModeLocked("sabah")'), false);

    scriptClick($page, 'button[aria-label="أذكار الصباح"]');

    waitForScript($page, 'window.location.hash', '#athkar-app-sabah');
    waitForNoticeVisible($page);
});

it('executes hidden completion buttons on desktop for single thikr and all athkar', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

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

    $desktopCompleteSelector = '[data-athkar-slide][data-active="true"] .sm\\:flex button[aria-label="إتمام الذكر"]';
    waitForScript(
        $page,
        js_template('Boolean(document.querySelector({{selector}}))', ['selector' => $desktopCompleteSelector]),
        true,
    );
    scriptClick($page, $desktopCompleteSelector);

    waitForScript($page, 'Boolean(document.querySelector(".fi-modal-window"))', true);
    clickModalAction($page, 'نعم، أكمل الذكر');

    waitForScript(
        $page,
        athkarReaderDataScript('data.countAt('.$multiIndex.') === data.requiredCount('.$multiIndex.')'),
        true,
    );

    $page->script(athkarReaderCommandScript('data.showCompletionHack({ pinned: true })'));

    waitForScript($page, athkarReaderDataScript('data.completionHack.isVisible'), true);

    safeClick($page, 'button[aria-label="إتمام جميع الأذكار"]');

    waitForScript($page, 'Boolean(document.querySelector(".fi-modal-window"))', true);

    clickModalAction($page, 'قرأتها');

    waitForScript($page, athkarReaderDataScript('data.isModeComplete("sabah")'), true);
    waitForScript($page, athkarReaderDataScript('data.activeMode'), null);
});

it('tracks progress by letters and counters by counts while updating page position', function () {
    $page = visit('/');

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
});

it('exposes all athkar for the active mode and navigates when switching is allowed', function () {
    $expectedCount = Thikr::query()
        ->whereIn('time', [ThikrTime::Shared, ThikrTime::Sabah])
        ->count();

    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_prevent_switching_athkar_until_completion' => true,
    ]);

    $activeCount = $page->script(athkarReaderDataScript('data.activeList.length'));

    expect($activeCount)->toBe($expectedCount);

    waitForScript($page, athkarReaderDataScript('data.maxNavigableIndex'), 0);

    $settings = [
        'does_prevent_switching_athkar_until_completion' => false,
    ];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);

    $page->script(athkarReaderCommandScript('data.setActiveIndex(data.activeList.length - 1);'));

    $lastIndex = $page->script(athkarReaderDataScript('data.activeList.length - 1'));

    waitForScript($page, athkarReaderDataScript('data.activeIndex'), $lastIndex);
});

it('shows the congrats panel briefly then returns to the gate when setting 4 is disabled', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    setAthkarSettings($page, [
        'does_skip_notice_panels' => false,
    ]);

    $page->script(athkarReaderCommandScript('data.markAllActiveModeComplete();'));

    waitForScript($page, athkarReaderDataScript('data.isCompletionVisible'), true);
    waitForScriptWithTimeout($page, athkarReaderDataScript('data.isCompletionVisible'), false, 4000);
    waitForScript($page, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript(
        $page,
        'window.location.hash === "#athkar-app-gate" || window.location.hash === ""',
        true,
    );
});

it('resets athkar progress when the day changes', function () {
    $page = visit('/');

    resetBrowserState($page);
    openAthkarReader($page, 'sabah', false);

    scriptClick($page, '[data-athkar-slide][data-active="true"] [data-athkar-tap]');

    waitForScript($page, athkarReaderDataScript('data.totalCompletedCount'), 1);

    $page->script(
        athkarReaderCommandScript('data.lastSeenDay = "2000-01-01"; data.syncDay();'),
    );

    waitForScript($page, athkarReaderDataScript('data.activeMode'), null);
    waitForScript(
        $page,
        athkarReaderDataScript('data.progress.sabah.counts.every((count) => Number(count) === 0)'),
        true,
    );
});
