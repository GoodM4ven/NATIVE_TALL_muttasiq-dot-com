#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../../" && pwd)"
source "${root_dir}/.scripts/native/mobile/support/prepare-platform.sh"
ios_dir="${root_dir}/nativephp/ios"
binaries_url="${NATIVEPHP_IOS_BINARIES_URL:-https://bin.nativephp.com/nativephp-ios-2.0.0-php8.4.zip}"

copy_ios_binaries_from() {
    local source_dir="$1"

    if [[ ! -d "${source_dir}/Include" || ! -d "${source_dir}/Libraries" ]]; then
        return 1
    fi

    echo "[native-prepare:ios] using iOS PHP binaries from ${source_dir}"
    mkdir -p "${ios_dir}"
    cp -R "${source_dir}/Include" "${ios_dir}/"
    cp -R "${source_dir}/Libraries" "${ios_dir}/"

    if [[ -d "${source_dir}/Licenses" ]]; then
        cp -R "${source_dir}/Licenses" "${ios_dir}/"
    fi

    return 0
}

download_ios_binaries() {
    local temp_dir=""
    local zip_path=""
    local extract_dir=""
    local detected_source=""

    if ! command -v curl >/dev/null 2>&1; then
        echo "[native-prepare:ios] curl was not found; cannot download iOS PHP binaries." >&2
        return 1
    fi

    temp_dir="$(mktemp -d "${TMPDIR:-/tmp}/nativephp-ios-binaries.XXXXXX")"
    zip_path="${temp_dir}/ios-binaries.zip"
    extract_dir="${temp_dir}/extracted"

    cleanup() {
        rm -rf "${temp_dir}"
    }
    trap cleanup RETURN

    echo "[native-prepare:ios] downloading iOS PHP binaries from ${binaries_url}"
    if ! curl -fL --retry 3 --connect-timeout 30 --max-time 600 "${binaries_url}" -o "${zip_path}"; then
        echo "[native-prepare:ios] failed to download iOS PHP binaries." >&2
        return 1
    fi

    mkdir -p "${extract_dir}"
    if command -v ditto >/dev/null 2>&1; then
        if ! ditto -x -k "${zip_path}" "${extract_dir}"; then
            echo "[native-prepare:ios] failed to extract iOS PHP binaries with ditto." >&2
            return 1
        fi
    elif command -v unzip >/dev/null 2>&1; then
        if ! unzip -q "${zip_path}" -d "${extract_dir}"; then
            echo "[native-prepare:ios] failed to extract iOS PHP binaries with unzip." >&2
            return 1
        fi
    else
        echo "[native-prepare:ios] neither ditto nor unzip are available for extraction." >&2
        return 1
    fi

    if copy_ios_binaries_from "${extract_dir}"; then
        return 0
    fi

    while IFS= read -r -d '' dir; do
        if [[ -d "${dir}/Include" && -d "${dir}/Libraries" ]]; then
            detected_source="${dir}"
            break
        fi
    done < <(find "${extract_dir}" -type d -print0)

    if [[ -z "${detected_source}" ]]; then
        echo "[native-prepare:ios] extracted archive does not contain Include/Libraries directories." >&2
        return 1
    fi

    copy_ios_binaries_from "${detected_source}"
}

if [[ "$(uname -s)" != "Darwin" ]]; then
    echo "[native-prepare:ios] iOS prepare requires macOS (Darwin)." >&2
    exit 1
fi

install_args=()

if [[ "$(native_read_icu_preference)" == "1" ]]; then
    install_args+=(--with-icu)
    echo "[native-prepare:ios] ICU-enabled PHP binaries are required by NativePHP lock/config"
fi

install_args_display="${install_args[*]-}"
echo "[native-prepare:ios] forcing native:install ios ${install_args_display} --force --no-interaction"
(
    cd "${root_dir}"
    if (( ${#install_args[@]} > 0 )); then
        php artisan native:install ios "${install_args[@]}" --force --no-interaction
    else
        php artisan native:install ios --force --no-interaction
    fi
)

if [[ ! -d "${ios_dir}/Include" || ! -d "${ios_dir}/Libraries" ]]; then
    candidates=(
        "${NATIVEPHP_IOS_BINARIES_DIR:-}"
        "${root_dir}/nativephp-ios-2"
        "${root_dir}/nativephp-ios"
        "${HOME}/Downloads/nativephp-ios-2"
        "${HOME}/Downloads/nativephp-ios"
    )

    for candidate in "${candidates[@]}"; do
        if [[ -z "${candidate}" ]]; then
            continue
        fi

        if copy_ios_binaries_from "${candidate}"; then
            break
        fi
    done
fi

if [[ ! -d "${ios_dir}/Include" || ! -d "${ios_dir}/Libraries" ]]; then
    download_ios_binaries || true
fi

if [[ ! -d "${ios_dir}/Include" || ! -d "${ios_dir}/Libraries" ]]; then
    echo "[native-prepare:ios] missing nativephp/ios Include/Libraries after install." >&2
    echo "[native-prepare:ios] set NATIVEPHP_IOS_BINARIES_DIR or NATIVEPHP_IOS_BINARIES_URL and try again." >&2
    exit 1
fi

(
    cd "${root_dir}"
    npm run build -- --mode=ios
)
