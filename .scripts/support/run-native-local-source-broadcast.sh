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
composer_dev_log_file="${project_root}/storage/logs/native-local-source-broadcast-composer-dev.log"
tailscale_log_file="${project_root}/storage/logs/native-local-source-broadcast-tailscale.log"
adb_reverse_enabled=0
adb_reverse_active=0
composer_dev_pid=""
server_ready=0
port_was_explicit=0
native_android_keep_loopback_endpoints=0
tailscale_funnel_pid=""
tailscale_funnel_active=0
tailscale_funnel_target=""
# Must stay :443 — the Telegram login widget is served from this funnel domain and
# BotFather's domain setting is host-only (rejects a port), so a non-standard port
# stops the widget from rendering.
tailscale_funnel_https_port=443
tailscale_funnel_reverb_target=""
tailscale_funnel_reverb_active=0
tailscale_funnel_reverb_https_port="${NATIVE_QURAN_LOCAL_REVERB_HTTPS_PORT:-8443}"
reverb_server_port="${REVERB_SERVER_PORT:-8080}"
watch_prefers_tailscale=0
tailscale_requires_sudo=0

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

resolve_tailscale_funnel_url() {
    local funnel_status=""

    if ! command -v tailscale >/dev/null 2>&1; then
        return 1
    fi

    funnel_status="$(tailscale funnel status 2>/dev/null || true)"

    if [[ -z "${funnel_status}" ]]; then
        return 1
    fi

    printf '%s\n' "${funnel_status}" \
        | grep -Eo 'https://[^[:space:]]+\.ts\.net(:[0-9]+)?' \
        | head -n 1 \
        | sed -E 's#:443$##'
}

resolve_tailscale_command_prefix() {
    if [[ "$(uname -s)" == "Linux" ]] && [[ "$(id -u)" -ne 0 ]] && command -v sudo >/dev/null 2>&1; then
        tailscale_requires_sudo=1
        printf 'sudo'
        return 0
    fi

    tailscale_requires_sudo=0
    printf ''
}

log_tailscale_diagnostics() {
    if ! command -v tailscale >/dev/null 2>&1; then
        echo "[native-local-source-broadcast] tailscale: command not installed" >&2
        return 1
    fi

    {
        echo "[native-local-source-broadcast] tailscale version:"
        tailscale version
        echo "[native-local-source-broadcast] tailscale status:"
        tailscale status --json || true
        echo "[native-local-source-broadcast] tailscale funnel status:"
        tailscale funnel status || true
    } >"${tailscale_log_file}" 2>&1

    local tailscale_backend_state=""
    local tailscale_health_state=""

    tailscale_backend_state="$(
        grep -Eo '"BackendState"[[:space:]]*:[[:space:]]*"[^"]+"' "${tailscale_log_file}" 2>/dev/null \
            | head -n 1 \
            | sed -E 's/.*:[[:space:]]*"([^"]+)"/\1/'
    )"
    tailscale_health_state="$(
        grep -Eo '"Health"[[:space:]]*:[[:space:]]*\[[^]]*\]' "${tailscale_log_file}" 2>/dev/null \
            | head -n 1 \
            | sed -E 's/.*\[([^]]*)\].*/\1/'
    )"

    echo "[native-local-source-broadcast] tailscale backend: ${tailscale_backend_state:-unknown}" >&2
    echo "[native-local-source-broadcast] tailscale health: ${tailscale_health_state:-unknown}" >&2
    echo "[native-local-source-broadcast] tailscale diagnostics: ${tailscale_log_file}" >&2

    if [[ "${tailscale_backend_state}" == "NeedsLogin" ]]; then
        return 1
    fi

    if [[ "${tailscale_health_state}" == '"Tailscale is stopped."' ]]; then
        return 1
    fi

    return 0
}

prompt_for_tailscale_setup_or_exit() {
    local setup_link="https://login.tailscale.com/f/funnel?node=nBuJSLYtYt11CNTRL"

    echo "[native-local-source-broadcast] tailscale is not ready yet." >&2
    echo "[native-local-source-broadcast] first-time setup reminders:" >&2
    echo "[native-local-source-broadcast] - run: sudo tailscale up" >&2
    echo "[native-local-source-broadcast] - if this host still needs Funnel approval, open: ${setup_link}" >&2
    echo "[native-local-source-broadcast] - if you want non-root Funnel config later, run: sudo tailscale set --operator=\"${USER}\"" >&2
    echo "[native-local-source-broadcast] continuing without Tailscale will fall back to the local LAN URL." >&2

    if [[ ! -t 0 ]]; then
        echo "[native-local-source-broadcast] non-interactive shell; stopping here." >&2
        exit 1
    fi

    read -r -p "[native-local-source-broadcast] continue with LAN fallback anyway? [y/N] " response

    case "${response}" in
    y | Y | yes | YES)
        return 0
        ;;
    *)
        echo "[native-local-source-broadcast] aborted." >&2
        exit 1
        ;;
    esac
}

