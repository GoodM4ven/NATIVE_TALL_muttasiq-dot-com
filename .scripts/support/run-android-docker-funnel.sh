#!/usr/bin/env bash
set -euo pipefail

# Runs the Android native watch against the EXISTING lara-stacker Docker app
# (https://<app>.dev.localhost) exposed over a single Tailscale Funnel on :443.
# No parallel `php artisan serve` and no second funnel port: the app and its
# Reverb websocket both ride the one :443 funnel domain, which is host-only so
# Telegram's login widget accepts it. The Funnel runs in lara-stacker's Tailscale
# container, which shares Caddy's network and proxies to its dedicated :8081 site.

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

reverb_server_port="${REVERB_SERVER_PORT:-8080}"
reverb_pid=""
tailscale_container="${LARA_STACKER_TAILSCALE_CONTAINER:-}"

resolve_lara_stacker_tailscale_container() {
    docker ps \
        --filter 'label=com.docker.compose.project=lara-stacker' \
        --filter 'label=com.docker.compose.service=tailscale' \
        --filter 'status=running' \
        --format '{{.ID}}' \
        | awk 'NR == 1 { print; exit }'
}

tailscale_exec() {
    docker exec "${tailscale_container}" tailscale "$@"
}

# The port-less :443 funnel URL is the authoritative FQDN (that's what Telegram and
# the phone hit). Read it back from `funnel status` rather than guessing "Self".
resolve_funnel_fqdn() {
    tailscale_exec funnel status 2>/dev/null \
        | grep -Eo 'https://[a-zA-Z0-9.-]+\.ts\.net([[:space:]]|$)' \
        | head -n 1 \
        | sed -E 's#https://([^[:space:]]+).*#\1#'
}

is_port_in_use() {
    local port="${1}"

    if command -v ss >/dev/null 2>&1; then
        ss -lnt | awk -v target_port=":${port}" '$4 ~ target_port"$" { found = 1 } END { exit found ? 0 : 1 }'
        return $?
    fi

    if command -v lsof >/dev/null 2>&1; then
        [[ -n "$(lsof -nP -iTCP:"${port}" -sTCP:LISTEN -t 2>/dev/null)" ]]
        return $?
    fi

    if command -v netstat >/dev/null 2>&1; then
        netstat -an -p tcp 2>/dev/null \
            | awk -v target_port=".${port}" '$4 ~ target_port"$" && $6 == "LISTEN" { found = 1 } END { exit found ? 0 : 1 }'
        return $?
    fi

    echo "[android-docker-funnel] cannot inspect TCP ports: install lsof, iproute2, or net-tools." >&2
    return 2
}

resolve_reverb_app_key() {
    (
        cd "${project_root}"
        php artisan tinker --execute 'echo (string) config("broadcasting.connections.reverb.key");' 2>/dev/null
    ) | tr -d '\r\n'
}

websocket_route_accepts_handshake() {
    local host="${1}"
    local app_key="${2}"
    local status_line

    status_line="$(
        curl --silent --include --no-buffer --http1.1 --max-time 3 \
            --header "Origin: https://${host}" \
            --header 'Connection: Upgrade' \
            --header 'Upgrade: websocket' \
            --header 'Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==' \
            --header 'Sec-WebSocket-Version: 13' \
            "https://${host}/app/${app_key}?protocol=7&client=js&version=8.5.0&flash=false" \
            2>/dev/null \
            | head -n 1
    )" || true

    [[ "${status_line}" == *" 101 "* ]]
}

if ! command -v docker >/dev/null 2>&1; then
    echo "[android-docker-funnel] Docker was not found." >&2
    exit 1
fi

# Point lara-stacker's generic funnel document root at THIS project. Caddy serves
# /var/www/html/_funnel_app/public through its internal :8081 site, so we symlink
# `_funnel_app` in the apps root to this project.
apps_root_dir="$(dirname "${project_root}")"
ln -sfn "$(basename "${project_root}")" "${apps_root_dir}/_funnel_app" \
    || { echo "[android-docker-funnel] could not create _funnel_app in ${apps_root_dir}." >&2; exit 1; }

if [[ -z "${tailscale_container}" ]]; then
    tailscale_container="$(resolve_lara_stacker_tailscale_container || true)"
fi

if [[ -z "${tailscale_container}" ]]; then
    echo "[android-docker-funnel] lara-stacker's Tailscale container is not running." >&2
    echo "[android-docker-funnel] start lara-stacker with its Tailscale profile enabled." >&2
    exit 1
fi

tailscale_exec funnel --yes --bg --https=443 http://127.0.0.1:8081 \
    || { echo "[android-docker-funnel] lara-stacker's Tailscale container could not expose Caddy :8081." >&2; exit 1; }

funnel_fqdn=""
for _ in {1..20}; do
    funnel_fqdn="$(resolve_funnel_fqdn || true)"
    [[ -n "${funnel_fqdn}" ]] && break
    sleep 0.25
