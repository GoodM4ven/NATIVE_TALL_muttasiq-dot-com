<?php

use App\Providers\NativeServiceProvider;

/**
 * @return array{name?: string, version?: string}
 */
function nativePatchesLockPackage(): array
{
    $lockFile = dirname(__DIR__, 2).'/composer.lock';

    /** @var array{packages?: array<int, array{name?: string, version?: string}>} $lock */
    $lock = json_decode(file_get_contents($lockFile), true, flags: JSON_THROW_ON_ERROR);

    foreach ($lock['packages'] ?? [] as $package) {
        if (($package['name'] ?? null) === 'goodm4ven/nativephp-muttasiq-patches') {
            return $package;
        }
    }

    return [];
}

test('native patches plugin is registered for android builds', function () {
    $provider = new NativeServiceProvider(app());
    $plugins = $provider->plugins();

    expect($plugins)->toContain('Goodm4ven\\NativePatches\\NativePatchesServiceProvider');
});

test('native patches hook command is registered with artisan', function () {
    $providersPath = dirname(__DIR__, 2).'/bootstrap/providers.php';
    $providersContents = file_get_contents($providersPath);
    $dispatcherOutput = [];
    $dispatcherStatus = null;
    $androidOutput = [];
    $androidStatus = null;
    $iosOutput = [];
    $iosStatus = null;

    exec('php artisan nativephp:muttasiq:patches --help', $dispatcherOutput, $dispatcherStatus);
    exec('php artisan nativephp:muttasiq:patches-android --help', $androidOutput, $androidStatus);
    exec('php artisan nativephp:muttasiq:patches-ios --help', $iosOutput, $iosStatus);

    expect($providersContents)->toContain('use Goodm4ven\\NativePatches\\NativePatchesServiceProvider;');
    expect($providersContents)->toContain('NativePatchesServiceProvider::class');
    expect($dispatcherStatus)->toBe(0);
    expect($androidStatus)->toBe(0);
    expect($iosStatus)->toBe(0);
    expect(implode("\n", $dispatcherOutput))->toContain('nativephp:muttasiq:patches');
    expect(implode("\n", $androidOutput))->toContain('nativephp:muttasiq:patches-android');
    expect(implode("\n", $iosOutput))->toContain('nativephp:muttasiq:patches-ios');
});

test('native run script relies on plugin patches', function () {
    $root = dirname(__DIR__, 2);
    $androidScripts = [
        $root.'/.scripts/run-android.sh',
        $root.'/.scripts/watch-android.sh',
        $root.'/.scripts/share-android.sh',
    ];

    foreach ($androidScripts as $script) {
        expect(file_exists($script))->toBeTrue();

        $contents = file_get_contents($script);

        expect($contents)->not()->toContain('.scripts/native/mobile/android/patches/');
        expect($contents)->not()->toContain('.scripts/native/mobile/support/patches/edge-components.sh');
    }

    $nativeShareContents = file_get_contents($root.'/.scripts/share-android.sh');
    expect($nativeShareContents)->toContain('.scripts/native/mobile/support/patches/jump-status-texts.sh');
});

test('native ios scripts rely on plugin patches', function () {
    $root = dirname(__DIR__, 2);
    $iosScripts = [
        $root.'/.scripts/run-ios.sh',
        $root.'/.scripts/watch-ios.sh',
        $root.'/.scripts/share-ios.sh',
    ];

    foreach ($iosScripts as $script) {
        expect(file_exists($script))->toBeTrue();

        $contents = file_get_contents($script);

        expect($contents)->not()->toContain('.scripts/native/mobile/ios/patches/');
        expect($contents)->not()->toContain('.scripts/native/mobile/support/patches/edge-components.sh');
    }

    expect(file_exists($root.'/.scripts/native/mobile/ios/patches/back-handler.sh'))->toBeFalse();
    expect(file_exists($root.'/.scripts/native/mobile/ios/patches/system-ui.sh'))->toBeFalse();
    expect(file_exists($root.'/.scripts/native/mobile/support/patches/edge-components.sh'))->toBeFalse();

    $nativeShareContents = file_get_contents($root.'/.scripts/share-ios.sh');
    expect($nativeShareContents)->toContain('.scripts/native/mobile/support/patches/jump-status-texts.sh');
});

