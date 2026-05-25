<?php

declare(strict_types=1);

test('native performance overrides stay scoped to base breakpoint css blocks', function () {
    $targets = [
        resource_path('views/components/main-menu/index.blade.php'),
        resource_path('views/components/partials/athkar-app/gate.blade.php'),
        resource_path('views/components/partials/quran-app/gate.blade.php'),
        resource_path('views/livewire/quran-app/reader.blade.php'),
    ];

    foreach ($targets as $path) {
        $source = file_get_contents($path);

        expect($source)->not->toBeFalse()
            ->and($source)->toContain('@media (max-width: 639px)')
            ->and($source)->toContain('.native-platform');

        $nativePosition = strpos((string) $source, '.native-platform');
        $mediaPosition = strpos((string) $source, '@media (max-width: 639px)');

        expect($nativePosition)->not->toBeFalse()
            ->and($mediaPosition)->not->toBeFalse()
            ->and($mediaPosition)->toBeLessThan($nativePosition);
    }
});

test('filament modal actions opt into muttasiq modal color override classes', function () {
    $filamentComponentCss = file_get_contents(resource_path('css/core/filament/components.css'));

    expect($filamentComponentCss)->not->toBeFalse()
        ->and($filamentComponentCss)->toContain('.fi-modal .muttasiq-modal-window')
        ->and($filamentComponentCss)->toContain('.fi-modal .muttasiq-modal-overlay')
        ->and($filamentComponentCss)->toContain('.fi-modal:not(.fi-modal-slide-over):not(.fi-width-screen)')
        ->and($filamentComponentCss)->toContain('> .fi-modal-window-ctn')
        ->and($filamentComponentCss)->toContain('> .fi-modal-window')
        ->and($filamentComponentCss)->toContain('#quran-reader-bookmarks-modal')
        ->and($filamentComponentCss)->toContain('border-radius: 1.2rem;')
        ->and($filamentComponentCss)->toContain(':where(.dark, .dark *) #quran-reader-search-modal .quran-search-input-shell');

    $controlPanelSource = file_get_contents(app_path('Livewire/ControlPanel.php'));
    $athkarManagerSource = file_get_contents(app_path('Livewire/AthkarManager.php'));
    $hiddenCompletionSource = file_get_contents(app_path('Livewire/AthkarApp/HiddenCompletionButton.php'));
    $jsErrorReporterSource = file_get_contents(app_path('Livewire/JsErrorReporter.php'));
    $quranReaderSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));

    expect($controlPanelSource)->not->toBeFalse()
        ->and($controlPanelSource)->toContain("'class' => 'muttasiq-modal-window quran-control-panel-modal-window'")
        ->and($controlPanelSource)->toContain("'class' => 'muttasiq-modal-overlay quran-control-panel-modal-overlay'")
        ->and($controlPanelSource)->toContain("'class' => 'muttasiq-modal-window quran-support-unlock-modal-window'")
        ->and($controlPanelSource)->toContain("'class' => 'muttasiq-modal-overlay quran-support-unlock-modal-overlay'");

    expect($athkarManagerSource)->not->toBeFalse()
        ->and($athkarManagerSource)->toContain("'class' => 'muttasiq-modal-window'")
        ->and($athkarManagerSource)->toContain("'class' => 'muttasiq-modal-overlay'");

    expect($hiddenCompletionSource)->not->toBeFalse()
        ->and($hiddenCompletionSource)->toContain("'id' => 'athkar-hidden-completion-modal'")
        ->and($hiddenCompletionSource)->toContain("'id' => 'athkar-hidden-single-completion-modal'")
        ->and($hiddenCompletionSource)->toContain("'class' => 'muttasiq-modal-window'")
        ->and($hiddenCompletionSource)->toContain("'class' => 'muttasiq-modal-overlay'");

    expect($jsErrorReporterSource)->not->toBeFalse()
        ->and($jsErrorReporterSource)->toContain("'id' => 'js-error-report-modal'")
        ->and($jsErrorReporterSource)->toContain("'class' => 'muttasiq-modal-window'")
        ->and($jsErrorReporterSource)->toContain("'class' => 'muttasiq-modal-overlay'");

    expect($quranReaderSource)->not->toBeFalse()
        ->and($quranReaderSource)->toContain("'id' => 'quran-reader-search-modal'")
        ->and($quranReaderSource)->toContain("'id' => 'quran-reader-jump-page-modal'")
        ->and($quranReaderSource)->toContain("'id' => self::HISTORY_MODAL_ID")
        ->and($quranReaderSource)->toContain("'id' => self::BOOKMARKS_MODAL_ID")
        ->and($quranReaderSource)->toContain("'class' => 'muttasiq-modal-window'")
        ->and($quranReaderSource)->toContain("'class' => 'muttasiq-modal-overlay'");
});

