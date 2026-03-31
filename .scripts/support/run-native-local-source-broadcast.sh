#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

platform="${1:-}"
mode="${2:-run}"

if [[ "${platform}" != "android" && "${platform}" != "ios" ]]; then
    echo "Usage: ./.scripts/support/run-native-local-source-broadcast.sh <android|ios> [run|watch]" >&2
    exit 1
fi

if [[ "${mode}" != "run" && "${mode}" != "watch" ]]; then
    echo "Usage: ./.scripts/support/run-native-local-source-broadcast.sh <android|ios> [run|watch]" >&2
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
port_was_explicit=0

if [[ -n "${NATIVE_QURAN_LOCAL_API_PORT:-}" ]]; then
    port_was_explicit=1
fi

if [[ ! "${port}" =~ ^[0-9]+$ ]] || [[ "${port}" -lt 1 ]] || [[ "${port}" -gt 65535 ]]; then
    echo "[native-local-source-broadcast] invalid NATIVE_QURAN_LOCAL_API_PORT: ${port}" >&2
    exit 1
fi

is_tcp_port_in_use() {
    local candidate_port="$1"

    ss -lnt | awk -v target_port=":${candidate_port}" '$4 ~ target_port"$" { found = 1 } END { exit found ? 0 : 1 }'
}

pick_available_tcp_port() {
    local candidate_port="$1"

    for _ in {1..256}; do
        if ! is_tcp_port_in_use "${candidate_port}"; then
            printf '%s' "${candidate_port}"
            return 0
        fi

        candidate_port=$((candidate_port + 1))
    done

    return 1
}

resolve_local_lan_ipv4() {
    local lan_ipv4=""

    if command -v ip >/dev/null 2>&1; then
        lan_ipv4="$(ip route get 1.1.1.1 2>/dev/null | awk '/src/ {for (i = 1; i <= NF; i++) if ($i == "src") {print $(i + 1); exit}}')"
    fi

    if [[ -z "${lan_ipv4}" ]] && command -v hostname >/dev/null 2>&1; then
        lan_ipv4="$(hostname -I 2>/dev/null | awk '{print $1}')"
    fi

    if [[ "${lan_ipv4}" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
        printf '%s' "${lan_ipv4}"
    fi
}

extract_url_host() {
    local url="$1"
    local without_scheme="${url#*://}"
    local host_and_port="${without_scheme%%/*}"

    printf '%s' "${host_and_port%%:*}"
}

is_loopback_style_host() {
    local host="$1"

    case "${host}" in
    "localhost" | "127.0.0.1" | "10.0.2.2")
        return 0
        ;;
    *)
        return 1
        ;;
    esac
}

public_base_url="${public_base_url%/}"

if is_tcp_port_in_use "${port}"; then
    if [[ -n "${public_base_url}" || "${port_was_explicit}" -eq 1 ]]; then
        echo "[native-local-source-broadcast] port ${port} is already in use. Free it or choose another NATIVE_QURAN_LOCAL_API_PORT." >&2
        ss -lntp | awk -v target_port=":${port}" '$4 ~ target_port"$" { print }' >&2 || true
        exit 1
    fi

    next_port="$(pick_available_tcp_port "$((port + 1))" || true)"

    if [[ -z "${next_port}" ]]; then
        echo "[native-local-source-broadcast] could not find a free TCP port near ${port}" >&2
        exit 1
    fi

    echo "[native-local-source-broadcast] port ${port} is occupied; using free port ${next_port}" >&2
    port="${next_port}"
fi

if [[ "${platform}" == "android" && "${NATIVE_QURAN_LOCAL_DISABLE_ADB_REVERSE:-0}" != "1" ]]; then
    if command -v adb >/dev/null 2>&1; then
        adb_reverse_enabled=1
    fi
fi

if [[ "${adb_reverse_enabled}" -eq 1 ]] && adb get-state >/dev/null 2>&1; then
    if adb reverse "tcp:${port}" "tcp:${port}" >/dev/null 2>&1; then
        adb_reverse_active=1

        if [[ -z "${public_base_url}" ]]; then
            public_base_url="http://127.0.0.1:${port}"
        fi
    fi
fi

if [[ -z "${public_base_url}" ]]; then
    if [[ "${platform}" == "android" ]]; then
        local_lan_ipv4="$(resolve_local_lan_ipv4)"

        if [[ -n "${local_lan_ipv4}" && "${android_host}" == "10.0.2.2" && "${adb_reverse_active}" -eq 0 ]]; then
            public_base_url="http://${local_lan_ipv4}:${port}"
            echo "[native-local-source-broadcast] adb reverse is inactive; using detected LAN host ${local_lan_ipv4}" >&2
        else
            public_base_url="http://${android_host}:${port}"
        fi
    else
        public_base_url="http://${ios_host}:${port}"
    fi
fi

public_base_url="${public_base_url%/}"

if [[ ! "${public_base_url}" =~ ^https?:// ]]; then
    echo "[native-local-source-broadcast] invalid NATIVE_QURAN_LOCAL_PUBLIC_BASE_URL: ${public_base_url}" >&2
    exit 1
fi

public_host="$(extract_url_host "${public_base_url}")"

if [[ -z "${public_host}" ]]; then
    echo "[native-local-source-broadcast] unable to resolve host from NATIVE_QURAN_LOCAL_PUBLIC_BASE_URL: ${public_base_url}" >&2
    exit 1
fi

if ! is_loopback_style_host "${public_host}" && [[ "${bind_host}" == "127.0.0.1" || "${bind_host}" == "localhost" ]]; then
    bind_host="0.0.0.0"
    echo "[native-local-source-broadcast] using LAN host ${public_host}; forcing bind host ${bind_host}" >&2
fi

native_script="${project_root}/.scripts/run-${platform}.sh"

if [[ "${mode}" == "watch" ]]; then
    native_script="${project_root}/.scripts/support/watch-${platform}-native.sh"
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
: >"${api_log_file}"

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

meta_endpoint="${public_base_url}/api/quran-snapshot/meta"
download_endpoint="${public_base_url}/api/quran-snapshot/download"
settings_endpoint="${public_base_url}/api/settings"

echo "[native-local-source-broadcast] local API server: http://${bind_host}:${port}"
echo "[native-local-source-broadcast] settings endpoint: ${settings_endpoint}"
echo "[native-local-source-broadcast] meta endpoint: ${meta_endpoint}"
echo "[native-local-source-broadcast] download endpoint: ${download_endpoint}"
echo "[native-local-source-broadcast] running ${native_script}"

(
    cd "${project_root}"
    NATIVE_SETTINGS_ENDPOINT="${settings_endpoint}" \
        NATIVE_QURAN_SNAPSHOT_META_ENDPOINT="${meta_endpoint}" \
        NATIVE_QURAN_SNAPSHOT_DOWNLOAD_ENDPOINT="${download_endpoint}" \
        "${native_script}"
)