start_tailscale_funnel() {
    local funnel_target="$1"

    if ! command -v tailscale >/dev/null 2>&1; then
        return 1
    fi

    : >"${tailscale_log_file}"

    local tailscale_command_prefix
    tailscale_command_prefix="$(resolve_tailscale_command_prefix)"

    if [[ "${tailscale_requires_sudo}" -eq 1 ]]; then
        echo "[native-local-source-broadcast] tailscale funnel config requires sudo on this machine; you will be prompted once." >&2
    fi

    if [[ -n "${tailscale_command_prefix}" ]]; then
        (
            cd "${project_root}"
            ${tailscale_command_prefix} tailscale funnel --yes --bg --https="${tailscale_funnel_https_port}" "${funnel_target}"
        ) >"${tailscale_log_file}" 2>&1
    else
        (
            cd "${project_root}"
            tailscale funnel --yes --bg --https="${tailscale_funnel_https_port}" "${funnel_target}"
        ) >"${tailscale_log_file}" 2>&1
    fi

    tailscale_funnel_pid=""
    tailscale_funnel_active=1
    tailscale_funnel_target="${funnel_target}"

    return 0
}

start_tailscale_reverb_funnel() {
    local funnel_target="$1"

    if ! command -v tailscale >/dev/null 2>&1; then
        return 1
    fi

    local tailscale_command_prefix
    tailscale_command_prefix="$(resolve_tailscale_command_prefix)"

    if [[ -n "${tailscale_command_prefix}" ]]; then
        (
            cd "${project_root}"
            ${tailscale_command_prefix} tailscale funnel --yes --bg --https="${tailscale_funnel_reverb_https_port}" "${funnel_target}"
        ) >>"${tailscale_log_file}" 2>&1
    else
        (
            cd "${project_root}"
            tailscale funnel --yes --bg --https="${tailscale_funnel_reverb_https_port}" "${funnel_target}"
        ) >>"${tailscale_log_file}" 2>&1
    fi

    tailscale_funnel_reverb_active=1
    tailscale_funnel_reverb_target="${funnel_target}"

    return 0
}

stop_tailscale_funnel() {
    if [[ "${tailscale_funnel_reverb_active}" -eq 1 && -n "${tailscale_funnel_reverb_target}" ]]; then
        if [[ "${tailscale_requires_sudo}" -eq 1 ]]; then
            sudo tailscale funnel --yes --https="${tailscale_funnel_reverb_https_port}" "${tailscale_funnel_reverb_target}" off >/dev/null 2>&1 || true
        else
            tailscale funnel --yes --https="${tailscale_funnel_reverb_https_port}" "${tailscale_funnel_reverb_target}" off >/dev/null 2>&1 || true
        fi
    fi

    if [[ "${tailscale_funnel_active}" -ne 1 ]]; then
        return 0
    fi

    if [[ -n "${tailscale_funnel_pid}" ]] && kill -0 "${tailscale_funnel_pid}" >/dev/null 2>&1; then
        kill "${tailscale_funnel_pid}" >/dev/null 2>&1 || true
        wait "${tailscale_funnel_pid}" >/dev/null 2>&1 || true
    fi

    if [[ -n "${tailscale_funnel_target}" ]]; then
        if [[ "${tailscale_requires_sudo}" -eq 1 ]]; then
            sudo tailscale funnel --yes --https="${tailscale_funnel_https_port}" "${tailscale_funnel_target}" off >/dev/null 2>&1 || true
        else
            tailscale funnel --yes --https="${tailscale_funnel_https_port}" "${tailscale_funnel_target}" off >/dev/null 2>&1 || true
        fi
    fi
}

if [[ "${mode}" == "watch" ]]; then
    if log_tailscale_diagnostics; then
        watch_prefers_tailscale=1
    else
        prompt_for_tailscale_setup_or_exit
    fi
fi

