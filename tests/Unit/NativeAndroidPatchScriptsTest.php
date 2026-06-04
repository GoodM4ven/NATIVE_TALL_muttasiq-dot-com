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
    $snapshotOutput = [];
    $snapshotStatus = null;

    exec('php artisan nativephp:muttasiq:patches --help', $dispatcherOutput, $dispatcherStatus);
    exec('php artisan nativephp:muttasiq:patches-android --help', $androidOutput, $androidStatus);
    exec('php artisan nativephp:muttasiq:patches-ios --help', $iosOutput, $iosStatus);
    exec('php artisan app:build-native-quran-database --help', $snapshotOutput, $snapshotStatus);

    expect($providersContents)->toContain('use Goodm4ven\\NativePatches\\NativePatchesServiceProvider;');
    expect($providersContents)->toContain('NativePatchesServiceProvider::class');
    expect($dispatcherStatus)->toBe(0);
    expect($androidStatus)->toBe(0);
    expect($iosStatus)->toBe(0);
    expect($snapshotStatus)->toBe(0);
    expect(implode("\n", $dispatcherOutput))->toContain('nativephp:muttasiq:patches');
    expect(implode("\n", $androidOutput))->toContain('nativephp:muttasiq:patches-android');
    expect(implode("\n", $iosOutput))->toContain('nativephp:muttasiq:patches-ios');
    expect(implode("\n", $snapshotOutput))->toContain('app:build-native-quran-database');
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

    expect(file_get_contents($root.'/.scripts/run-android.sh'))->toContain('COMPOSER_NO_DEV=1');

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
    expect(file_get_contents($root.'/.scripts/run-ios.sh'))->toContain('COMPOSER_NO_DEV=1');
});

test('native watch scripts include local quran broadcast via shared support runner with endpoint overrides', function () {
    $root = dirname(__DIR__, 2);
    $supportScriptPath = $root.'/.scripts/support/run-native-local-source-broadcast.sh';
    $androidWatchScriptPath = $root.'/.scripts/watch-android.sh';
    $iosWatchScriptPath = $root.'/.scripts/watch-ios.sh';
    $androidNativeWatchScriptPath = $root.'/.scripts/support/watch-android-native.sh';
    $iosNativeWatchScriptPath = $root.'/.scripts/support/watch-ios-native.sh';
    $watchmanWaitShimPath = $root.'/.scripts/support/bin/watchman-wait';
    $supportScriptContents = file_get_contents($supportScriptPath);
    $androidWatchScriptContents = file_get_contents($androidWatchScriptPath);
    $iosWatchScriptContents = file_get_contents($iosWatchScriptPath);
    $androidNativeWatchScriptContents = file_get_contents($androidNativeWatchScriptPath);
    $iosNativeWatchScriptContents = file_get_contents($iosNativeWatchScriptPath);
    $watchmanWaitShimContents = file_get_contents($watchmanWaitShimPath);

    expect($supportScriptPath)->toBeFile();
    expect($androidWatchScriptPath)->toBeFile();
    expect($iosWatchScriptPath)->toBeFile();
    expect($androidNativeWatchScriptPath)->toBeFile();
    expect($iosNativeWatchScriptPath)->toBeFile();
    expect($watchmanWaitShimPath)->toBeFile();
    expect($supportScriptContents)->toContain('NATIVE_QURAN_SNAPSHOT_META_ENDPOINT');
    expect($supportScriptContents)->toContain('NATIVE_QURAN_SNAPSHOT_DOWNLOAD_ENDPOINT');
    expect($supportScriptContents)->toContain('NATIVE_SETTINGS_ENDPOINT');
    expect($supportScriptContents)->toContain('NATIVE_ANDROID_KEEP_LOOPBACK_ENDPOINTS');
    expect($supportScriptContents)->toContain('php artisan serve');
    expect($supportScriptContents)->toContain('adb reverse');
    expect($supportScriptContents)->toContain('/api/quran-snapshot/meta');
    expect($supportScriptContents)->toContain('/api/quran-snapshot/download');
    expect($supportScriptContents)->toContain('/api/settings');
    expect($supportScriptContents)->toContain('port ${port} is already in use');
    expect($supportScriptContents)->toContain('bind_host="0.0.0.0"');
    expect($supportScriptContents)->toContain('watch-${platform}-native.sh');
    expect($supportScriptContents)->not()->toContain('.env');
    expect($androidWatchScriptContents)->toContain('.scripts/support/run-native-local-source-broadcast.sh');
    expect($androidWatchScriptContents)->toContain('android watch');
    expect($iosWatchScriptContents)->toContain('.scripts/support/run-native-local-source-broadcast.sh');
    expect($iosWatchScriptContents)->toContain('ios watch');
    expect($androidNativeWatchScriptContents)->toContain('.scripts/support/bin');
    expect($androidNativeWatchScriptContents)->toContain('watchman-wait is unavailable');
    expect($androidNativeWatchScriptContents)->toContain('watchman shutdown-server');
    expect($androidNativeWatchScriptContents)->toContain('COMPOSER_NO_DEV=1');
    expect($iosNativeWatchScriptContents)->toContain('.scripts/support/bin');
    expect($iosNativeWatchScriptContents)->toContain('watchman-wait is unavailable');
    expect($iosNativeWatchScriptContents)->toContain('watchman shutdown-server');
    expect($iosNativeWatchScriptContents)->toContain('COMPOSER_NO_DEV=1');
    expect($watchmanWaitShimContents)->toContain('watchman-wait-shim');
    expect($watchmanWaitShimContents)->toContain('watch-project');
    expect($watchmanWaitShimContents)->toContain("['query', \$watchRoot, \$queryPayload]");
});

