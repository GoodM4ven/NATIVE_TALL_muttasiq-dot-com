#!/usr/bin/env bash
set -euo pipefail

# Examples:
#   ./.scripts/publish-android.sh <defaults to `minor` => 0.1.0 bump>
#   ./.scripts/publish-android.sh major
#   RELEASE_TYPE=major ANDROID_KEYSTORE_PASSWORD=... ./.scripts/publish-android.sh
#   ANDROID_KEY_ALIAS=app-key ANDROID_KEYSTORE_FILE=.credentials/app-release-key.jks ./.scripts/publish-android.sh patch

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_type="${1:-${RELEASE_TYPE:-minor}}"

read_env_var() {
    local key="$1"
    local env_file="${2:-${root_dir}/.env}"

    if [[ ! -f "${env_file}" ]]; then
        return 1
    fi

    local line
    line="$(grep -E "^${key}=" "${env_file}" | tail -n 1 || true)"

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

case "${release_type}" in
    patch | minor | major)
        ;;
    *)
        echo "Unsupported release type: ${release_type}" >&2
        exit 1
        ;;
esac

keystore_file="${ANDROID_KEYSTORE_FILE:-$(read_env_var "ANDROID_KEYSTORE_FILE" || true)}"
keystore_password="${ANDROID_KEYSTORE_PASSWORD:-$(read_env_var "ANDROID_KEYSTORE_PASSWORD" || true)}"
key_alias="${ANDROID_KEY_ALIAS:-$(read_env_var "ANDROID_KEY_ALIAS" || true)}"
key_password="${ANDROID_KEY_PASSWORD:-$(read_env_var "ANDROID_KEY_PASSWORD" || true)}"

if [[ -z "${keystore_file}" ]]; then
    echo "Missing Android keystore file path" >&2
    exit 1
fi

if [[ -z "${key_alias}" ]]; then
    echo "Missing Android key alias" >&2
    exit 1
fi

if [[ -z "${keystore_password}" ]]; then
    read -r -s -p "Android keystore password: " keystore_password
    printf '\n'
fi

if [[ -z "${key_password}" ]]; then
    key_password="${keystore_password}"
fi

if [[ ! -f "${keystore_file}" ]]; then
    echo "Missing required credential file: ${keystore_file}" >&2
    exit 1
fi

cd "${root_dir}"

./.scripts/support/prepare.sh
./.scripts/native/mobile/android/support/prepare.sh

php artisan native:release "${release_type}" --no-interaction

php artisan native:package android \
    --build-type=bundle \
    --keystore="${keystore_file}" \
    --keystore-password="${keystore_password}" \
    --key-alias="${key_alias}" \
    --key-password="${key_password}" \
    --no-interaction \
    --no-tty
