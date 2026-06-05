#!/usr/bin/env bash
set -euo pipefail

# Examples:
#   ./.scripts/publish-android.sh <defaults to `minor` => 0.1.0 bump>
#   ./.scripts/publish-android.sh major
#   RELEASE_TYPE=minor ./.scripts/publish-android.sh
#   ANDROID_UPLOAD_CERTIFICATE_FILE=.credentials/upload_cert.der ./.scripts/publish-android.sh patch

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_type="${1:-${RELEASE_TYPE:-minor}}"
upload_certificate_file="${ANDROID_UPLOAD_CERTIFICATE_FILE:-${root_dir}/.credentials/upload_cert.der}"
env_file="${root_dir}/.env"

read_env_var() {
    local key="$1"
    local file="${2:-${env_file}}"

    if [[ ! -f "${file}" ]]; then
        return 1
    fi

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

normalize_fingerprint() {
    printf '%s' "$1" | tr -d '[:space:]:'
}

read_certificate_sha1() {
    local certificate_file="$1"

    if [[ ! -f "${certificate_file}" ]]; then
        return 1
    fi

    keytool -printcert -file "${certificate_file}" 2>/dev/null \
        | awk -F'SHA1: ' '/SHA1: / { print $2; exit }' \
        | tr -d '[:space:]'
}

find_keystore_alias_by_fingerprint() {
    local keystore_file="$1"
    local keystore_password="$2"
    local expected_fingerprint
    expected_fingerprint="$(normalize_fingerprint "$3")"

    if [[ -z "${expected_fingerprint}" ]]; then
        return 1
    fi

    keytool -list -v \
        -keystore "${keystore_file}" \
        -storepass "${keystore_password}" 2>/dev/null \
        | awk -v expected="${expected_fingerprint}" '
            BEGIN {
                alias = ""
            }
            /^Alias name: / {
                alias = substr($0, 13)
                gsub(/^[[:space:]]+|[[:space:]]+$/, "", alias)
            }
            /SHA1: / {
                fingerprint = $2
                gsub(/[^[:alnum:]]/, "", fingerprint)
                if (toupper(fingerprint) == toupper(expected)) {
                    print alias
                    exit 0
                }
            }
        '
}

read_keystore_alias_sha1() {
    local keystore_file="$1"
    local keystore_password="$2"
    local alias="$3"

    keytool -list -v \
        -keystore "${keystore_file}" \
        -storepass "${keystore_password}" 2>/dev/null \
        | awk -v alias="${alias}" '
            BEGIN {
                in_alias = 0
            }
            /^Alias name: / {
                current_alias = substr($0, 13)
                gsub(/^[[:space:]]+|[[:space:]]+$/, "", current_alias)
                in_alias = (current_alias == alias)
            }
            in_alias && /SHA1: / {
                print $2
                exit 0
            }
        '
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
key_password="${ANDROID_KEY_PASSWORD:-$(read_env_var "ANDROID_KEY_PASSWORD" || true)}"

if [[ -z "${keystore_file}" ]]; then
    echo "Missing Android keystore file path" >&2
    exit 1
fi

if [[ ! -f "${keystore_file}" ]]; then
    echo "Missing required credential file: ${keystore_file}" >&2
    exit 1
fi

if [[ -z "${keystore_password}" ]]; then
    read -r -s -p "Android keystore password: " keystore_password
    printf '\n'
fi

if [[ ! -f "${upload_certificate_file}" ]]; then
    echo "Missing upload certificate file: ${upload_certificate_file}" >&2
    exit 1
fi

key_alias="${ANDROID_KEY_ALIAS:-$(read_env_var "ANDROID_KEY_ALIAS" || true)}"
if [[ -z "${key_alias}" ]]; then
    upload_certificate_sha1="$(read_certificate_sha1 "${upload_certificate_file}" || true)"
    if [[ -z "${upload_certificate_sha1}" ]]; then
        echo "Unable to read SHA1 fingerprint from upload certificate: ${upload_certificate_file}" >&2
        exit 1
    fi

    key_alias="$(find_keystore_alias_by_fingerprint "${keystore_file}" "${keystore_password}" "${upload_certificate_sha1}" || true)"
    if [[ -z "${key_alias}" ]]; then
        echo "Unable to find a keystore alias matching the upload certificate fingerprint in ${upload_certificate_file}" >&2
        exit 1
    fi
fi

if [[ -z "${key_password}" ]]; then
    key_password="${keystore_password}"
fi

export ANDROID_KEY_ALIAS="${key_alias}"

cd "${root_dir}"

./.scripts/support/prepare.sh
./.scripts/native/mobile/android/support/prepare.sh

php artisan native:release "${release_type}" --no-interaction

export NATIVEPHP_APP_VERSION="$(read_env_var "NATIVEPHP_APP_VERSION" || true)"

php artisan native:package android \
    --build-type=bundle \
    --keystore="${keystore_file}" \
    --keystore-password="${keystore_password}" \
    --key-alias="${key_alias}" \
    --key-password="${key_password}" \
    --no-interaction \
    --no-tty
