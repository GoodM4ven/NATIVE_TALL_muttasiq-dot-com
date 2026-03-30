<?php

declare(strict_types=1);

use Pest\Browser\Execution;
use Pest\Browser\Playwright\Playwright;

const BROWSER_SETUP_TIMEOUT_MS = 1500;
const PLAYWRIGHT_SIGTERM_FALLBACK = 15;

if (! defined('SIGTERM')) {
    define('SIGTERM', PLAYWRIGHT_SIGTERM_FALLBACK);
}

function isBrowserPluginEnabled(): bool
{
    return filter_var(env('PEST_ENABLE_BROWSER_PLUGIN', true), FILTER_VALIDATE_BOOL);
}

function runPlaywrightBrowserPreflight(): void
{
    if (DIRECTORY_SEPARATOR === '\\') {
        return;
    }

    if (! function_exists('exec')) {
        return;
    }

    /** @var array<int, string> $disabledFunctions */
    $disabledFunctions = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

    if (in_array('exec', $disabledFunctions, true)) {
        return;
    }

    $scriptPath = dirname(__DIR__, 3).'/.scripts/testing/support/preflight.sh';

    if (! file_exists($scriptPath) || ! is_executable($scriptPath)) {
        return;
    }

    exec(escapeshellarg($scriptPath).' >/dev/null 2>&1');
}

function assertBrowserAssetsReady(): void
{
    if (filter_var(env('SKIP_VITE_ASSET_PREFLIGHT', false), FILTER_VALIDATE_BOOL)) {
        return;
    }

    $basePath = dirname(__DIR__, 3).'/public';
    $manifestPath = $basePath.'/build/manifest.json';
    $hotPath = $basePath.'/hot';

    if (file_exists($manifestPath)) {
        return;
    }

    if (! file_exists($hotPath)) {
        throw new Exception('Browser tests require Vite assets. Run npm run build or npm run dev.');
    }
}

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

function isFastBrowserMode(): bool
{
    return filter_var(env('BROWSER_TEST_FAST_MODE', false), FILTER_VALIDATE_BOOL);
}

function testRetrySleepMicroseconds(): int
{
    return isFastBrowserMode() ? 20_000 : 200_000;
}

function waitForScript($page, string $expression, mixed $expected = true): void
{
    Execution::instance()->waitForExpectation(
        function () use ($page, $expression, $expected): void {
            $actual = null;

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $actual = $page->script($expression);

                    break;
                } catch (Throwable $exception) {
                    if ($attempt === 2) {
                        $underlyingMessage = trim((string) $exception->getMessage());
                        $details = $underlyingMessage !== '' ? ' | cause: '.$underlyingMessage : '';

                        throw new RuntimeException(
                            'Browser script execution failed for expression: '.$expression.$details,
                            previous: $exception,
                        );
                    }

                    usleep(testRetrySleepMicroseconds());
                }
            }

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
    waitForScriptWithTimeout($page, appReadyScript(), true, browserSetupTimeoutMs());
}

function applyTestSpeedups($page): void
{
    try {
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
    } catch (Throwable) {
        //
    }
}

function appReadyScript(): string
{
    return <<<'JS'
(() => {
  if (!window.Alpine || !window.Alpine.store) {
    return false;
  }
  if (!document.querySelector('[data-main-menu-item]')) {
    return false;
  }

  return true;
})()
JS;
}

function browserSetupTimeoutMs(): int
{
    return max(Playwright::timeout(), BROWSER_SETUP_TIMEOUT_MS);
}

function safeBrowserResize($page, int $width, int $height): void
{
    $attempts = 2;

    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        try {
            $page->resize($width, $height);

            return;
        } catch (Throwable) {
            usleep(testRetrySleepMicroseconds());
        }
    }
}

function enableTouchContext($page, int $width, int $height, string $breakpoint): void
{
    safeBrowserResize($page, $width, $height);

    $page->script(js_template(<<<'JS'
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
  document.documentElement.style.setProperty('--breakpoint', {{breakpoint}});
  if (window.Alpine?.store?.('bp')) {
    window.Alpine.store('bp').current = {{breakpoint}};
  }
  window.dispatchEvent(new Event('resize'));
  window.dispatchEvent(new Event('orientationchange'));
})();
JS, ['breakpoint' => $breakpoint]));
}

function enableMobileContext($page): void
{
    enableTouchContext($page, 375, 812, 'base');

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

function enableTabletContext($page): void
{
    enableTouchContext($page, 834, 1112, 'md');

    waitForScript($page, "Boolean(window.Alpine && window.Alpine.store && window.Alpine.store('bp'))", true);
    waitForScript($page, "window.Alpine.store('bp').current", 'md');
    waitForScript($page, 'window.innerWidth >= 768', true);

    $page->script(mainMenuCommandScript('data.isTouchDevice = true;'));
}

function visitMobile(string $path = '/')
{
    return visit($path);
}

function resetBrowserState($page, bool $isMobile = false): void
{
    $previousTimeout = Playwright::timeout();
    Playwright::setTimeout(browserSetupTimeoutMs());

    try {
        if ($isMobile) {
            safeBrowserResize($page, 375, 812);
        }

        try {
            $page->script(<<<'JS'
(() => {
  window.__disableJsErrorReporting = true;
  localStorage.clear();
  sessionStorage.clear();

  try {
    Storage.prototype.setItem = () => {};
    Storage.prototype.removeItem = () => {};
    Storage.prototype.clear = () => {};
  } catch (e) {
    // ignore
  }

  window.history.replaceState({}, document.title, window.location.pathname + window.location.search);
})()
JS);
            $page->refresh();
        } catch (Throwable) {
            //
        }

        waitForAlpineReady($page);
        applyTestSpeedups($page);

        try {
            $page->script('window.__disableJsErrorReporting = true;');
        } catch (Throwable) {
            //
        }

        if ($isMobile) {
            enableMobileContext($page);
        }

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
    } finally {
        Playwright::setTimeout($previousTimeout);
    }
}