extract_url_host() {
    local url="$1"
    local without_scheme="${url#*://}"
    local host_and_port="${without_scheme%%/*}"

    printf '%s' "${host_and_port%%:*}"
}

extract_url_scheme() {
    local url="$1"

    printf '%s' "${url%%://*}"
}

extract_url_port() {
    local url="$1"
    local fallback_port="$2"
    local without_scheme="${url#*://}"
    local host_and_port="${without_scheme%%/*}"

    if [[ "${host_and_port}" == *:* ]]; then
        printf '%s' "${host_and_port##*:}"
        return 0
    fi

    printf '%s' "${fallback_port}"
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
        native_android_keep_loopback_endpoints=1

        if [[ -z "${public_base_url}" && ! ("${mode}" == "watch" && "${watch_prefers_tailscale}" -eq 1) ]]; then
            public_base_url="http://127.0.0.1:${port}"
        fi
    fi
fi

if [[ -z "${public_base_url}" && "${mode}" == "watch" ]]; then
    tailscale_funnel_target="localhost:${port}"

    if log_tailscale_diagnostics && start_tailscale_funnel "${tailscale_funnel_target}"; then
        for _ in {1..80}; do
            if funnel_url="$(resolve_tailscale_funnel_url || true)"; then
                if [[ -n "${funnel_url}" ]]; then
                    public_base_url="${funnel_url}"
                    echo "[native-local-source-broadcast] using tailscale funnel ${public_base_url}" >&2
                    start_tailscale_reverb_funnel "localhost:${reverb_server_port}" || true
                    break
                fi
            fi

            if ! kill -0 "${tailscale_funnel_pid}" >/dev/null 2>&1; then
                echo "[native-local-source-broadcast] tailscale funnel exited early. See ${tailscale_log_file}" >&2
                stop_tailscale_funnel
                break
            fi

            sleep 0.25
        done

        if [[ -z "${public_base_url}" ]]; then
            echo "[native-local-source-broadcast] tailscale funnel did not become ready in time. Falling back to LAN host." >&2
            stop_tailscale_funnel
        fi
    fi
fi

if [[ -z "${public_base_url}" ]]; then
    local_lan_ipv4="$(resolve_local_lan_ipv4 || true)"

    if [[ "${platform}" == "android" ]]; then
        if [[ -n "${local_lan_ipv4}" && "${android_host}" == "10.0.2.2" && "${adb_reverse_active}" -eq 0 ]]; then
            public_base_url="http://${local_lan_ipv4}:${port}"
            echo "[native-local-source-broadcast] adb reverse is inactive; using detected LAN host ${local_lan_ipv4}" >&2
        else
            public_base_url="http://${android_host}:${port}"
        fi
    elif [[ -n "${local_lan_ipv4}" ]] && is_loopback_style_host "${ios_host}"; then
        public_base_url="http://${local_lan_ipv4}:${port}"
        echo "[native-local-source-broadcast] using detected LAN host ${local_lan_ipv4} for ios" >&2
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
public_scheme="$(extract_url_scheme "${public_base_url}")"
public_reverb_port="${NATIVE_QURAN_LOCAL_REVERB_PUBLIC_PORT:-$(extract_url_port "${public_base_url}" "$([[ "${public_scheme}" == "https" ]] && printf '443' || printf '80')")}"

if [[ "${tailscale_funnel_reverb_active}" -eq 1 ]]; then
    public_reverb_port="${tailscale_funnel_reverb_https_port}"
fi

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
    stop_tailscale_funnel

    if [[ "${adb_reverse_active}" -eq 1 ]]; then
        adb reverse --remove "tcp:${port}" >/dev/null 2>&1 || true
    fi

    if [[ -n "${composer_dev_pid}" ]] && kill -0 "${composer_dev_pid}" >/dev/null 2>&1; then
        kill "${composer_dev_pid}" >/dev/null 2>&1 || true
        wait "${composer_dev_pid}" >/dev/null 2>&1 || true
    fi
}

trap cleanup EXIT INT TERM

mkdir -p "$(dirname "${composer_dev_log_file}")"
: >"${composer_dev_log_file}"

