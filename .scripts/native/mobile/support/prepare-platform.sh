#!/usr/bin/env bash
set -euo pipefail

native_root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../" && pwd)"

native_read_locked_package_version() {
    php -r '
$lockPath = $argv[1] ?? null;
$packageName = $argv[2] ?? null;
if (! $lockPath || ! $packageName || ! file_exists($lockPath)) {
    exit(1);
}
$lock = json_decode(file_get_contents($lockPath), true);
if (! is_array($lock)) {
    exit(1);
}
foreach (($lock["packages"] ?? []) as $package) {
    if (($package["name"] ?? null) === $packageName) {
        echo (string) ($package["version"] ?? "");
        exit(0);
    }
}
exit(1);
' "${native_root_dir}/composer.lock" "$1"
}

native_read_mobile_version() {
    native_read_locked_package_version "nativephp/mobile"
}

native_read_icu_preference() {
    php -r '
$lockPath = $argv[1] ?? null;
$legacyJsonPath = $argv[2] ?? null;

if ($lockPath && file_exists($lockPath)) {
    $lock = json_decode(file_get_contents($lockPath), true);

    if (is_array($lock) && array_key_exists("php", $lock) && is_array($lock["php"]) && array_key_exists("icu", $lock["php"])) {
        echo ! empty($lock["php"]["icu"]) ? "1" : "0";
        exit(0);
    }
}

if ($legacyJsonPath && file_exists($legacyJsonPath)) {
    $json = json_decode(file_get_contents($legacyJsonPath), true);

    if (is_array($json)) {
        echo ! empty($json["php"]["icu"]) ? "1" : "0";
        exit(0);
    }
}

echo "0";
' "${native_root_dir}/nativephp.lock" "${native_root_dir}/nativephp.json"
}

native_hash_paths_signature() {
    php -r '
array_shift($argv);
$context = hash_init("sha256");

foreach ($argv as $path) {
    if ($path === "") {
        continue;
    }

    if (! file_exists($path)) {
        hash_update($context, $path."|missing\n");
        continue;
    }

    if (is_file($path)) {
        $hash = hash_file("sha256", $path) ?: "";
        hash_update($context, $path."|file|".$hash."|".filesize($path)."|".filemtime($path)."\n");
        continue;
    }

    hash_update($context, $path."|dir\n");
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
    );
    $files = [];

    foreach ($iterator as $item) {
        if (! $item->isFile()) {
            continue;
        }

        $files[] = $item->getPathname();
    }

    sort($files);

    foreach ($files as $filePath) {
        $hash = hash_file("sha256", $filePath) ?: "";
        hash_update($context, $filePath."|".$hash."|".filesize($filePath)."|".filemtime($filePath)."\n");
    }
}

echo hash_final($context);
' "$@"
}

