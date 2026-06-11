<?php

declare(strict_types=1);

use Native\Mobile\Traits\BuildsAndroidEmulatorLaunchArguments;
use Tests\TestCase;

uses(TestCase::class);

if (! trait_exists(BuildsAndroidEmulatorLaunchArguments::class)) {
    require_once dirname(__DIR__, 2).'/vendor/nativephp/mobile/src/Traits/BuildsAndroidEmulatorLaunchArguments.php';
}

function createAndroidLaunchTempDirectory(string $prefix = 'muttasiq-android-launch-'): string
{
    $basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefix.bin2hex(random_bytes(6));

    if (! mkdir($basePath, 0777, true) && ! is_dir($basePath)) {
        throw new RuntimeException("Unable to create temporary directory at [{$basePath}].");
    }

    return $basePath;
}

function deleteAndroidLaunchTempDirectory(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        @unlink($path);

        return;
    }

    $items = scandir($path);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        deleteAndroidLaunchTempDirectory($path.DIRECTORY_SEPARATOR.$item);
    }

    @rmdir($path);
}

function writeAndroidLaunchExecutable(string $path, string $contents): void
{
    file_put_contents($path, $contents);
    chmod($path, 0755);
}

function runAndroidLaunchCommand(string $command, array $environment, string $workingDirectory): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, $workingDirectory, $environment);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start process.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
}

function runAndroidPrepareCommand(string $command, array $environment, string $workingDirectory): array
{
    return runAndroidLaunchCommand($command, $environment, $workingDirectory);
}

function restoreAndroidLaunchEnv(string $key, string|false|null $previousValue): void
{
    if ($previousValue === false || $previousValue === null) {
        putenv($key);

        return;
    }

    putenv("{$key}={$previousValue}");
}

it('defaults to swiftshader emulator args on linux when no override is set', function () {
    $previousNativeArgs = getenv('NATIVEPHP_ANDROID_EMULATOR_ARGS');
    $previousAndroidFlags = getenv('ANDROID_EMULATOR_FLAGS');

    putenv('NATIVEPHP_ANDROID_EMULATOR_ARGS');
    putenv('ANDROID_EMULATOR_FLAGS');

    try {
        $resolver = new class
        {
            use BuildsAndroidEmulatorLaunchArguments;

            public function args(): array
            {
                return $this->resolveAndroidEmulatorLaunchArguments();
            }
        };

        expect($resolver->args())->toBe(['-gpu', 'swiftshader_indirect']);
    } finally {
        restoreAndroidLaunchEnv('NATIVEPHP_ANDROID_EMULATOR_ARGS', $previousNativeArgs);
        restoreAndroidLaunchEnv('ANDROID_EMULATOR_FLAGS', $previousAndroidFlags);
    }
});

it('passes the linux emulator args through the run-android wrapper', function () {
    $workspace = createAndroidLaunchTempDirectory();
    $projectRoot = dirname(__DIR__, 2);

    try {
        mkdir($workspace.'/.scripts/support', 0777, true);
        mkdir($workspace.'/.scripts/native/mobile/android/support', 0777, true);
        mkdir($workspace.'/bin', 0777, true);

        copy($projectRoot.'/.scripts/run-android.sh', $workspace.'/.scripts/run-android.sh');
        chmod($workspace.'/.scripts/run-android.sh', 0755);

        writeAndroidLaunchExecutable($workspace.'/.scripts/support/prepare.sh', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
exit 0
BASH);

        writeAndroidLaunchExecutable($workspace.'/.scripts/native/mobile/android/support/prepare.sh', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
exit 0
BASH);

        writeAndroidLaunchExecutable($workspace.'/bin/php', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "artisan" && "${2:-}" == "native:run" && "${3:-}" == "android" ]]; then
    printf '%s' "${NATIVEPHP_ANDROID_EMULATOR_ARGS:-}"
    exit 0
fi

echo "unexpected php invocation: $*" >&2
exit 1
BASH);

        $result = runAndroidLaunchCommand(
            'bash '.escapeshellarg($workspace.'/.scripts/run-android.sh'),
            [
                'PATH' => $workspace.'/bin:'.getenv('PATH'),
            ],
            $workspace,
        );

        expect($result['exit_code'])->toBe(0, $result['stderr']);
        expect($result['stderr'])->toBe('');
        expect(trim($result['stdout']))->toBe('-gpu swiftshader_indirect');
    } finally {
        deleteAndroidLaunchTempDirectory($workspace);
    }
});

