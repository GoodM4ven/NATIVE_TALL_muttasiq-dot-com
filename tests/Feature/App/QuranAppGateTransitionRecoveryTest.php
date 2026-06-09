<?php

declare(strict_types=1);

it('keeps an iOS native gate media recovery hook for quran gate re-entry', function () {
    $gateScript = file_get_contents(resource_path('js/support/alpine/data/quran-app-gate.js'));
    $gateView = file_get_contents(resource_path('views/components/partials/quran-app/gate.blade.php'));

    expect($gateScript)->toBeString()
        ->toContain('gateImageSrc(path)')
        ->toContain('queueNativeGateMediaRecovery')
        ->toContain('this.isMobileNativeRuntime =')
        ->toContain('this.$root?.dataset?.nativeMobileRuntime === \'true\';')
        ->toContain("if (nextView === 'quran-app-gate')")
        ->toContain('this.queueNativeGateMediaRecovery();')
        ->toContain('this.nativeGateMediaRefreshKey += 1;')
        ->toContain('quran-app-gate-shell--native-media-recovering')
        ->toContain('nativeGateMediaRecoveryDelayMs')
        ->toContain('nativeGateMediaRecoveryHoldMs')
        ->toContain('!this.isIosNativePlatform() || !this.shouldUseMobileBasePerfMode()');

    expect($gateView)->toBeString()
        ->toContain('data-native-mobile-runtime="{{ is_platform(\'mobile\') ? \'true\' : \'false\' }}"')
        ->toContain("'quran-app-gate-shell--base-perf' => is_platform('mobile')")
        ->toContain('@if (is_platform(\'mobile\'))')
        ->toContain('@if (!is_platform(\'mobile\'))')
        ->toContain('media="(prefers-color-scheme: dark)"')
        ->toContain('src="{{ asset(\'images/background/quran/morning/tilawa.webp\') }}"')
        ->toContain('.quran-app-gate-shell.quran-app-gate-shell--native-media-recovering')
        ->toContain('visibility: visible !important;')
        ->toContain('-webkit-backface-visibility: hidden;');
});

it('renders centered touch prompts for available and locked quran gate modes', function () {
    $gateView = file_get_contents(resource_path('views/components/partials/quran-app/gate.blade.php'));

    expect($gateView)->toBeString()
        ->toContain('quran-app-sector__chip-lock--touch-prompt')
        ->toContain('quran-app-sector__chip-lock--text-only')
        ->toContain('data-quran-app-sector-touch-callout')
        ->toContain("shouldShowAvailableModePrompt('tilawa')")
        ->toContain('.quran-app-sector--tilawa .quran-app-sector__chip-lock--touch-prompt')
        ->toContain('aria-hidden="true"')
        ->toContain('{{ arabic_text(\'انقر\') }}')
        ->toContain('text-[0.78rem] sm:text-[0.84rem] md:text-[0.92rem] lg:text-[0.95rem] xl:text-[0.78rem] 2xl:text-[0.84rem]')
        ->toContain('justify-center')
        ->toContain('z-index: 6;')
        ->toContain('top: 39%;')
        ->toContain('text-shadow: 0 2px 8px rgba(8, 4, 2, 0.52);');

    expect(substr_count($gateView, 'data-quran-app-sector-touch-callout'))->toBe(1)
        ->and(substr_count($gateView, 'quran-app-sector__chip-lock--text-only quran-app-sector__chip-lock--touch-prompt'))->toBe(1)
        ->and(substr_count($gateView, "shouldShowAvailableModePrompt('"))->toBe(1);
});
