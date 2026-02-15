#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
state_file="${root_dir}/vendor/pestphp/pest-plugin-browser/.temp/playwright-server.json"
temp_dir="${root_dir}/vendor/pestphp/pest-plugin-browser/.temp"
project_playwright_bin="${root_dir}/node_modules/.bin/playwright"
project_name="$(basename "${root_dir}")"

collect_descendant_pids() {
    local pid="$1"
    local children=()

    mapfile -t children < <(pgrep -P "${pid}" || true)

    if [[ "${#children[@]}" -eq 0 ]]; then
        return
    fi

    local child
    for child in "${children[@]}"; do
        collect_descendant_pids "${child}"
        echo "${child}"
    done
}

kill_process_tree() {
    local root_pid="$1"
    local descendants=()
    local targets=()

    mapfile -t descendants < <(collect_descendant_pids "${root_pid}" || true)

    if [[ "${#descendants[@]}" -gt 0 ]]; then
        targets=("${descendants[@]}" "${root_pid}")
    else
        targets=("${root_pid}")
    fi

    kill -TERM "${targets[@]}" >/dev/null 2>&1 || true
    sleep 0.3

    local survivors=()
    mapfile -t survivors < <(ps -o pid= -p "${targets[@]}" 2>/dev/null | awk '{print $1}' || true)

    if [[ "${#survivors[@]}" -gt 0 ]]; then
        kill -KILL "${survivors[@]}" >/dev/null 2>&1 || true
    fi
}

state_port=""
if [[ -f "${state_file}" ]]; then
    state_port="$(grep -Eo '"port":[0-9]+' "${state_file}" | head -n1 | cut -d: -f2 || true)"
fi

if [[ -d "${temp_dir}" ]]; then
    rm -f "${temp_dir}"/*.json >/dev/null 2>&1 || true
fi

if [[ "${OSTYPE:-}" != "msys" && "${OSTYPE:-}" != "cygwin" && "${OSTYPE:-}" != "win32" ]]; then
    if command -v pgrep >/dev/null 2>&1; then
        mapfile -t playwright_pids < <(pgrep -f "${project_playwright_bin} run-server" || true)

        if [[ "${#playwright_pids[@]}" -eq 0 ]]; then
            mapfile -t playwright_pids < <(pgrep -f "\\./node_modules/\\.bin/playwright run-server --host .* --port .* --mode launchServer" || true)
        fi

        if [[ -n "${state_port}" ]] && command -v lsof >/dev/null 2>&1; then
            mapfile -t state_port_pids < <(lsof -tiTCP:"${state_port}" -sTCP:LISTEN 2>/dev/null || true)
            if [[ "${#state_port_pids[@]}" -gt 0 ]]; then
                mapfile -t playwright_pids < <(
                    printf '%s\n' "${playwright_pids[@]}" "${state_port_pids[@]}" \
                        | awk 'NF && !seen[$1]++'
                )
            fi
        fi

        if [[ "${#playwright_pids[@]}" -gt 0 ]]; then
            for pid in "${playwright_pids[@]}"; do
                kill_process_tree "${pid}"
            done
        fi

        mapfile -t orphan_browser_pids < <(pgrep -f 'chrome-headless-shell.*--user-data-dir=/tmp/playwright_' || true)

        if [[ "${#orphan_browser_pids[@]}" -gt 0 ]]; then
            kill -TERM "${orphan_browser_pids[@]}" >/dev/null 2>&1 || true
            sleep 0.2
            kill -KILL "${orphan_browser_pids[@]}" >/dev/null 2>&1 || true
        fi

    fi
fi

if ! command -v docker >/dev/null 2>&1; then
    exit 0
fi

container_lines="$(docker ps --format '{{.Names}} {{.Label "com.docker.compose.service"}} {{.Label "com.docker.compose.project"}}' 2>/dev/null || true)"

if [[ -z "${container_lines}" ]]; then
    exit 0
fi

mapfile -t lara_stacker_app_containers < <(
    awk '$2 == "app" && $3 == "lara-stacker" { print $1 }' <<<"${container_lines}"
)

if [[ "${#lara_stacker_app_containers[@]}" -eq 0 ]]; then
    exit 0
fi

for container_name in "${lara_stacker_app_containers[@]}"; do
    container_project_root="/var/www/html/${project_name}"

    docker exec \
        -e "PROJECT_ROOT=${container_project_root}" \
        "${container_name}" \
        sh -lc '
            state_file="${PROJECT_ROOT}/vendor/pestphp/pest-plugin-browser/.temp/playwright-server.json"
            temp_dir="${PROJECT_ROOT}/vendor/pestphp/pest-plugin-browser/.temp"
            project_playwright_bin="${PROJECT_ROOT}/node_modules/.bin/playwright"

            if [ -d "${temp_dir}" ]; then
                rm -f "${temp_dir}"/*.json >/dev/null 2>&1 || true
            elif [ -f "${state_file}" ]; then
                rm -f "${state_file}"
            fi

            if ! command -v pgrep >/dev/null 2>&1; then
                exit 0
            fi

            pids="$(pgrep -f "${project_playwright_bin} run-server" || true)"

            if [ -z "${pids}" ]; then
                pids="$(pgrep -f "\./node_modules/\.bin/playwright run-server --host .* --port .* --mode launchServer" || true)"
            fi

            if [ -z "${pids}" ]; then
                exit 0
            fi

            kill -TERM ${pids} >/dev/null 2>&1 || true
            sleep 0.3

            surviving_pids=""
            for pid in ${pids}; do
                if ps -p "${pid}" >/dev/null 2>&1; then
                    surviving_pids="${surviving_pids} ${pid}"
                fi
            done

            if [ -n "${surviving_pids}" ]; then
                kill -KILL ${surviving_pids} >/dev/null 2>&1 || true
            fi

            orphan_pids="$(pgrep -f "chrome-headless-shell.*--user-data-dir=/tmp/playwright_" || true)"

            if [ -n "${orphan_pids}" ]; then
                kill -TERM ${orphan_pids} >/dev/null 2>&1 || true
                sleep 0.2
                kill -KILL ${orphan_pids} >/dev/null 2>&1 || true
            fi

        ' >/dev/null 2>&1 || true
done
