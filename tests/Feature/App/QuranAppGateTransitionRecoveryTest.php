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
