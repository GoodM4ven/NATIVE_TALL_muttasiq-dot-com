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
    local cpu_cores="$2"
    local parallel_processes="$3"
    local container_name="${4:-}"

    if [[ "${mode}" == "docker" ]]; then
        echo "[testing:${script_name}] mode=docker container=${container_name} cpu=${cpu_cores} processes=${parallel_processes}" >&2

        return
    fi

    echo "[testing:${script_name}] mode=local cpu=${cpu_cores} processes=${parallel_processes}" >&2
}

detect_cpu_cores() {
    local cpu_cores="${TEST_CPU_CORES:-}"

    if [[ "${cpu_cores}" =~ ^[0-9]+$ ]] && (( cpu_cores > 0 )); then
        printf '%s\n' "${cpu_cores}"

        return 0
    fi

    if command -v nproc >/dev/null 2>&1; then
        cpu_cores="$(nproc 2>/dev/null || true)"
    elif command -v getconf >/dev/null 2>&1; then
        cpu_cores="$(getconf _NPROCESSORS_ONLN 2>/dev/null || true)"
    else
        cpu_cores=""
    fi

    if ! [[ "${cpu_cores}" =~ ^[0-9]+$ ]] || (( cpu_cores < 1 )); then
        cpu_cores=1
    fi

    printf '%s\n' "${cpu_cores}"
}

resolve_parallel_processes() {
    local cpu_cores="$1"
    local reserved_cores="${TEST_RESERVED_CORES:-1}"
    local max_processes="${TEST_MAX_PROCESSES:-8}"
    local parallel_processes

    if ! [[ "${reserved_cores}" =~ ^[0-9]+$ ]]; then
        reserved_cores=1
    fi

    parallel_processes=$(( cpu_cores - reserved_cores ))

    if (( parallel_processes < 1 )); then
        parallel_processes=1
    fi

    if ! [[ "${max_processes}" =~ ^[0-9]+$ ]] || (( max_processes < 1 )); then
        max_processes=8
    fi

    if (( parallel_processes > max_processes )); then
        parallel_processes="${max_processes}"
    fi

    printf '%s\n' "${parallel_processes}"
}

run_local() (
    set -euo pipefail
    cd "${root_dir}"
    export XDEBUG_MODE="${xdebug_mode}"

    local cpu_cores
    local parallel_processes
    cpu_cores="$(detect_cpu_cores)"
    parallel_processes="$(resolve_parallel_processes "${cpu_cores}")"
    print_runtime_indicator "local" "${cpu_cores}" "${parallel_processes}"

    plugin_cache_file="${plugin_cache_relative_path}"
    backup_file=""

    if [[ -f "${plugin_cache_file}" ]]; then
        backup_file="${plugin_cache_file}.testel.bak.$$"
        cp "${plugin_cache_file}" "${backup_file}"
        trap 'if [[ -n "${backup_file}" && -f "${backup_file}" ]]; then mv "${backup_file}" "${plugin_cache_file}"; fi' EXIT INT TERM
        sed -i.bak '/"Pest\\\\Browser\\\\Plugin"/d' "${plugin_cache_file}"
        rm -f "${plugin_cache_file}.bak"
    fi

    PEST_ENABLE_BROWSER_PLUGIN=0 "${run_clean_script}" vendor/bin/pest --parallel --processes="${parallel_processes}" --exclude-group=browser "$@"
)

run_in_container() {
    local container_name="$1"
    shift

    docker exec \
        -e PEST_ENABLE_BROWSER_PLUGIN=0 \
        -e "XDEBUG_MODE=${xdebug_mode}" \
        -e "TESTING_SCRIPT_NAME=${script_name}" \
        -e "TESTING_CONTAINER_NAME=${container_name}" \
        -e "TEST_RESERVED_CORES=${TEST_RESERVED_CORES:-1}" \
        -e "TEST_MAX_PROCESSES=${TEST_MAX_PROCESSES:-8}" \
        -e "TEST_CPU_CORES=${TEST_CPU_CORES:-}" \
        -w "${container_project_root}" \
        "${container_name}" \
        sh -lc '
            set -eu

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
                max_processes="${TEST_MAX_PROCESSES:-8}"

                if ! printf "%s" "${reserved_cores}" | grep -Eq "^[0-9]+$"; then
                    reserved_cores=1
                fi

                if ! printf "%s" "${max_processes}" | grep -Eq "^[0-9]+$" || [ "${max_processes}" -lt 1 ]; then
                    max_processes=8
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

            plugin_cache_file="'"${plugin_cache_relative_path}"'"
            backup_file=""
            cpu_cores="$(detect_cpu_cores)"
            parallel_processes="$(resolve_parallel_processes "${cpu_cores}")"

            if [ -f "${plugin_cache_file}" ]; then
                backup_file="${plugin_cache_file}.testel.bak.$$"
                cp "${plugin_cache_file}" "${backup_file}"
                trap '"'"'if [ -n "${backup_file}" ] && [ -f "${backup_file}" ]; then mv "${backup_file}" "${plugin_cache_file}"; fi'"'"' EXIT INT TERM
                sed -i.bak '"'"'/"Pest\\\\Browser\\\\Plugin"/d'"'"' "${plugin_cache_file}"
                rm -f "${plugin_cache_file}.bak"
            fi

            echo "[testing:${TESTING_SCRIPT_NAME}] mode=docker container=${TESTING_CONTAINER_NAME} cpu=${cpu_cores} processes=${parallel_processes}" >&2

            PEST_ENABLE_BROWSER_PLUGIN=0 .scripts/testing/support/run-clean.sh vendor/bin/pest --parallel --processes="${parallel_processes}" --exclude-group=browser "$@"
        ' sh "$@"
}

if container_name="$(resolve_test_container)"; then
    run_in_container "${container_name}" "$@"
    exit 0
fi

run_local "$@"
