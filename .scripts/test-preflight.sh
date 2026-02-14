#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
state_file="${root_dir}/vendor/pestphp/pest-plugin-browser/.temp/playwright-server.json"
project_playwright_bin="${root_dir}/node_modules/.bin/playwright"
project_name="$(basename "${root_dir}")"

if [[ -f "${state_file}" ]]; then
    rm -f "${state_file}"
fi

if [[ "${OSTYPE:-}" != "msys" && "${OSTYPE:-}" != "cygwin" && "${OSTYPE:-}" != "win32" ]]; then
    if command -v pgrep >/dev/null 2>&1; then
        mapfile -t playwright_pids < <(pgrep -f "${project_playwright_bin} run-server" || true)

        if [[ "${#playwright_pids[@]}" -eq 0 ]]; then
            mapfile -t playwright_pids < <(pgrep -f "\\./node_modules/\\.bin/playwright run-server --host .* --port .* --mode launchServer" || true)
        fi

        if [[ "${#playwright_pids[@]}" -gt 0 ]]; then
            kill -TERM "${playwright_pids[@]}" >/dev/null 2>&1 || true
            sleep 0.3

            mapfile -t still_running_pids < <(ps -o pid= -p "${playwright_pids[@]}" 2>/dev/null | awk '{print $1}' || true)

            if [[ "${#still_running_pids[@]}" -gt 0 ]]; then
                kill -KILL "${still_running_pids[@]}" >/dev/null 2>&1 || true
            fi
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
            project_playwright_bin="${PROJECT_ROOT}/node_modules/.bin/playwright"

            if [ -f "${state_file}" ]; then
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
        ' >/dev/null 2>&1 || true
done