native_prune_stale_build_assets() {
    php -r '
$manifestPath = $argv[1] ?? null;
$assetsPath = $argv[2] ?? null;

if (! $manifestPath || ! is_file($manifestPath) || ! $assetsPath || ! is_dir($assetsPath)) {
    exit(0);
}

try {
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
} catch (Throwable) {
    exit(0);
}

if (! is_array($manifest)) {
    exit(0);
}

$retainedAssets = [];

foreach ($manifest as $entry) {
    if (! is_array($entry)) {
        continue;
    }

    $candidates = [];

    if (is_string($entry["file"] ?? null)) {
        $candidates[] = $entry["file"];
    }

    foreach (($entry["css"] ?? []) as $assetPath) {
        if (is_string($assetPath)) {
            $candidates[] = $assetPath;
        }
    }

    foreach (($entry["assets"] ?? []) as $assetPath) {
        if (is_string($assetPath)) {
            $candidates[] = $assetPath;
        }
    }

    foreach ($candidates as $assetPath) {
        $normalizedAssetPath = ltrim($assetPath, "/");

        if ($normalizedAssetPath !== "" && str_starts_with($normalizedAssetPath, "assets/")) {
            $normalizedAssetPath = substr($normalizedAssetPath, strlen("assets/"));
            $retainedAssets[$normalizedAssetPath] = true;
        }
    }
}

$removedFilesCount = 0;
$removedBytes = 0;
$assetsRoot = rtrim($assetsPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($assetsPath, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST,
);

foreach ($iterator as $item) {
    if ($item->isDir()) {
        @rmdir($item->getPathname());

        continue;
    }

    $pathName = $item->getPathname();
    $relativePath = substr($pathName, strlen($assetsRoot));

    if (isset($retainedAssets[$relativePath])) {
        continue;
    }

    $removedBytes += $item->getSize();

    if (@unlink($pathName)) {
        $removedFilesCount++;
    }
}

if ($removedFilesCount > 0) {
    fwrite(
        STDOUT,
        sprintf(
            "[native-prepare:build-assets] pruned %d stale Vite asset(s) (%.2f MB)\n",
            $removedFilesCount,
            $removedBytes / 1024 / 1024,
        ),
    );
}
' "${native_root_dir}/public/build/manifest.json" "${native_root_dir}/public/build/assets"
}

native_prepare_bundle_inputs() {
    native_prune_stale_build_assets

    if [[ "${NATIVE_QURAN_SNAPSHOT_CLEAR_BEFORE_BUILD:-0}" == "1" ]]; then
        rm -f \
            "${native_root_dir}/database/native-quran-reader.sqlite" \
            "${native_root_dir}/database/native-quran-reader.json" \
            "${native_root_dir}/database/native-quran-reader.sqlite.gz"

        echo "[native-prepare:bundle] cleared local Quran snapshot files (NATIVE_QURAN_SNAPSHOT_CLEAR_BEFORE_BUILD=1)"
    fi
}

native_read_bundle_signature() {
    native_hash_paths_signature \
        "${native_root_dir}/nativephp.json" \
        "${native_root_dir}/public/docs/updates" \
        "${native_root_dir}/vendor/goodm4ven/nativephp-muttasiq-patches/src" \
        "${native_root_dir}/vendor/goodm4ven/nativephp-muttasiq-patches/composer.json"
}

native_prepare_platform_install() {
    local platform="$1"
    local required_file="$2"
    shift 2
    local -a install_args=("$@")

    local stamp_file="${native_root_dir}/nativephp/.nativephp-mobile-version-${platform}"
    local current_version=""
    current_version="$(native_read_mobile_version || true)"
    local plugin_version=""
    plugin_version="$(native_read_locked_package_version "goodm4ven/nativephp-muttasiq-patches" || true)"
    local desired_icu="0"
    desired_icu="$(native_read_icu_preference)"
    native_prepare_bundle_inputs
    local bundle_signature=""
    bundle_signature="$(native_read_bundle_signature)"
    local desired_signature=""
    desired_signature="${current_version}|patches=${plugin_version}|icu=${desired_icu}|bundle=${bundle_signature}"

    if [[ -z "${current_version}" ]]; then
        echo "[native-prepare:${platform}] failed to read nativephp/mobile version from composer.lock" >&2
        exit 1
    fi

    if [[ "${desired_icu}" == "1" ]]; then
        install_args+=(--with-icu)
    fi

    local should_install=0
    local reason=""
    local platform_dir="${native_root_dir}/nativephp/${platform}"
    local required_path="${native_root_dir}/${required_file}"

    if [[ ! -d "${native_root_dir}/nativephp" ]]; then
        should_install=1
        reason="nativephp directory missing"
    elif [[ ! -d "${platform_dir}" ]]; then
        should_install=1
        reason="nativephp/${platform} directory missing"
    elif [[ ! -f "${required_path}" ]]; then
        should_install=1
        reason="${required_file} missing"
    elif [[ ! -f "${stamp_file}" ]]; then
        should_install=1
        reason="version stamp missing"
    else
        local installed_signature=""
        installed_signature="$(<"${stamp_file}")"
        if [[ "${installed_signature}" != "${desired_signature}" ]]; then
            should_install=1
            reason="install signature changed (${installed_signature} -> ${desired_signature})"
        fi
    fi

    if [[ "${should_install}" -eq 1 ]]; then
        echo "[native-prepare:${platform}] refreshing native ${platform} project (${reason})"
        if [[ "${desired_icu}" == "1" ]]; then
            echo "[native-prepare:${platform}] ICU-enabled PHP binaries are required by NativePHP lock/config"
        fi
        (
            cd "${native_root_dir}"
            php artisan native:install "${platform}" "${install_args[@]}" --force --no-interaction
        )
        printf '%s\n' "${desired_signature}" > "${stamp_file}"
    fi
}
