#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
script_name="$(basename "${BASH_SOURCE[0]}")"
project_name="$(basename "${root_dir}")"
container_project_root="/var/www/html/${project_name}"
run_clean_script="${root_dir}/.scripts/testing/support/run-clean.sh"
browser_tests_path="${BROWSER_TESTS_PATH:-tests/Feature/Browser}"
plugin_cache_relative_path="vendor/pest-plugins.json"
browser_plugin_signature='Pest\\Browser\\Plugin'
xdebug_mode="${XDEBUG_MODE:-off}"

if [[ ! -x "${run_clean_script}" ]]; then
    echo "Missing executable script at ${run_clean_script}" >&2
    exit 1
fi

resolve_test_container() {
    if ! command -v docker >/dev/null 2>&1; then
        return 1
    fi

    local container_name="${TEST_CONTAINER:-${TESTING_CONTAINER:-${TEST_BROWSER_CONTAINER:-}}}"

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
    local cpu_cores="${3:-}"
    local parallel_processes="${4:-}"

    if [[ "${mode}" == "docker" ]]; then
        echo "[testing:${script_name}] mode=docker container=${container_name} cpu=${cpu_cores} processes=${parallel_processes}" >&2

        return
    fi

    if [[ -n "${cpu_cores}" && -n "${parallel_processes}" ]]; then
        echo "[testing:${script_name}] mode=local cpu=${cpu_cores} processes=${parallel_processes}" >&2

        return
    fi

    echo "[testing:${script_name}] mode=local" >&2
}

run_compact_command() {
    if command -v timeout >/dev/null 2>&1; then
        "${run_clean_script}" timeout 5m vendor/bin/pest --compact "${browser_tests_path}" "$@"

        return
    fi

    if [[ -x /usr/bin/timeout ]]; then
        "${run_clean_script}" /usr/bin/timeout 5m vendor/bin/pest --compact "${browser_tests_path}" "$@"

        return
    fi

    "${run_clean_script}" vendor/bin/pest --compact "${browser_tests_path}" "$@"
}

run_parallel_command() {
    local parallel_processes="$1"
    shift

    if command -v timeout >/dev/null 2>&1; then
        "${run_clean_script}" timeout 5m vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${browser_tests_path}" "$@"

        return
    fi

    if [[ -x /usr/bin/timeout ]]; then
        "${run_clean_script}" /usr/bin/timeout 5m vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${browser_tests_path}" "$@"

        return
    fi

    "${run_clean_script}" vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${browser_tests_path}" "$@"
}

ensure_local_browser_plugin_cache() {
    local plugin_cache_file="${root_dir}/${plugin_cache_relative_path}"

    if [[ -f "${plugin_cache_file}" ]] && grep -Fq "${browser_plugin_signature}" "${plugin_cache_file}"; then
        return
    fi

    if command -v composer >/dev/null 2>&1; then
        (
            cd "${root_dir}"
            composer pest:dump-plugins >/dev/null 2>&1 || true
        )
    fi

    if [[ -f "${plugin_cache_file}" ]] && grep -Fq "${browser_plugin_signature}" "${plugin_cache_file}"; then
        return
    fi

    echo "Missing Pest Browser plugin cache entry. Run 'composer pest:dump-plugins'." >&2
    exit 1
}

run_local() (
    cd "${root_dir}"
    ensure_local_browser_plugin_cache
    export XDEBUG_MODE="${xdebug_mode}"

    local use_parallel="${BROWSER_TEST_PARALLEL:-1}"

    if [[ "${use_parallel}" == "0" ]]; then
        print_runtime_indicator "local"
        run_compact_command "$@"
        return
    fi

    local cpu_cores
    local parallel_processes

    if command -v nproc >/dev/null 2>&1; then
        cpu_cores="$(nproc 2>/dev/null || true)"
    elif command -v getconf >/dev/null 2>&1; then
        cpu_cores="$(getconf _NPROCESSORS_ONLN 2>/dev/null || true)"
    else
        cpu_cores=""
    fi

    if [[ -z "${cpu_cores}" ]] || ! printf "%s" "${cpu_cores}" | grep -Eq "^[0-9]+$" || [[ "${cpu_cores}" -lt 1 ]]; then
        cpu_cores=1
    fi

    local reserved_cores="${TEST_RESERVED_CORES:-1}"
    local max_processes="${TEST_BROWSER_MAX_PROCESSES:-${TEST_MAX_PROCESSES:-4}}"

    if ! printf "%s" "${reserved_cores}" | grep -Eq "^[0-9]+$"; then
        reserved_cores=1
    fi

    if ! printf "%s" "${max_processes}" | grep -Eq "^[0-9]+$" || [[ "${max_processes}" -lt 1 ]]; then
        max_processes=4
    fi

    parallel_processes=$(( cpu_cores - reserved_cores ))

    if [[ "${parallel_processes}" -lt 1 ]]; then
        parallel_processes=1
    fi

    if [[ "${parallel_processes}" -gt "${max_processes}" ]]; then
        parallel_processes="${max_processes}"
    fi

    print_runtime_indicator "local" "" "${cpu_cores}" "${parallel_processes}"
    run_parallel_command "${parallel_processes}" "$@"
)