# Build the web assets so the funnel-served web app (composer dev:native serves
# built assets, no Vite) ships the latest realtime client, which reads the Reverb
# host injected at runtime. Skippable for speed once built.
if [[ "${NATIVE_LOCAL_SKIP_WEB_BUILD:-0}" != "1" ]]; then
    echo "[native-local-source-broadcast] building web assets (set NATIVE_LOCAL_SKIP_WEB_BUILD=1 to skip)..." >&2
    (cd "${project_root}" && pnpm run build) >>"${composer_dev_log_file}" 2>&1 || {
        echo "[native-local-source-broadcast] web asset build failed. See ${composer_dev_log_file}" >&2
        exit 1
    }
fi

(
    cd "${project_root}"
    SERVER_HOST="${bind_host}" SERVER_PORT="${port}" \
        REVERB_PUBLIC_HOST="${public_host}" \
        REVERB_PUBLIC_PORT="${public_reverb_port}" \
        REVERB_PUBLIC_SCHEME="${public_scheme}" \
        REVERB_ALLOWED_ORIGINS="*" \
        composer dev:native >"${composer_dev_log_file}" 2>&1
) &

composer_dev_pid="$!"

for _ in {1..60}; do
    if ! kill -0 "${composer_dev_pid}" >/dev/null 2>&1; then
        echo "[native-local-source-broadcast] composer dev exited early. See ${composer_dev_log_file}" >&2
        exit 1
    fi

    if curl --silent --fail --max-time 2 "http://127.0.0.1:${port}/api/settings" >/dev/null 2>&1; then
        server_ready=1
        break
    fi

    sleep 0.25
done

if [[ "${server_ready}" -ne 1 ]]; then
    echo "[native-local-source-broadcast] local API server did not become ready in time. See ${composer_dev_log_file}" >&2
    exit 1
fi

meta_endpoint="${public_base_url}/api/quran-snapshot/meta"
download_endpoint="${public_base_url}/api/quran-snapshot/download"
settings_endpoint="${public_base_url}/api/settings"
telegram_auth_endpoint="${public_base_url}/auth/telegram/native"

echo "[native-local-source-broadcast] local API server: http://${bind_host}:${port}"
echo "[native-local-source-broadcast] settings endpoint: ${settings_endpoint}"
echo "[native-local-source-broadcast] meta endpoint: ${meta_endpoint}"
echo "[native-local-source-broadcast] download endpoint: ${download_endpoint}"
echo "[native-local-source-broadcast] telegram auth endpoint: ${telegram_auth_endpoint}"
echo "[native-local-source-broadcast] reverb host: ${public_host}:${public_reverb_port} (${public_scheme})"
echo "[native-local-source-broadcast] running composer dev:native + ${native_script}"
echo "[native-local-source-broadcast] ---"
echo "[native-local-source-broadcast] LAPTOP (web) test client: open  http://127.0.0.1:${port}  in your browser"
echo "[native-local-source-broadcast]   (NOT the ${public_host} funnel URL — this machine is on the tailnet, so"
echo "[native-local-source-broadcast]    MagicDNS routes the funnel domain to the local node, which only serves"
echo "[native-local-source-broadcast]    TLS on :${public_reverb_port}, not :443. The phone uses the funnel; the laptop uses local.)"
echo "[native-local-source-broadcast]   Sign in with username/password (same account as the phone)."
echo "[native-local-source-broadcast]   Reverb for both is ${public_host}:${public_reverb_port} (${public_scheme}); the laptop reaches it"
echo "[native-local-source-broadcast]   over the tailnet, so settings/progress sync live both ways. Other"
echo "[native-local-source-broadcast]   accounts/guests are isolated (private per-account channel)."
echo "[native-local-source-broadcast] ---"

(
    cd "${project_root}"
    NATIVE_SETTINGS_ENDPOINT="${settings_endpoint}" \
        NATIVE_QURAN_SNAPSHOT_META_ENDPOINT="${meta_endpoint}" \
        NATIVE_QURAN_SNAPSHOT_DOWNLOAD_ENDPOINT="${download_endpoint}" \
        NATIVE_TELEGRAM_AUTH_ENDPOINT="${telegram_auth_endpoint}" \
        NATIVE_ANDROID_KEEP_LOOPBACK_ENDPOINTS="${native_android_keep_loopback_endpoints}" \
        NATIVEPHP_RUNNING="true" \
        NATIVEPHP_PLATFORM="${platform}" \
        BROADCAST_CONNECTION="null" \
        VITE_REVERB_HOST="${public_host}" \
        VITE_REVERB_PORT="${public_reverb_port}" \
        VITE_REVERB_SCHEME="${public_scheme}" \
        "${native_script}"
)