done

if [[ -z "${funnel_fqdn}" ]]; then
    echo "[android-docker-funnel] lara-stacker's Funnel did not report a :443 .ts.net URL." >&2
    echo "[android-docker-funnel] inspect it with: docker exec ${tailscale_container} tailscale funnel status" >&2
    exit 1
fi

public_base_url="https://${funnel_fqdn}"

cleanup() {
    if [[ -n "${reverb_pid}" ]] && kill -0 "${reverb_pid}" >/dev/null 2>&1; then
        kill "${reverb_pid}" >/dev/null 2>&1 || true
        wait "${reverb_pid}" >/dev/null 2>&1 || true
    fi
}

trap cleanup EXIT INT TERM

# Reverb on the host, reachable by Caddy via host.docker.internal:<port>. Skip if
# something (e.g. `composer dev`) already holds the port — then make sure that
# Reverb allows the funnel origin (REVERB_ALLOWED_ORIGINS).
if is_port_in_use "${reverb_server_port}"; then
    echo "[android-docker-funnel] Reverb port ${reverb_server_port} already in use; reusing it." >&2
    echo "[android-docker-funnel] ensure that Reverb's REVERB_ALLOWED_ORIGINS includes https://${funnel_fqdn}" >&2
else
    (
        cd "${project_root}"
        exec env REVERB_ALLOWED_ORIGINS="*" php artisan reverb:start --host=0.0.0.0 --port="${reverb_server_port}"
    ) &
    reverb_pid="$!"
fi

# Wait for the Docker app to answer over the funnel host (Caddy -> php-fpm).
server_ready=0
for _ in {1..40}; do
    if curl --silent --fail --max-time 3 "${public_base_url}/api/settings" >/dev/null 2>&1; then
        server_ready=1
        break
    fi
    sleep 0.5
done

if [[ "${server_ready}" -ne 1 ]]; then
    echo "[android-docker-funnel] the Docker app did not answer for host ${funnel_fqdn}." >&2
    echo "[android-docker-funnel] confirm lara-stacker Caddy and Tailscale containers are healthy and _funnel_app points to this project." >&2
    exit 1
fi

reverb_app_key="$(resolve_reverb_app_key || true)"

if [[ -z "${reverb_app_key}" ]]; then
    echo "[android-docker-funnel] could not resolve REVERB_APP_KEY from Laravel config." >&2
    exit 1
fi

reverb_route_ready=0
for _ in {1..20}; do
    if websocket_route_accepts_handshake "${funnel_fqdn}" "${reverb_app_key}"; then
        reverb_route_ready=1
        break
    fi
    sleep 0.5
done

if [[ "${reverb_route_ready}" -ne 1 ]]; then
    echo "[android-docker-funnel] Reverb websocket did not answer at wss://${funnel_fqdn}/app." >&2
    echo "[android-docker-funnel] confirm Reverb is listening on host :${reverb_server_port} and lara-stacker Caddy has been restarted with the /app proxy." >&2
    exit 1
fi

settings_endpoint="${public_base_url}/api/settings"
meta_endpoint="${public_base_url}/api/quran-snapshot/meta"
download_endpoint="${public_base_url}/api/quran-snapshot/download"
telegram_auth_endpoint="${public_base_url}/auth/telegram/native"

echo "[android-docker-funnel] funnel + Docker app: ${public_base_url}"
echo "[android-docker-funnel] settings endpoint: ${settings_endpoint}"
echo "[android-docker-funnel] telegram auth endpoint: ${telegram_auth_endpoint}"
echo "[android-docker-funnel] reverb: wss://${funnel_fqdn}/app (proxied to host :${reverb_server_port})"
echo "[android-docker-funnel] set the Telegram login-widget domain to ${funnel_fqdn} in BotFather."
echo "[android-docker-funnel] ---"

(
    cd "${project_root}"
    NATIVE_SETTINGS_ENDPOINT="${settings_endpoint}" \
        NATIVE_QURAN_SNAPSHOT_META_ENDPOINT="${meta_endpoint}" \
        NATIVE_QURAN_SNAPSHOT_DOWNLOAD_ENDPOINT="${download_endpoint}" \
        NATIVE_TELEGRAM_AUTH_ENDPOINT="${telegram_auth_endpoint}" \
        NATIVE_ANDROID_KEEP_LOOPBACK_ENDPOINTS="0" \
        NATIVEPHP_RUNNING="true" \
        NATIVEPHP_PLATFORM="android" \
        BROADCAST_CONNECTION="null" \
        VITE_REVERB_HOST="${funnel_fqdn}" \
        VITE_REVERB_PORT="443" \
        VITE_REVERB_SCHEME="https" \
        "${project_root}/.scripts/support/watch-android-native.sh"
)
