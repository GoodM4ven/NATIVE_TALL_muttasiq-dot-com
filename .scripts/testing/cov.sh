#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
script_name="$(basename "${BASH_SOURCE[0]}")"
project_name="$(basename "${root_dir}")"
container_project_root="/var/www/html/${project_name}"
plugin_cache_relative_path="vendor/pest-plugins.json"
testcov_memory_limit="${TESTCOV_MEMORY_LIMIT:-768M}"

print_runtime_indicator() {
    local mode="$1"
    local container_name="${2:-}"

    if [[ "${mode}" == "docker" ]]; then
        echo "[testing:${script_name}] mode=docker container=${container_name}" >&2

        return
    fi

    echo "[testing:${script_name}] mode=local" >&2
}

run_pest_coverage() {
    .scripts/testing/support/run-clean.sh \
        php -d "memory_limit=${testcov_memory_limit}" vendor/bin/pest \
        tests/Unit \
        tests/Feature/App \
        --testdox-summary \
        --display-skipped \
        --display-all-issues \
        --coverage \
        --exclude-group=browser \
        "$@"
}

run_testcov_in_current_shell() (
    set -euo pipefail
    cd "${root_dir}"
    print_runtime_indicator "local"

    plugin_cache_file="${plugin_cache_relative_path}"
    backup_file=""

    if [[ -f "${plugin_cache_file}" ]]; then
        backup_file="${plugin_cache_file}.testcov.bak.$$"
        cp "${plugin_cache_file}" "${backup_file}"
        trap 'if [[ -n "${backup_file}" && -f "${backup_file}" ]]; then mv "${backup_file}" "${plugin_cache_file}"; fi' EXIT INT TERM
        sed -i.bak '/"Pest\\\\Browser\\\\Plugin"/d' "${plugin_cache_file}"
        rm -f "${plugin_cache_file}.bak"
    fi

    PEST_ENABLE_BROWSER_PLUGIN=0 XDEBUG_MODE=coverage run_pest_coverage "$@"
)

if ! command -v docker >/dev/null 2>&1; then
    run_testcov_in_current_shell "$@"
    exit 0
fi

container_name="${TESTCOV_CONTAINER:-${TEST_CONTAINER:-${TESTING_CONTAINER:-}}}"

if [[ -z "${container_name}" ]]; then
    container_lines="$(docker ps --format '{{.Names}} {{.Label "com.docker.compose.service"}} {{.Label "com.docker.compose.project"}}' 2>/dev/null || true)"

    if [[ -n "${container_lines}" ]]; then
        container_name="$(awk '$2 == "app" && $3 == "lara-stacker" { print $1; exit }' <<<"${container_lines}")"

        if [[ -z "${container_name}" ]]; then
            container_name="$(awk '$2 == "app" { print $1; exit }' <<<"${container_lines}")"
        fi
    fi
fi

if [[ -n "${container_name}" ]] && ! docker ps --format '{{.Names}}' 2>/dev/null | grep -Fxq "${container_name}"; then
    run_testcov_in_current_shell "$@"
    exit 0
fi

if [[ -z "${container_name}" ]]; then
    run_testcov_in_current_shell "$@"
    exit 0
fi

print_runtime_indicator "docker" "${container_name}"

docker exec \
    -e PEST_ENABLE_BROWSER_PLUGIN=0 \
    -e XDEBUG_MODE=coverage \
    -e "TESTCOV_MEMORY_LIMIT=${testcov_memory_limit}" \
    -w "${container_project_root}" \
    "${container_name}" \
    sh -lc '
        set -eu

        plugin_cache_file="'"${plugin_cache_relative_path}"'"
        backup_file=""

        if [ -f "${plugin_cache_file}" ]; then
            backup_file="${plugin_cache_file}.testcov.bak.$$"
            cp "${plugin_cache_file}" "${backup_file}"
            trap '"'"'if [ -n "${backup_file}" ] && [ -f "${backup_file}" ]; then mv "${backup_file}" "${plugin_cache_file}"; fi'"'"' EXIT INT TERM
            sed -i.bak '"'"'/"Pest\\\\Browser\\\\Plugin"/d'"'"' "${plugin_cache_file}"
            rm -f "${plugin_cache_file}.bak"
        fi

        .scripts/testing/support/run-clean.sh \
            php -d "memory_limit=${TESTCOV_MEMORY_LIMIT}" vendor/bin/pest \
            tests/Unit \
            tests/Feature/App \
            --testdox-summary \
            --display-skipped \
            --display-all-issues \
            --coverage \
            --exclude-group=browser \
            "$@"
    ' sh "$@"