test('native patches plugin supports ios content view patching', function () {
    $dispatcherPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/RunNativePatchesCommand.php';
    $androidCommandPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/ApplyAndroidPatchesCommand.php';
    $iosCommandPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/ApplyIosPatchesCommand.php';
    $iosTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesIosContentView.php';
    $iosNativePhpAppTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesIosNativePhpApp.php';
    $androidPhpWebViewTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesAndroidPhpWebViewClient.php';
    $helpersTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/InteractsWithPatchFiles.php';

    expect(file_exists($dispatcherPath))->toBeTrue();
    expect(file_exists($androidCommandPath))->toBeTrue();
    expect(file_exists($iosCommandPath))->toBeTrue();
    expect(file_exists($iosTraitPath))->toBeTrue();
    expect(file_exists($iosNativePhpAppTraitPath))->toBeTrue();
    expect(file_exists($androidPhpWebViewTraitPath))->toBeTrue();
    expect(file_exists($helpersTraitPath))->toBeTrue();

    $dispatcherContents = file_get_contents($dispatcherPath);
    $androidContents = file_get_contents($androidCommandPath);
    $iosContents = file_get_contents($iosCommandPath);
    $iosTraitContents = file_get_contents($iosTraitPath);
    $iosNativePhpAppTraitContents = file_get_contents($iosNativePhpAppTraitPath);
    $androidPhpWebViewTraitContents = file_get_contents($androidPhpWebViewTraitPath);
    $helpersTraitContents = file_get_contents($helpersTraitPath);

    expect($dispatcherContents)->toContain('nativephp:muttasiq:patches-android');
    expect($dispatcherContents)->toContain('nativephp:muttasiq:patches-ios');
    expect($androidContents)->toContain('use PatchesAndroidMainActivity;');
    expect($androidContents)->toContain('use PatchesAndroidPhpWebViewClient;');
    expect($androidContents)->toContain('use PatchesAndroidWebViewManager;');
    expect($androidContents)->toContain('use PatchesAndroidLaravelEnvironment;');
    expect($iosContents)->toContain('use PatchesIosContentView;');
    expect($iosContents)->toContain('use PatchesIosNativePhpApp;');
    expect($androidPhpWebViewTraitContents)->toContain('resolveBundledQpcFontFile');
    expect($androidPhpWebViewTraitContents)->toContain('Binary asset missing from filesystem; refusing PHP fallback');
    expect($iosTraitContents)->toContain('verifyIosSystemUi');
    expect($iosTraitContents)->toContain('patchIosBackHandler');
    expect($iosTraitContents)->toContain('NativePHPBackEdgeGesture');
    expect($iosTraitContents)->toContain('WKWebsiteDataStore.default()');
    expect($iosNativePhpAppTraitContents)->toContain('setenv("DB_CONNECTION", "sqlite", 1)');
    expect($iosNativePhpAppTraitContents)->toContain('artisan migrate START (classic mode)');
    expect($iosNativePhpAppTraitContents)->toContain('artisan migrate START (persistent fallback)');
    expect($helpersTraitContents)->toContain('setSwiftFunctionBody');
    expect($helpersTraitContents)->toContain('locateSwiftFunction');
});

test('app service provider leaves livewire routes untouched', function () {
    $providerPath = dirname(__DIR__, 2).'/app/Providers/AppServiceProvider.php';

    expect(file_exists($providerPath))->toBeTrue();

    $providerContents = file_get_contents($providerPath);

    expect($providerContents)->not()->toContain('configureNativeMobileLivewireRoutes');
    expect($providerContents)->not()->toContain('Livewire::setUpdateRoute(');
    expect($providerContents)->not()->toContain('Livewire::setScriptRoute(');
});

test('native patches package stays pinned in app dependencies', function () {
    $composerPath = dirname(__DIR__, 2).'/composer.json';

    /** @var array{require?: array<string, string>} $composer */
    $composer = json_decode(file_get_contents($composerPath), true, flags: JSON_THROW_ON_ERROR);
    $lockedPackage = nativePatchesLockPackage();
    $packageConstraint = (string) ($composer['require']['goodm4ven/nativephp-muttasiq-patches'] ?? '');

    expect($composer['require'] ?? [])
        ->toHaveKey('goodm4ven/nativephp-muttasiq-patches');

    expect($packageConstraint)->not()->toBe('');

    expect($lockedPackage)
        ->toMatchArray(['name' => 'goodm4ven/nativephp-muttasiq-patches']);

    preg_match('/(\d+)/', $packageConstraint, $constraintMajorMatch);

    $lockedVersion = (string) ($lockedPackage['version'] ?? '');
    preg_match('/^v?(\d+)/', $lockedVersion, $lockedMajorMatch);

    expect($constraintMajorMatch[1] ?? null)->toBe('1');
    expect($lockedMajorMatch[1] ?? null)->toBe($constraintMajorMatch[1] ?? null);
});

test('composer local plugin switch script toggles the muttasiq patches package by default', function () {
    $root = dirname(__DIR__, 2);
    $script = file_get_contents($root.'/.scripts/composer-local-plugins-switch.sh');

    expect($script)->toContain('goodm4ven/nativephp-muttasiq-patches');
    expect($script)->toContain('${HOME}/Code/LaravelPackages/NATIVE_PLUGIN_muttasiq-patches');
    expect($script)->toContain('action="toggle"');
    expect($script)->toContain('if [[ "${1:-}" == "on" || "${1:-}" == "off" || "${1:-}" == "toggle" ]]; then');
    expect($script)->toContain('matching_repository_keys="$(find_matching_repository_keys)"');
    expect($script)->toContain('while IFS= read -r matching_repository_key; do');
    expect($script)->toContain('composer config --unset "repositories.${matching_repository_key}"');
    expect($script)->toContain('if [[ "${action}" == "off" ]]; then');
    expect($script)->toContain('if [[ "${action}" == "toggle" && -n "${matching_repository_keys}" ]]; then');
    expect($script)->toContain('composer config "repositories.${repository_key}" --json "$(cat <<JSON');
    expect($script)->toContain('"type": "path"');
    expect($script)->toContain('"${package_name}": "${local_forced_version}"');
    expect($script)->toContain('run_package_update');
    expect($script)->toContain('composer update "${package_name}" --with-dependencies');
});

test('android log script writes into storage logs', function () {
    $root = dirname(__DIR__, 2);
    $scriptPath = $root.'/.scripts/log-android.sh';
    $script = file_get_contents($scriptPath);

    expect($scriptPath)->toBeFile();
    expect($script)->toBeString();
    expect($script)->toContain('output_dir="${project_root}/storage/logs"');
    expect($script)->toContain('output_file="${output_dir}/native-log-android.txt"');
});
