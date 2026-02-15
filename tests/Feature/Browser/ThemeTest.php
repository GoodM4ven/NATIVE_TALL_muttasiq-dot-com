<?php

declare(strict_types=1);

it('persists dark and light mode preferences via the switch button', function () {
    $page = visit('/');

    resetBrowserState($page);
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
