<?php

declare(strict_types=1);

function createTemporaryDirectory(string $prefix = 'muttasiq-publish-android-'): string
{
    $basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefix.bin2hex(random_bytes(6));

    if (! mkdir($basePath, 0777, true) && ! is_dir($basePath)) {
        throw new RuntimeException("Unable to create temporary directory at [{$basePath}].");
    }

    return $basePath;
}

function recursiveDelete(string $path): void
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

        recursiveDelete($path.DIRECTORY_SEPARATOR.$item);
    }

    @rmdir($path);
}

function writeExecutable(string $path, string $contents): void
{
    file_put_contents($path, $contents);
    chmod($path, 0755);
}

function readEnvValue(string $envFile, string $key): ?string
{
    $contents = file_get_contents($envFile);
    if ($contents === false) {
        return null;
    }

    if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches)) {
        return null;
    }

    $value = trim($matches[1]);
    $value = trim($value, "\"'");

    return $value;
}

function runCommand(string $command, array $environment, string $workingDirectory): array
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

it('packages the released version and only advances the build number once', function () {
    $workspace = createTemporaryDirectory();
    $projectRoot = dirname(__DIR__, 2);

    try {
        mkdir($workspace.'/.scripts/support', 0777, true);
        mkdir($workspace.'/.scripts/native/mobile/android/support', 0777, true);
        mkdir($workspace.'/.credentials', 0777, true);
        mkdir($workspace.'/bin', 0777, true);

        copy($projectRoot.'/.scripts/publish-android.sh', $workspace.'/.scripts/publish-android.sh');
        chmod($workspace.'/.scripts/publish-android.sh', 0755);

        writeExecutable($workspace.'/.scripts/support/prepare.sh', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
exit 0
BASH);

        writeExecutable($workspace.'/.scripts/native/mobile/android/support/prepare.sh', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
exit 0
BASH);

        file_put_contents(
            $workspace.'/.env',
            implode("\n", [
                'NATIVEPHP_APP_VERSION="1.2.1"',
                'NATIVEPHP_APP_VERSION_CODE=6',
                'ANDROID_KEYSTORE_FILE="'.$workspace.'/.credentials/app-release-key.jks"',
                'ANDROID_KEYSTORE_PASSWORD="password"',
                'ANDROID_KEY_ALIAS="app-key"',
                'ANDROID_KEY_PASSWORD="password"',
                '',
            ]),
        );

        touch($workspace.'/.credentials/app-release-key.jks');
        touch($workspace.'/.credentials/upload_cert.der');

        writeExecutable($workspace.'/bin/php', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

workspace_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
env_file="${workspace_dir}/.env"

read_env_value() {
    local key="$1"
    local file="$2"
    local line

    line="$(grep -E "^${key}=" "${file}" | tail -n 1 || true)"
    if [[ -z "${line}" ]]; then
        return 1
    fi

    line="${line#*=}"
    line="${line%\"}"
    line="${line#\"}"
    line="${line%\'}"
    line="${line#\'}"

    printf '%s' "${line}"
}

if [[ "${1:-}" == "-r" ]]; then
    exit 0
fi

if [[ "${1:-}" == "artisan" && "${2:-}" == "app:native-bootstrap" ]]; then
    exit 0
fi

if [[ "${1:-}" == "artisan" && "${2:-}" == "native:release" ]]; then
    release_type="${3:-}"

    case "${release_type}" in
        patch)
            new_version="1.2.2"
            ;;
        minor)
            new_version="1.3.0"
            ;;
        major)
            new_version="2.0.0"
            ;;
        *)
            echo "unexpected release type: ${release_type}" >&2
            exit 1
            ;;
    esac

    sed -i -E "s/^NATIVEPHP_APP_VERSION=.*/NATIVEPHP_APP_VERSION=\"${new_version}\"/" "${env_file}"
    exit 0
fi

if [[ "${1:-}" == "artisan" && "${2:-}" == "native:package" ]]; then
    effective_version="${NATIVEPHP_APP_VERSION:-$(read_env_value NATIVEPHP_APP_VERSION "${env_file}")}"
    effective_version_code="${NATIVEPHP_APP_VERSION_CODE:-$(read_env_value NATIVEPHP_APP_VERSION_CODE "${env_file}")}"

    printf '%s' "${effective_version}" > "${workspace_dir}/packaged-version.txt"
    printf '%s' "${effective_version_code}" > "${workspace_dir}/packaged-version-code.txt"

    next_version_code="$((effective_version_code + 1))"
    sed -i -E "s/^NATIVEPHP_APP_VERSION_CODE=.*/NATIVEPHP_APP_VERSION_CODE=${next_version_code}/" "${env_file}"
    exit 0
fi

echo "unexpected php invocation: $*" >&2
exit 1
BASH);

        writeExecutable($workspace.'/bin/keytool', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

case "${1:-}" in
    -printcert)
        cat <<'OUT'
Owner: CN=Upload Cert
SHA1: AA BB CC DD EE FF 11 22 33 44 55 66 77 88 99 00 AA BB CC DD
OUT
        exit 0
        ;;
    -list)
        cat <<'OUT'
Alias name: app-key
SHA1: AA BB CC DD EE FF 11 22 33 44 55 66 77 88 99 00 AA BB CC DD
OUT
        exit 0
        ;;
esac

if [[ " $* " == *" -printcert "* ]]; then
    cat <<'OUT'
Owner: CN=Upload Cert
SHA1: AA BB CC DD EE FF 11 22 33 44 55 66 77 88 99 00 AA BB CC DD
OUT
    exit 0
fi

echo "unexpected keytool invocation: $*" >&2
exit 1
BASH);

        $result = runCommand(
            'bash '.escapeshellarg($workspace.'/.scripts/publish-android.sh').' patch',
            [
                'PATH' => $workspace.'/bin:'.getenv('PATH'),
                'ANDROID_UPLOAD_CERTIFICATE_FILE' => $workspace.'/.credentials/upload_cert.der',
                'ANDROID_KEYSTORE_FILE' => $workspace.'/.credentials/app-release-key.jks',
                'ANDROID_KEYSTORE_PASSWORD' => 'password',
                'ANDROID_KEY_PASSWORD' => 'password',
            ],
            $workspace,
        );

        expect($result['exit_code'])->toBe(0, $result['stderr']);
        expect($result['stderr'])->toBe('');

        expect(readEnvValue($workspace.'/.env', 'NATIVEPHP_APP_VERSION'))->toBe('1.2.2');
        expect(readEnvValue($workspace.'/.env', 'NATIVEPHP_APP_VERSION_CODE'))->toBe('7');
        expect(trim((string) file_get_contents($workspace.'/packaged-version.txt')))->toBe('1.2.2');
        expect(trim((string) file_get_contents($workspace.'/packaged-version-code.txt')))->toBe('6');
    } finally {
        recursiveDelete($workspace);
    }
});
