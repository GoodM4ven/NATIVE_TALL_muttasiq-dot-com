#!/usr/bin/env bash
set -euo pipefail

# Examples:
#   ./.scripts/publish-ios.sh <defaults to `minor` => 0.1.0 bump>
#   ./.scripts/publish-ios.sh major
#   RELEASE_TYPE=minor ./.scripts/publish-ios.sh
#   APP_STORE_API_KEY_PATH=.credentials/AuthKey_XXXXXX.p8 ./.scripts/publish-ios.sh patch

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_type="${1:-${RELEASE_TYPE:-minor}}"
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

case "${release_type}" in
    patch | minor | major)
        ;;
    *)
        echo "Unsupported release type: ${release_type}" >&2
        exit 1
        ;;
esac

app_store_api_key_path="${APP_STORE_API_KEY_PATH:-$(read_env_var "APP_STORE_API_KEY_PATH" || true)}"
app_store_api_key_id="${APP_STORE_API_KEY_ID:-$(read_env_var "APP_STORE_API_KEY_ID" || true)}"
app_store_api_issuer_id="${APP_STORE_API_ISSUER_ID:-$(read_env_var "APP_STORE_API_ISSUER_ID" || true)}"
distribution_certificate_path="${IOS_DISTRIBUTION_CERTIFICATE_PATH:-$(read_env_var "IOS_DISTRIBUTION_CERTIFICATE_PATH" || true)}"
distribution_certificate_password="${IOS_DISTRIBUTION_CERTIFICATE_PASSWORD:-$(read_env_var "IOS_DISTRIBUTION_CERTIFICATE_PASSWORD" || true)}"
provisioning_profile_path="${IOS_DISTRIBUTION_PROVISIONING_PROFILE_PATH:-$(read_env_var "IOS_DISTRIBUTION_PROVISIONING_PROFILE_PATH" || true)}"
team_id="${IOS_TEAM_ID:-$(read_env_var "IOS_TEAM_ID" || true)}"
if [[ -z "${team_id}" ]]; then
    team_id="${NATIVEPHP_DEVELOPMENT_TEAM:-$(read_env_var "NATIVEPHP_DEVELOPMENT_TEAM" || true)}"
fi

if [[ -z "${app_store_api_key_path}" ]]; then
    echo "Missing App Store Connect API key path" >&2
    exit 1
fi

if [[ ! -f "${app_store_api_key_path}" ]]; then
    echo "Missing required credential file: ${app_store_api_key_path}" >&2
    exit 1
fi

if [[ -z "${app_store_api_key_id}" ]]; then
    echo "Missing App Store Connect API key id" >&2
    exit 1
fi

if [[ -z "${app_store_api_issuer_id}" ]]; then
    echo "Missing App Store Connect API issuer id" >&2
    exit 1
fi

if [[ -z "${distribution_certificate_path}" ]]; then
    echo "Missing iOS distribution certificate path" >&2
    exit 1
fi

if [[ ! -f "${distribution_certificate_path}" ]]; then
    echo "Missing required credential file: ${distribution_certificate_path}" >&2
    exit 1
fi

if [[ -z "${distribution_certificate_password}" ]]; then
    read -r -s -p "iOS distribution certificate password: " distribution_certificate_password
    printf '\n'
fi

if [[ -z "${provisioning_profile_path}" ]]; then
    echo "Missing iOS provisioning profile path" >&2
    exit 1
fi

if [[ ! -f "${provisioning_profile_path}" ]]; then
    echo "Missing required credential file: ${provisioning_profile_path}" >&2
    exit 1
fi

if [[ -z "${team_id}" ]]; then
    echo "Missing iOS team id" >&2
    exit 1
fi

cd "${root_dir}"

./.scripts/support/prepare.sh
./.scripts/native/mobile/ios/support/prepare.sh

php artisan native:release "${release_type}" --no-interaction

export NATIVEPHP_APP_VERSION="$(read_env_var "NATIVEPHP_APP_VERSION" || true)"

rm -rf "${root_dir}/nativephp/ios/build/NativePHP.xcarchive"

php artisan native:package ios \
    --rebuild \
    --export-method=app-store \
    --api-key-path="${app_store_api_key_path}" \
    --api-key-id="${app_store_api_key_id}" \
    --api-issuer-id="${app_store_api_issuer_id}" \
    --certificate-path="${distribution_certificate_path}" \
    --certificate-password="${distribution_certificate_password}" \
    --provisioning-profile-path="${provisioning_profile_path}" \
    --team-id="${team_id}" \
    --upload-to-app-store \
    --no-interaction \
    --no-tty
