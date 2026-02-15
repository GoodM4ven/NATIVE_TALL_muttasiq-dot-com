<?php

declare(strict_types=1);

it('renders the main menu and shows hover captions', function () {
    $page = visit('/');

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
});

it('shows settings and color scheme buttons by default', function () {
    $page = visit('/');

    resetBrowserState($page);

    $page
        ->assertVisible('[data-stack-item][x-data] [data-testid="settings-button"]')
        ->assertVisible('[data-testid="color-scheme-switch-button"]');
});

it('adds the main menu hash on a fresh load', function () {
    $page = visit('/');

    resetBrowserState($page);

    waitForScript($page, 'window.location.hash', '#main-menu');
    waitForScript($page, 'JSON.parse(localStorage.getItem("app-active-view"))', 'main-menu');
});

it('stacks only visible quick-stack items when respecting stack mode', function () {
    $page = visit('/');

    resetBrowserState($page);

    $snapshot = null;
    $lastResult = null;

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        /** @var array<string, mixed>|null $result */
        $result = $page->script(<<<'JS'
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

        usleep(200_000);
    }

    expect($snapshot)->toBeArray('Last snapshot payload: '.var_export($lastResult, true));
    expect((bool) ($snapshot['ready'] ?? false))->toBeTrue();
    expect((int) ($snapshot['allCount'] ?? 0))->toBeGreaterThan((int) ($snapshot['visibleCount'] ?? 0));
    expect((bool) ($snapshot['hasNullTransforms'] ?? true))->toBeFalse();
    expect((float) ($snapshot['maxOffset'] ?? 0.0))
        ->toBeLessThanOrEqual((float) ($snapshot['expectedMaxOffset'] ?? 0.0) + 0.15);
});
