#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

platform="${1:-}"
mode="${2:-run}"

if [[ "${platform}" != "android" && "${platform}" != "ios" ]]; then
    echo "Usage: ./.scripts/run-native-local-source-broadcast.sh <android|ios> [run|watch]" >&2
    exit 1
fi

if [[ "${mode}" != "run" && "${mode}" != "watch" ]]; then
    echo "Usage: ./.scripts/run-native-local-source-broadcast.sh <android|ios> [run|watch]" >&2
    exit 1
fi

port="${NATIVE_QURAN_LOCAL_API_PORT:-8787}"
bind_host="${NATIVE_QURAN_LOCAL_BIND_HOST:-127.0.0.1}"
public_base_url="${NATIVE_QURAN_LOCAL_PUBLIC_BASE_URL:-}"
android_host="${NATIVE_QURAN_LOCAL_ANDROID_HOST:-10.0.2.2}"
ios_host="${NATIVE_QURAN_LOCAL_IOS_HOST:-127.0.0.1}"
api_log_file="${project_root}/storage/logs/native-local-source-broadcast-api.log"
adb_reverse_enabled=0
adb_reverse_active=0
server_pid=""
server_ready=0

if [[ -z "${public_base_url}" ]]; then
    if [[ "${platform}" == "android" ]]; then
        public_base_url="http://${android_host}:${port}"
    else
        public_base_url="http://${ios_host}:${port}"
    fi
fi

public_base_url="${public_base_url%/}"

if [[ ! "${public_base_url}" =~ ^https?:// ]]; then
    echo "[native-local-source-broadcast] invalid NATIVE_QURAN_LOCAL_PUBLIC_BASE_URL: ${public_base_url}" >&2
    exit 1
fi

if [[ "${platform}" == "android" && "${NATIVE_QURAN_LOCAL_DISABLE_ADB_REVERSE:-0}" != "1" ]]; then
    if command -v adb >/dev/null 2>&1; then
        adb_reverse_enabled=1
    fi
fi

native_script="${project_root}/.scripts/run-${platform}.sh"

if [[ "${mode}" == "watch" ]]; then
    native_script="${project_root}/.scripts/watch-${platform}.sh"
fi

if [[ ! -f "${native_script}" ]]; then
    echo "[native-local-source-broadcast] missing script: ${native_script}" >&2
    exit 1
fi

cleanup() {
    if [[ "${adb_reverse_active}" -eq 1 ]]; then
        adb reverse --remove "tcp:${port}" >/dev/null 2>&1 || true
    fi

    if [[ -n "${server_pid}" ]] && kill -0 "${server_pid}" >/dev/null 2>&1; then
        kill "${server_pid}" >/dev/null 2>&1 || true
        wait "${server_pid}" >/dev/null 2>&1 || true
    fi
}

trap cleanup EXIT INT TERM

mkdir -p "$(dirname "${api_log_file}")"

(
    cd "${project_root}"
    php artisan serve --host="${bind_host}" --port="${port}" >"${api_log_file}" 2>&1
) &

server_pid="$!"

for _ in {1..60}; do
    if ! kill -0 "${server_pid}" >/dev/null 2>&1; then
        echo "[native-local-source-broadcast] local API server exited early. See ${api_log_file}" >&2
        exit 1
    fi

    if curl --silent --fail --max-time 2 "http://127.0.0.1:${port}/api/settings" >/dev/null 2>&1; then
        server_ready=1
        break
    fi

    sleep 0.25
done

if [[ "${server_ready}" -ne 1 ]]; then
    echo "[native-local-source-broadcast] local API server did not become ready in time. See ${api_log_file}" >&2
    exit 1
fi

if [[ "${adb_reverse_enabled}" -eq 1 ]] && adb get-state >/dev/null 2>&1; then
    if adb reverse "tcp:${port}" "tcp:${port}" >/dev/null 2>&1; then
        adb_reverse_active=1

        if [[ -z "${NATIVE_QURAN_LOCAL_PUBLIC_BASE_URL:-}" ]]; then
            public_base_url="http://127.0.0.1:${port}"
        fi
    fi
fi

meta_endpoint="${public_base_url}/api/quran-snapshot/meta"
download_endpoint="${public_base_url}/api/quran-snapshot/download"

echo "[native-local-source-broadcast] local API server: http://${bind_host}:${port}"
echo "[native-local-source-broadcast] meta endpoint: ${meta_endpoint}"
echo "[native-local-source-broadcast] download endpoint: ${download_endpoint}"
echo "[native-local-source-broadcast] running ${native_script}"

(
    cd "${project_root}"
    NATIVE_QURAN_SNAPSHOT_META_ENDPOINT="${meta_endpoint}" \
        NATIVE_QURAN_SNAPSHOT_DOWNLOAD_ENDPOINT="${download_endpoint}" \
        "${native_script}"
)