it('reinstalls the native android project without forcing a binary cache reset', function () {
    $workspace = createAndroidLaunchTempDirectory('muttasiq-native-prepare-');
    $projectRoot = dirname(__DIR__, 2);

    try {
        mkdir($workspace.'/.scripts/native/mobile/support', 0777, true);
        mkdir($workspace.'/nativephp/android/app/src/main/java/com/nativephp/mobile/ui', 0777, true);
        mkdir($workspace.'/bin', 0777, true);

        copy($projectRoot.'/.scripts/native/mobile/support/prepare-platform.sh', $workspace.'/.scripts/native/mobile/support/prepare-platform.sh');
        chmod($workspace.'/.scripts/native/mobile/support/prepare-platform.sh', 0755);

        copy($projectRoot.'/composer.lock', $workspace.'/composer.lock');

        file_put_contents($workspace.'/nativephp.json', json_encode(['php' => ['icu' => false]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($workspace.'/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt', "<?php\n");
        file_put_contents($workspace.'/nativephp/android/old-file.txt', 'stale');

        writeAndroidLaunchExecutable($workspace.'/bin/php', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "-r" ]]; then
    exec "${REAL_PHP_BINARY:-php}" "$@"
fi

if [[ "${1:-}" == "artisan" && "${2:-}" == "native:install" ]]; then
    printf '%s' "$*" > "${WORKSPACE_DIR:-.}/native-install-args.txt"
    exit 0
fi

echo "unexpected php invocation: $*" >&2
exit 1
BASH);

        $runner = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

source "${WORKSPACE_DIR}/.scripts/native/mobile/support/prepare-platform.sh"
native_prepare_platform_install "android" "nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt"
BASH;
        file_put_contents($workspace.'/runner.sh', $runner);
        chmod($workspace.'/runner.sh', 0755);

        $result = runAndroidPrepareCommand(
            'bash '.escapeshellarg($workspace.'/runner.sh'),
            [
                'PATH' => $workspace.'/bin:'.getenv('PATH'),
                'WORKSPACE_DIR' => $workspace,
                'REAL_PHP_BINARY' => PHP_BINARY,
            ],
            $workspace,
        );

        expect($result['exit_code'])->toBe(0, $result['stderr']);
        expect($result['stderr'])->toBe('');

        $capturedArgs = trim((string) file_get_contents($workspace.'/native-install-args.txt'));
        expect($capturedArgs)->toContain('artisan native:install android');
        expect($capturedArgs)->toContain('--no-force');
        expect($capturedArgs)->not->toMatch('/(^|\\s)--force(\\s|$)/');
        expect(is_dir($workspace.'/nativephp/android'))->toBeFalse();
    } finally {
        deleteAndroidLaunchTempDirectory($workspace);
    }
});

it('reinstalls the native android project when the static php library is missing', function () {
    $workspace = createAndroidLaunchTempDirectory('muttasiq-native-prepare-libphp-');
    $projectRoot = dirname(__DIR__, 2);

    try {
        mkdir($workspace.'/.scripts/native/mobile/support', 0777, true);
        mkdir($workspace.'/nativephp/android/app/src/main/java/com/nativephp/mobile/ui', 0777, true);
        mkdir($workspace.'/nativephp/android/app/src/main/assets', 0777, true);
        mkdir($workspace.'/bin', 0777, true);

        copy($projectRoot.'/.scripts/native/mobile/support/prepare-platform.sh', $workspace.'/.scripts/native/mobile/support/prepare-platform.sh');
        chmod($workspace.'/.scripts/native/mobile/support/prepare-platform.sh', 0755);

        copy($projectRoot.'/composer.lock', $workspace.'/composer.lock');

        file_put_contents($workspace.'/nativephp.json', json_encode(['php' => ['icu' => false]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($workspace.'/nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt', "<?php\n");
        file_put_contents($workspace.'/nativephp/android/app/src/main/assets/laravel_bundle.zip', 'bundle');

        writeAndroidLaunchExecutable($workspace.'/bin/php', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

if [[ "${1:-}" == "-r" ]]; then
    exec "${REAL_PHP_BINARY:-php}" "$@"
fi

if [[ "${1:-}" == "artisan" && "${2:-}" == "native:install" ]]; then
    printf '%s' "$*" > "${WORKSPACE_DIR:-.}/native-install-args.txt"
    exit 0
fi

echo "unexpected php invocation: $*" >&2
exit 1
BASH);

        $signatureRunner = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

source "${WORKSPACE_DIR}/.scripts/native/mobile/support/prepare-platform.sh"
native_read_bundle_signature > "${WORKSPACE_DIR}/native-signature.txt"
BASH;
        file_put_contents($workspace.'/signature-runner.sh', $signatureRunner);
        chmod($workspace.'/signature-runner.sh', 0755);

        $signatureResult = runAndroidPrepareCommand(
            'bash '.escapeshellarg($workspace.'/signature-runner.sh'),
            [
                'PATH' => $workspace.'/bin:'.getenv('PATH'),
                'WORKSPACE_DIR' => $workspace,
                'REAL_PHP_BINARY' => PHP_BINARY,
            ],
            $workspace,
        );

        expect($signatureResult['exit_code'])->toBe(0, $signatureResult['stderr']);
        expect($signatureResult['stderr'])->toBe('');

        file_put_contents($workspace.'/nativephp/.nativephp-mobile-version-android', trim((string) file_get_contents($workspace.'/native-signature.txt')));

        $runner = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

source "${WORKSPACE_DIR}/.scripts/native/mobile/support/prepare-platform.sh"
native_prepare_platform_install "android" "nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt"
BASH;
        file_put_contents($workspace.'/runner.sh', $runner);
        chmod($workspace.'/runner.sh', 0755);

        $result = runAndroidPrepareCommand(
            'bash '.escapeshellarg($workspace.'/runner.sh'),
            [
                'PATH' => $workspace.'/bin:'.getenv('PATH'),
                'WORKSPACE_DIR' => $workspace,
                'REAL_PHP_BINARY' => PHP_BINARY,
            ],
            $workspace,
        );

        expect($result['exit_code'])->toBe(0, $result['stderr']);
        expect($result['stderr'])->toBe('');

        $capturedArgs = trim((string) file_get_contents($workspace.'/native-install-args.txt'));
        expect($capturedArgs)->toContain('artisan native:install android');
        expect($capturedArgs)->toContain('--no-force');
    } finally {
        deleteAndroidLaunchTempDirectory($workspace);
    }
});