run_in_container() {
    local container_name="$1"
    shift

    docker exec \
        -e "BROWSER_TESTS_PATH=${browser_tests_path}" \
        -e "XDEBUG_MODE=${xdebug_mode}" \
        -e "TESTING_SCRIPT_NAME=${script_name}" \
        -e "TESTING_CONTAINER_NAME=${container_name}" \
        -e "TEST_RESERVED_CORES=${TEST_RESERVED_CORES:-1}" \
        -e "TEST_MAX_PROCESSES=${TEST_MAX_PROCESSES:-8}" \
        -e "TEST_BROWSER_MAX_PROCESSES=${TEST_BROWSER_MAX_PROCESSES:-}" \
        -e "TEST_CPU_CORES=${TEST_CPU_CORES:-}" \
        -w "${container_project_root}" \
        "${container_name}" \
        sh -lc '
            set -eu

            ensure_container_browser_plugin_cache() {
                plugin_cache_file="'"${plugin_cache_relative_path}"'"
                browser_plugin_signature='"'"${browser_plugin_signature}"'"'

                if [ -f "${plugin_cache_file}" ] && grep -Fq "${browser_plugin_signature}" "${plugin_cache_file}"; then
                    return
                fi

                if command -v composer >/dev/null 2>&1; then
                    composer pest:dump-plugins >/dev/null 2>&1 || true
                fi

                if [ -f "${plugin_cache_file}" ] && grep -Fq "${browser_plugin_signature}" "${plugin_cache_file}"; then
                    return
                fi

                echo "Missing Pest Browser plugin cache entry. Run composer pest:dump-plugins." >&2
                exit 1
            }

            detect_cpu_cores() {
                cpu_cores="${TEST_CPU_CORES:-}"

                if [ -n "${cpu_cores}" ] && printf "%s" "${cpu_cores}" | grep -Eq "^[0-9]+$" && [ "${cpu_cores}" -gt 0 ]; then
                    printf "%s\n" "${cpu_cores}"
                    return 0
                fi

                if command -v nproc >/dev/null 2>&1; then
                    cpu_cores="$(nproc 2>/dev/null || true)"
                elif command -v getconf >/dev/null 2>&1; then
                    cpu_cores="$(getconf _NPROCESSORS_ONLN 2>/dev/null || true)"
                else
                    cpu_cores=""
                fi

                if [ -z "${cpu_cores}" ] || ! printf "%s" "${cpu_cores}" | grep -Eq "^[0-9]+$" || [ "${cpu_cores}" -lt 1 ]; then
                    cpu_cores=1
                fi

                printf "%s\n" "${cpu_cores}"
            }

            resolve_parallel_processes() {
                cpu_cores="$1"
                reserved_cores="${TEST_RESERVED_CORES:-1}"
                max_processes="${TEST_BROWSER_MAX_PROCESSES:-${TEST_MAX_PROCESSES:-4}}"

                if ! printf "%s" "${reserved_cores}" | grep -Eq "^[0-9]+$"; then
                    reserved_cores=1
                fi

                if ! printf "%s" "${max_processes}" | grep -Eq "^[0-9]+$" || [ "${max_processes}" -lt 1 ]; then
                    max_processes=4
                fi

                parallel_processes=$(( cpu_cores - reserved_cores ))

                if [ "${parallel_processes}" -lt 1 ]; then
                    parallel_processes=1
                fi

                if [ "${parallel_processes}" -gt "${max_processes}" ]; then
                    parallel_processes="${max_processes}"
                fi

                printf "%s\n" "${parallel_processes}"
            }

            cpu_cores="$(detect_cpu_cores)"
            parallel_processes="$(resolve_parallel_processes "${cpu_cores}")"
            ensure_container_browser_plugin_cache

            echo "[testing:${TESTING_SCRIPT_NAME}] mode=docker container=${TESTING_CONTAINER_NAME} cpu=${cpu_cores} processes=${parallel_processes}" >&2

            if command -v timeout >/dev/null 2>&1; then
                .scripts/testing/support/run-clean.sh timeout 5m vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${BROWSER_TESTS_PATH}" "$@"
                exit 0
            fi

            if [ -x /usr/bin/timeout ]; then
                .scripts/testing/support/run-clean.sh /usr/bin/timeout 5m vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${BROWSER_TESTS_PATH}" "$@"
                exit 0
            fi

            .scripts/testing/support/run-clean.sh vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${BROWSER_TESTS_PATH}" "$@"
        ' sh "$@"
}

container_has_playwright() {
    local container_name="$1"

    docker exec \
        -w "${container_project_root}" \
        "${container_name}" \
        sh -lc 'command -v node >/dev/null 2>&1 && node_modules/.bin/playwright --version >/dev/null 2>&1' >/dev/null 2>&1
}

if container_name="$(resolve_test_container)" && container_has_playwright "${container_name}"; then
    run_in_container "${container_name}" "$@"
    exit 0
fi

run_local "$@"
