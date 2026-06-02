<?php

declare(strict_types=1);

it('navigates to the athkar gate, persists restored state, and handles native back to main menu then exit', function () {
    $desktopPage = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($desktopPage);
    openAthkarGate($desktopPage, false);

    waitForScript($desktopPage, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript($desktopPage, 'JSON.parse(localStorage.getItem("app-active-view"))', 'athkar-app-gate');
    waitForGateVisible($desktopPage);

    $desktopPage->refresh();

    waitForAlpineReady($desktopPage);
    waitForScript($desktopPage, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForGateVisible($desktopPage);

    $mobilePage = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($mobilePage, true);
    openAthkarGate($mobilePage, true);

    waitForScript($mobilePage, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript($mobilePage, 'window.location.hash', '#athkar-app-gate');

    $mobilePage->refresh();

    waitForAlpineReady($mobilePage);
    enableMobileContext($mobilePage);
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript($mobilePage, 'window.location.hash', '#athkar-app-gate');

    expect($mobilePage->script('window.__nativeBackAction()'))->toBeTrue();

    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');

    expect($mobilePage->script('window.__nativeBackAction()'))->toBe('exit');
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');
});