test('colorful background layers are conditionally shown instead of opacity-only toggled', function () {
    $source = file_get_contents(resource_path('views/components/partials/colorful-background.blade.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('data-testid="quran-bg-tilawa-light-layer"')
        ->and($source)->toContain('data-testid="quran-bg-tilawa-dark-layer"')
        ->and($source)->toContain('data-testid="quran-bg-hifth-light-layer"')
        ->and($source)->toContain('data-testid="quran-bg-hifth-dark-layer"')
        ->and($source)->toContain('data-testid="quran-bg-tadabbur-light-layer"')
        ->and($source)->toContain('data-testid="quran-bg-tadabbur-dark-layer"')
        ->and($source)->toContain('x-show="views[`quran-app-tilawa`].isOpen && !$store.colorScheme.isDarkModeOn"')
        ->and($source)->toContain('x-show="views[`quran-app-tilawa`].isOpen && $store.colorScheme.isDarkModeOn"')
        ->and($source)->toContain('x-show="views[`quran-app-hifth`].isOpen && !$store.colorScheme.isDarkModeOn"')
        ->and($source)->toContain('x-show="views[`quran-app-hifth`].isOpen && $store.colorScheme.isDarkModeOn"')
        ->and($source)->toContain('x-show="views[`quran-app-tadabbur`].isOpen && !$store.colorScheme.isDarkModeOn"')
        ->and($source)->toContain('x-show="views[`quran-app-tadabbur`].isOpen && $store.colorScheme.isDarkModeOn"')
        ->and($source)->toContain('opacity-(--bg-quran-tilawa-light-opacity)')
        ->and($source)->toContain('opacity-(--bg-quran-tilawa-dark-opacity)')
        ->and($source)->not->toContain("x-bind:class=\"views[`quran-app-tilawa`].isOpen && 'opacity-100!'\"")
        ->and($source)->not->toContain("x-bind:class=\"views[`quran-app-hifth`].isOpen && 'opacity-100!'\"")
        ->and($source)->not->toContain("x-bind:class=\"views[`quran-app-tadabbur`].isOpen && 'opacity-100!'\"");
});

test('quran wird mushaf page indicator keeps responsive tailwind chip sizing classes', function () {
    $quranReaderViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));

    expect($quranReaderViewSource)->not->toBeFalse()
        ->and($quranReaderViewSource)->toContain('data-quran-mushaf-page-indicator')
        ->and($quranReaderViewSource)->toContain('sm:min-w-21')
        ->and($quranReaderViewSource)->toContain('md:min-w-[5.2rem]')
        ->and($quranReaderViewSource)->toContain('lg:min-w-[5.4rem]')
        ->and($quranReaderViewSource)->toContain('xl:min-w-20')
        ->and($quranReaderViewSource)->toContain('2xl:min-w-[4.4rem]')
        ->and($quranReaderViewSource)->toContain('3xl:min-w-[5.8rem]')
        ->and($quranReaderViewSource)->toContain('4xl:min-w-[5.8rem]');
});