test('native patches plugin supports ios content view patching', function () {
    $dispatcherPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/RunNativePatchesCommand.php';
    $androidCommandPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/ApplyAndroidPatchesCommand.php';
    $iosCommandPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/ApplyIosPatchesCommand.php';
    $iosTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesIosContentView.php';
    $iosNativePhpAppTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesIosNativePhpApp.php';
    $iosAppUpdateManagerTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesIosAppUpdateManager.php';
    $androidPhpWebViewTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesAndroidPhpWebViewClient.php';
    $androidPhpBridgeTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesAndroidPhpBridge.php';
    $androidPhpQueueWorkerTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesAndroidPhpQueueWorker.php';
    $androidMainActivityTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesAndroidMainActivity.php';
    $androidLaravelEnvironmentTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/PatchesAndroidLaravelEnvironment.php';
    $helpersTraitPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/Concerns/InteractsWithPatchFiles.php';

    expect(file_exists($dispatcherPath))->toBeTrue();
    expect(file_exists($androidCommandPath))->toBeTrue();
    expect(file_exists($iosCommandPath))->toBeTrue();
    expect(file_exists($iosTraitPath))->toBeTrue();
    expect(file_exists($iosNativePhpAppTraitPath))->toBeTrue();
    expect(file_exists($iosAppUpdateManagerTraitPath))->toBeTrue();
    expect(file_exists($androidPhpWebViewTraitPath))->toBeTrue();
    expect(file_exists($androidPhpBridgeTraitPath))->toBeTrue();
    expect(file_exists($androidPhpQueueWorkerTraitPath))->toBeTrue();
    expect(file_exists($androidMainActivityTraitPath))->toBeTrue();
    expect(file_exists($androidLaravelEnvironmentTraitPath))->toBeTrue();
    expect(file_exists($helpersTraitPath))->toBeTrue();

    $dispatcherContents = file_get_contents($dispatcherPath);
    $androidContents = file_get_contents($androidCommandPath);
    $iosContents = file_get_contents($iosCommandPath);
    $iosTraitContents = file_get_contents($iosTraitPath);
    $iosNativePhpAppTraitContents = file_get_contents($iosNativePhpAppTraitPath);
    $iosAppUpdateManagerTraitContents = file_get_contents($iosAppUpdateManagerTraitPath);
    $androidPhpWebViewTraitContents = file_get_contents($androidPhpWebViewTraitPath);
    $androidPhpBridgeTraitContents = file_get_contents($androidPhpBridgeTraitPath);
    $androidPhpQueueWorkerTraitContents = file_get_contents($androidPhpQueueWorkerTraitPath);
    $androidMainActivityTraitContents = file_get_contents($androidMainActivityTraitPath);
    $androidLaravelEnvironmentTraitContents = file_get_contents($androidLaravelEnvironmentTraitPath);
    $helpersTraitContents = file_get_contents($helpersTraitPath);

    expect($dispatcherContents)->toContain('nativephp:muttasiq:patches-android');
    expect($dispatcherContents)->toContain('nativephp:muttasiq:patches-ios');
    expect($androidContents)->toContain('use PatchesAndroidMainActivity;');
    expect($androidContents)->toContain('use PatchesAndroidPhpWebViewClient;');
    expect($androidContents)->toContain('use PatchesAndroidPhpBridge;');
    expect($androidContents)->toContain('use PatchesAndroidPhpQueueWorker;');
    expect($androidContents)->toContain('use PatchesAndroidWebViewManager;');
    expect($androidContents)->toContain('use PatchesAndroidLaravelEnvironment;');
    expect($androidContents)->toContain('$this->patchPhpQueueWorker($phpQueueWorkerPath);');
    expect($androidContents)->toContain('ZipArchive');
    expect($androidContents)->toContain('pruneBundledLaravelArchive');
    expect($androidContents)->toContain('laravel_bundle.zip');
    expect($androidContents)->toContain('vendor/goodm4ven/arabicable/resources/raw-data/quran/exegesis/');
    expect($androidContents)->toContain('database/native-quran-reader.sqlite');
    expect($androidContents)->toContain('database/native-quran-reader.sqlite.gz');
    expect($androidContents)->toContain('database/native-quran-reader.json');
    expect($androidContents)->toContain('public/build/manifest.json');
    expect($androidContents)->toContain('stale-build-assets=');
    expect($androidContents)->toContain('retainedBundledBuildAssetEntries');
    expect($androidContents)->not()->toContain('vendor/phpstan/');
    expect($androidContents)->not()->toContain('vendor/phpunit/');
    expect($androidContents)->not()->toContain('vendor/pestphp/');
    expect($androidContents)->not()->toContain('vendor/fakerphp/');
    expect($iosContents)->toContain('use PatchesIosContentView;');
    expect($iosContents)->toContain('use PatchesIosNativePhpApp;');
    expect($iosContents)->toContain('use PatchesIosAppUpdateManager;');
    expect($androidPhpWebViewTraitContents)->toContain('resolveBundledQpcFontFile');
    expect($androidPhpWebViewTraitContents)->toContain('resolveBundledQuranRouteAsset');
    expect($androidPhpWebViewTraitContents)->toContain('Binary asset missing from filesystem; refusing PHP fallback');
    expect($androidPhpBridgeTraitContents)->toContain('nativePersistentArtisan("about --version")');
    expect($androidPhpBridgeTraitContents)->toContain('Persistent runtime probe failed');
    expect($androidPhpBridgeTraitContents)->toContain('Persistent dispatch lost boot state');
    expect($androidPhpBridgeTraitContents)->toContain('nativePersistentShutdown()');
    expect($androidPhpQueueWorkerTraitContents)->toContain('queue:work --once -v --no-interaction');
    expect($androidPhpQueueWorkerTraitContents)->toContain('Queue worker output: ${output.take(500)}');
    expect($androidPhpQueueWorkerTraitContents)->toContain('val handledQueueWork =');
    expect($androidPhpWebViewTraitContents)->toContain('quran-surah-header-font');
    expect($androidPhpWebViewTraitContents)->toContain('quran-basmallah-font/quran-common-ligature');
    expect($androidPhpWebViewTraitContents)->not()->toContain('getLaravelPublicPath');
    expect($androidMainActivityTraitContents)->toContain('setQuranVolumeNavigationEnabled');
    expect($androidMainActivityTraitContents)->toContain('dispatchQuranVolumeButton');
    expect($androidLaravelEnvironmentTraitContents)->toContain('app:native-bootstrap --no-interaction');
    expect($androidLaravelEnvironmentTraitContents)->not()->toContain('optimize:clear');
    expect($androidLaravelEnvironmentTraitContents)->toContain('storagePublicDir');
    expect($androidLaravelEnvironmentTraitContents)->toContain('QUEUE_CONNECTION');
    expect($androidLaravelEnvironmentTraitContents)->toContain('sync');
    expect($androidLaravelEnvironmentTraitContents)->toContain('NATIVE_SETTINGS_ENDPOINT');
    expect($androidLaravelEnvironmentTraitContents)->toContain('NATIVE_QURAN_SNAPSHOT_META_ENDPOINT');
    expect($androidLaravelEnvironmentTraitContents)->toContain('NATIVE_QURAN_SNAPSHOT_DOWNLOAD_ENDPOINT');
    expect($androidLaravelEnvironmentTraitContents)->toContain('NATIVE_ANDROID_KEEP_LOOPBACK_ENDPOINTS');
    expect($androidLaravelEnvironmentTraitContents)->toContain('NATIVE_QURAN_LOCAL_LAN_IP');
    expect($androidLaravelEnvironmentTraitContents)->toContain('normalizeAndroidEndpointOverride');
    expect($androidLaravelEnvironmentTraitContents)->toContain('resolveLocalLanIpv4');
    expect($androidLaravelEnvironmentTraitContents)->toContain('logMuttasiqNativeEnvironmentSummary');
    expect($androidLaravelEnvironmentTraitContents)->not()->toContain('database/native-quran-reader.sqlite');
    expect($androidLaravelEnvironmentTraitContents)->not()->toContain('bundledQuranSnapshotFile.copyTo');
    expect($androidLaravelEnvironmentTraitContents)->toContain('dbFile.createNewFile()');
    expect($androidLaravelEnvironmentTraitContents)->toContain('skipsDormantQuranExegesis');
    expect($androidLaravelEnvironmentTraitContents)->toContain('resources/raw-data/quran/exegesis/');
    expect($iosTraitContents)->toContain('verifyIosSystemUi');
    expect($iosTraitContents)->toContain('patchIosBackHandler');
    expect($iosTraitContents)->toContain('NativePHPBackEdgeGesture');
    expect($iosTraitContents)->toContain('WKWebsiteDataStore.default()');
    expect($iosTraitContents)->toContain('window.webkit.messageHandlers.screenAwake.postMessage');
    expect($iosTraitContents)->toContain('window.AndroidBridge.setScreenAwake = function(enabled)');
    expect($iosTraitContents)->toContain('UIApplication.shared.isIdleTimerDisabled = enabled');
    expect($iosNativePhpAppTraitContents)->toContain('setenv("DB_CONNECTION", "sqlite", 1)');
    expect($iosNativePhpAppTraitContents)->toContain('app:native-bootstrap --no-interaction');
    expect($iosNativePhpAppTraitContents)->toContain('artisan migrate START (classic mode)');
    expect($iosNativePhpAppTraitContents)->toContain('artisan migrate START (persistent fallback)');
    expect($iosAppUpdateManagerTraitContents)->toContain('app:native-bootstrap');
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
    expect($script)->toContain('has_matching_repositories() {');
    expect($script)->toContain('remove_matching_repositories() {');
    expect($script)->toContain('has_matching_repository="$(has_matching_repositories)"');
    expect($script)->toContain('if [[ "${action}" == "off" ]]; then');
    expect($script)->toContain('if [[ "${action}" == "toggle" && -n "${has_matching_repository}" ]]; then');
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

test('native install scripts respect nativephp ICU configuration for mobile builds', function () {
    $root = dirname(__DIR__, 2);
    $nativephpLock = file_get_contents($root.'/nativephp.lock');
    $nativephpJson = file_exists($root.'/nativephp.json')
        ? file_get_contents($root.'/nativephp.json')
        : null;
    $sharedPrepareScript = file_get_contents($root.'/.scripts/native/mobile/support/prepare-platform.sh');
    $iosPrepareScript = file_get_contents($root.'/.scripts/native/mobile/ios/support/prepare.sh');

    expect($nativephpLock)->toContain('"icu": true');
    if (is_string($nativephpJson)) {
        expect($nativephpJson)->toContain('"icu":');
    }
    expect($sharedPrepareScript)->toContain('vendor/goodm4ven/nativephp-muttasiq-patches/src');
    expect($sharedPrepareScript)->toContain('native_prune_stale_build_assets');
    expect($sharedPrepareScript)->toContain('NATIVE_QURAN_SNAPSHOT_CLEAR_BEFORE_BUILD');
    expect($sharedPrepareScript)->toContain('[native-prepare:bundle] cleared local Quran snapshot files');
    expect($sharedPrepareScript)->toContain('database/native-quran-reader.sqlite');
    expect($sharedPrepareScript)->toContain('database/native-quran-reader.json');
    expect($sharedPrepareScript)->toContain('database/native-quran-reader.sqlite.gz');
    expect($sharedPrepareScript)->not()->toContain('app:build-native-quran-database --no-interaction');
    expect($sharedPrepareScript)->toContain('public/build/manifest.json');
    expect($sharedPrepareScript)->toContain('nativephp directory missing');
    expect($sharedPrepareScript)->toContain('native_ensure_icu_preference');
    expect($sharedPrepareScript)->toContain('install_args+=(--with-icu)');
    expect($sharedPrepareScript)->toContain('ICU-enabled PHP binaries are required by NativePHP lock/config');
    expect($sharedPrepareScript)->toContain('install signature changed');
    expect($iosPrepareScript)->toContain('native_ensure_icu_preference');
    expect($iosPrepareScript)->toContain('install_args+=(--with-icu)');
    expect($iosPrepareScript)->toContain('ICU-enabled PHP binaries are required by NativePHP lock/config');
});

test('android bundle pruning targets dormant quran exegesis and generated quran snapshots', function () {
    $androidCommandPath = dirname(__DIR__, 2).'/vendor/goodm4ven/nativephp-muttasiq-patches/src/Commands/ApplyAndroidPatchesCommand.php';

    $androidCommandContents = file_get_contents($androidCommandPath);

    expect($androidCommandContents)->toContain('vendor/goodm4ven/arabicable/resources/raw-data/quran/exegesis/');
    expect($androidCommandContents)->toContain('database/native-quran-reader.sqlite');
    expect($androidCommandContents)->toContain('database/native-quran-reader.sqlite.gz');
    expect($androidCommandContents)->toContain('database/native-quran-reader.json');
    expect($androidCommandContents)->toContain('public/build/manifest.json');
    expect($androidCommandContents)->toContain('stale-build-assets=');
    expect($androidCommandContents)->not()->toContain('vendor/phpunit/');
    expect($androidCommandContents)->not()->toContain('vendor/phpstan/');
    expect($androidCommandContents)->not()->toContain('vendor/fakerphp/');
    expect($androidCommandContents)->not()->toContain('vendor/pestphp/');
});
