<?php

declare(strict_types=1);

use App\Models\Setting;

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

it('handles athkar notice selection, confirmation/swipe transitions, and restored mobile back flow', function () {
    $page = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($page);
    openAthkarGate($page, false);
    $settings = [Setting::DOES_SKIP_GUIDANCE_PANELS => false];
    setAthkarSettings($page, $settings);
    waitForAthkarSettings($page, $settings);
    openAthkarNotice($page, 'sabah', false);

    waitForScript($page, 'window.location.hash', '#athkar-app-sabah');
    waitForNoticeVisible($page);

    confirmAthkarNotice($page);
    waitForReaderVisible($page);

    openAthkarGate($page, false);
    openAthkarNotice($page, 'sabah', false);
    waitForNoticeVisible($page);

    swipeNotice($page, 'back', 'mouse');

    waitForScript($page, athkarReaderDataScript('data.isNoticeVisible'), false);
    waitForScript($page, athkarReaderDataScript('data.activeMode'), null);
    waitForGateVisible($page);

    openAthkarNotice($page, 'sabah', false);
    waitForNoticeVisible($page);

    swipeNotice($page, 'forward', 'mouse');

    waitForReaderVisible($page);

    $mobilePage = visit('/', ['waitUntil' => 'domcontentloaded']);

    resetBrowserState($mobilePage, true);
    openAthkarReader($mobilePage, 'sabah', true);

    $mobileSettings = [
        Setting::DOES_SKIP_GUIDANCE_PANELS => false,
        Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION => false,
    ];
    setAthkarSettings($mobilePage, $mobileSettings);
    waitForAthkarSettings($mobilePage, $mobileSettings);

    waitForReaderVisible($mobilePage);
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'athkar-app-sabah');

    $mobilePage->refresh();

    waitForAlpineReady($mobilePage);
    enableMobileContext($mobilePage);
    waitForNoticeVisible($mobilePage);
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'athkar-app-sabah');

    swipeNotice($mobilePage, 'forward', 'touch');

    waitForReaderVisible($mobilePage);
    waitForScript($mobilePage, athkarReaderDataScript('data.isNoticeVisible'), false);
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'athkar-app-sabah');

    expect($mobilePage->script('window.__nativeBackAction()'))->toBeTrue();
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'athkar-app-gate');
    waitForScript($mobilePage, 'window.location.hash', '#athkar-app-gate');

    expect($mobilePage->script('window.__nativeBackAction()'))->toBeTrue();
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');

    expect($mobilePage->script('window.__nativeBackAction()'))->toBe('exit');
    waitForScript($mobilePage, homeDataScript('data.activeView'), 'main-menu');
    waitForScript($mobilePage, 'window.location.hash', '#main-menu');
});