test('quran reader keeps fit reserve variables across sm md lg and avoids sm+ strip dark shades', function () {
    $source = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('/* sm */')
        ->and($source)->toContain('/* md */')
        ->and($source)->toContain('/* lg */')
        ->and($source)->toContain('--quran-fit-panel-stack-clearance: 0.68rem;')
        ->and($source)->toContain('--quran-fit-panel-top-reserve: 4.35rem;')
        ->and($source)->toContain('--quran-fit-panel-stack-clearance: 0.72rem;')
        ->and($source)->toContain('--quran-fit-panel-top-reserve: 4.3rem;')
        ->and($source)->toContain('--quran-fit-panel-stack-clearance: 0.74rem;')
        ->and($source)->toContain('--quran-fit-panel-top-reserve: 4.2rem;')
        ->and($source)->toContain('.dark .quran-top-strip {')
        ->and($source)->toContain('.dark .quran-bottom-strip {')
        ->and($source)->toContain('background: transparent;');
});

test('athkar gate spill visuals remain available on sm+ while containment stays base-only', function () {
    $athkarGateSource = file_get_contents(resource_path('views/components/partials/athkar-app/gate.blade.php'));

    expect($athkarGateSource)->not->toBeFalse()
        ->and($athkarGateSource)->toContain('--gate-glass-border-radius: calc(var(--radius) - 10px);')
        ->and($athkarGateSource)->toContain('.athkar-gate__spill')
        ->and($athkarGateSource)->toContain('@media (max-width: 639px) {')
        ->and($athkarGateSource)->toContain('--gate-blur: 0px;')
        ->and($athkarGateSource)->toContain('contain: layout paint;')
        ->and($athkarGateSource)->not->toContain("--gate-glass-border-radius: calc(var(--radius) - 10px);\n            contain: layout paint;");
});

test('control panel keeps base-only setting visibility and compact base spacing for general settings', function () {
    $controlPanelSettingsTabSource = file_get_contents(
        app_path('Services/Traits/HasControlPanelSettingsTab.php'),
    );

    expect($controlPanelSettingsTabSource)->not->toBeFalse()
        ->and($controlPanelSettingsTabSource)->toContain(
            'Components\\Checkbox::make(Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY)',
        )
        ->and($controlPanelSettingsTabSource)->toContain("'class' => 'relative z-20 mt-0 sm:mt-0'")
        ->and($controlPanelSettingsTabSource)->toContain(
            "'class' => 'relative z-20 sm:hidden'",
        );
});

test('control panel syncs quran wird setting ordinal with runtime visibility rules', function () {
    $controlPanelSource = file_get_contents(resource_path('views/livewire/control-panel.blade.php'));

    expect($controlPanelSource)->not->toBeFalse()
        ->and($controlPanelSource)->toContain('syncQuranWirdSettingOrdinalLabel()')
        ->and($controlPanelSource)->toContain('const shouldUseSixthOrdinal = (() => {')
        ->and($controlPanelSource)->toContain("const resolvedOrdinal = shouldUseSixthOrdinal ? '6.' : '5.';")
        ->and($controlPanelSource)->toContain('!candidate.closest(\'.quran-wird-frequency-field\')')
        ->and($controlPanelSource)->toContain('labelElement.dataset.quranWirdBaseLabel = computedBaseLabel;')
        ->and($controlPanelSource)->toContain("labelElement.dataset.controlPanelOrdinalManaged = 'true';");
});

test('quran reader requests sm+ web refit after bookmarks modal closes', function () {
    $source = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/search-and-modals-lifecycle-and-state.js'),
    );

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('queueSmPlusWebModalCloseRefit(modalId = \'\')')
        ->and($source)->toContain('!this.nativeRuntime &&')
        ->and($source)->toContain('!this.shouldUseImmersiveReaderChrome() &&')
        ->and($source)->toContain('this.queueReaderReentryRefit(72, 5);')
        ->and($source)->toContain('window.setTimeout(() => this.queueReaderReentryRefit(180, 3), 120);')
        ->and($source)->toContain('await this.runSecondaryModalExitRecoveryPulse(normalizedModalId);')
        ->and($source)->toContain('await this.ensureModalDrivenPageVisible(targetPage, {')
        ->and($source)->toContain('this.queueSmPlusWebModalCloseRefit(this.historyModalId);')
        ->and($source)->toContain('this.queueSmPlusWebModalCloseRefit(this.bookmarksModalId);');
});
