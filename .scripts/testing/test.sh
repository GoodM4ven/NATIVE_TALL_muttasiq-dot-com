#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
script_name="$(basename "${BASH_SOURCE[0]}")"
project_name="$(basename "${root_dir}")"
container_project_root="/var/www/html/${project_name}"
run_clean_script="${root_dir}/.scripts/testing/support/run-clean.sh"
plugin_cache_relative_path="vendor/pest-plugins.json"
xdebug_mode="${XDEBUG_MODE:-off}"

if [[ ! -x "${run_clean_script}" ]]; then
    echo "Missing executable script at ${run_clean_script}" >&2
    exit 1
fi

resolve_test_container() {
    if ! command -v docker >/dev/null 2>&1; then
        return 1
    fi

    local container_name="${TEST_CONTAINER:-${TESTING_CONTAINER:-}}"

    if [[ -n "${container_name}" ]]; then
        if docker ps --format '{{.Names}}' 2>/dev/null | grep -Fxq "${container_name}"; then
            printf '%s\n' "${container_name}"

            return 0
        fi

        return 1
    fi

    local container_lines
    container_lines="$(docker ps --format '{{.Names}} {{.Label "com.docker.compose.service"}} {{.Label "com.docker.compose.project"}}' 2>/dev/null || true)"

    if [[ -z "${container_lines}" ]]; then
        return 1
    fi

    container_name="$(awk '$2 == "app" && $3 == "lara-stacker" { print $1; exit }' <<<"${container_lines}")"

    if [[ -z "${container_name}" ]]; then
        container_name="$(awk '$2 == "app" { print $1; exit }' <<<"${container_lines}")"
    fi

    if [[ -z "${container_name}" ]]; then
        return 1
    fi

    printf '%s\n' "${container_name}"
}

print_runtime_indicator() {
    local mode="$1"
    local container_name="${2:-}"

    if [[ "${mode}" == "docker" ]]; then
        echo "[testing:${script_name}] mode=docker container=${container_name}" >&2

        return
    fi

    echo "[testing:${script_name}] mode=local" >&2
}

run_local() (
    set -euo pipefail
    cd "${root_dir}"
    print_runtime_indicator "local"
    export XDEBUG_MODE="${xdebug_mode}"

    plugin_cache_file="${plugin_cache_relative_path}"
    backup_file=""

    if [[ -f "${plugin_cache_file}" ]]; then
        backup_file="${plugin_cache_file}.test.bak.$$"
        cp "${plugin_cache_file}" "${backup_file}"
        trap 'if [[ -n "${backup_file}" && -f "${backup_file}" ]]; then mv "${backup_file}" "${plugin_cache_file}"; fi' EXIT INT TERM
        sed -i.bak '/"Pest\\\\Browser\\\\Plugin"/d' "${plugin_cache_file}"
        rm -f "${plugin_cache_file}.bak"
    fi

    PEST_ENABLE_BROWSER_PLUGIN=0 "${run_clean_script}" vendor/bin/pest --exclude-group=browser "$@"
)

run_in_container() {
    local container_name="$1"
    shift

    print_runtime_indicator "docker" "${container_name}"

    docker exec \
        -e PEST_ENABLE_BROWSER_PLUGIN=0 \
        -e "XDEBUG_MODE=${xdebug_mode}" \
        -w "${container_project_root}" \
        "${container_name}" \
        sh -lc '
            set -eu

            plugin_cache_file="'"${plugin_cache_relative_path}"'"
            backup_file=""

            if [ -f "${plugin_cache_file}" ]; then
                backup_file="${plugin_cache_file}.test.bak.$$"
                cp "${plugin_cache_file}" "${backup_file}"
                trap '"'"'if [ -n "${backup_file}" ] && [ -f "${backup_file}" ]; then mv "${backup_file}" "${plugin_cache_file}"; fi'"'"' EXIT INT TERM
                sed -i.bak '"'"'/"Pest\\\\Browser\\\\Plugin"/d'"'"' "${plugin_cache_file}"
                rm -f "${plugin_cache_file}.bak"
            fi

            PEST_ENABLE_BROWSER_PLUGIN=0 .scripts/testing/support/run-clean.sh vendor/bin/pest --exclude-group=browser "$@"
        ' sh "$@"
}

if container_name="$(resolve_test_container)"; then
    run_in_container "${container_name}" "$@"
    exit 0
fi

run_local "$@"
