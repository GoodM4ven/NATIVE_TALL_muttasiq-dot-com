<?php

declare(strict_types=1);

it('submits captured browser errors through the reporting modal while ignoring known resize-observer noise', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    waitForScript($page, 'Boolean(window.Livewire)', true);

    $page->script(<<<'JS'
(() => {
  window.__jsErrorReportSubmitted = null;

  window.addEventListener('js-error-report-submitted', (event) => {
    window.__jsErrorReportSubmitted = event?.detail?.reportId ?? true;
  });

  if (window.Livewire?.on) {
    window.Livewire.on('js-error-report-submitted', (payload) => {
      const reportId = payload?.reportId ?? payload?.detail?.reportId ?? true;
      window.__jsErrorReportSubmitted = reportId;
    });
  }
})();
JS);

    $page->script(<<<'JS'
(() => {
  try {
    Object.defineProperty(window.location, 'reload', {
      configurable: true,
      value: () => {
        window.__testReloadCount = Number(window.__testReloadCount ?? 0) + 1;
      },
    });
  } catch (error) {
    window.__testReloadOverrideFailed = true;
  }
})();
JS);

    $dispatched = (bool) $page->script(<<<'JS'
(() => {
  window.dispatchEvent(new CustomEvent('open-js-error-report-modal', {
    detail: {
      errors: [{
        type: 'error',
        time: new Date().toISOString(),
        message: 'Browser test synthetic failure',
        source: `${window.location.origin}/build/assets/app-test.js`,
        line: 37,
        column: 12,
        stack: 'TypeError: test',
      }],
      context: {
        url: window.location.href,
        user_agent: navigator.userAgent,
        language: navigator.language,
        platform: navigator.platform,
      },
    },
  }));

  return true;
})()
JS);

    expect($dispatched)->toBeTrue();

    waitForScriptWithTimeout($page, 'Boolean(document.querySelector(".fi-modal-window"))', true, 8_000);
    $page->assertSee('حدث خلل غير متوقع في المنصة');

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const technicalField = Array.from(document.querySelectorAll('.fi-modal-window textarea'))
    .find((field) => field.disabled);

  if (!technicalField) {
    return false;
  }

  const text = String(technicalField.value ?? '').trim();

  return text.includes('Browser test synthetic failure') && text.includes('/build/assets/app-test.js');
})()
JS,
        true,
    );

    $filled = (bool) $page->script(<<<'JS'
(() => {
  const userNoteField = Array.from(document.querySelectorAll('.fi-modal-window textarea'))
    .find((field) => !field.disabled);

  if (!userNoteField) {
    return false;
  }

  userNoteField.focus();
  userNoteField.value = 'كنت أفتح صفحة الأذكار ثم ظهر الخطأ بشكل مفاجئ.';
  userNoteField.dispatchEvent(new Event('input', { bubbles: true }));
  userNoteField.dispatchEvent(new Event('change', { bubbles: true }));
  userNoteField.blur();

  return true;
})()
JS);

    expect($filled)->toBeTrue();

    waitForScript(
        $page,
        <<<'JS'
(() => {
  const modal = document.querySelector('.fi-modal-window');
  const submit = modal?.querySelector('button[type="submit"]');

  if (!submit) {
    return false;
  }

  return submit.disabled !== true && submit.getAttribute('aria-disabled') !== 'true';
})()
JS,
        true,
    );

    $clicked = clickModalAction($page, 'إرسال البلاغ');
    expect($clicked)->toBeTrue();

    $closed = clickModalAction($page, 'إغلاق');
    expect($closed)->toBeTrue();
    waitForScriptWithTimeout($page, modalClosedScript(), true, 8_000);
    $page->script(<<<'JS'
(() => {
  document.documentElement.dataset.disableJsErrorReporting = 'false';
  window.__disableJsErrorReporting = false;
  localStorage.removeItem('jsErrorLog');
  return true;
})()
JS);

    $entriesCount = (int) $page->script(<<<'JS'
(() => {
  window.dispatchEvent(new ErrorEvent('error', {
    message: 'ResizeObserver loop completed with undelivered notifications.',
    filename: `${window.location.origin}/#athkar-app-gate`,
    lineno: 0,
    colno: 0,
  }));

  const entries = JSON.parse(localStorage.getItem('jsErrorLog') || '[]');
  return Array.isArray(entries) ? entries.length : -1;
})()
JS);

    expect($entriesCount)->toBe(0);

    waitForScript(
        $page,
        'Boolean(document.querySelector(".fi-modal-window"))',
        false,
    );
});
