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
playwright_runtime_state_rel="vendor/pestphp/pest-plugin-browser/.temp/playwright-runtime.version"
browser_timeout_raw="${BROWSER_TEST_TIMEOUT:-10m}"
keep_vite_hot_in_tests="${KEEP_VITE_HOT_IN_TESTS:-0}"
browser_stop_on_failure="${BROWSER_TEST_STOP_ON_FAILURE:-1}"

browser_timeout="${browser_timeout_raw}"
if [[ "${browser_timeout_raw}" =~ ^([0-9]+)m$ ]]; then
    if (( BASH_REMATCH[1] < 10 )); then
        browser_timeout="10m"
        echo "[testing:${script_name}] BROWSER_TEST_TIMEOUT=${browser_timeout_raw} too low; using ${browser_timeout}" >&2
    fi
fi

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

has_stop_option() {
    local arg

    for arg in "$@"; do
        case "${arg}" in
        --stop-on-* | --bail)
            return 0
            ;;
        esac
    done

    return 1
}

compose_browser_pest_args() {
    local -n output_ref="$1"
    shift

    output_ref=("$@")

    if [[ "${browser_stop_on_failure}" != "1" ]]; then
        return
    fi

    if has_stop_option "${output_ref[@]}"; then
        return
    fi

    output_ref=("${output_ref[@]}")
}

run_compact_command() {
    local -a browser_pest_args
    compose_browser_pest_args browser_pest_args "$@"

    if command -v timeout >/dev/null 2>&1; then
        "${run_clean_script}" timeout "${browser_timeout}" vendor/bin/pest --compact "${browser_tests_path}" "${browser_pest_args[@]}"

        return
    fi

    if [[ -x /usr/bin/timeout ]]; then
        "${run_clean_script}" /usr/bin/timeout "${browser_timeout}" vendor/bin/pest --compact "${browser_tests_path}" "${browser_pest_args[@]}"

        return
    fi

    "${run_clean_script}" vendor/bin/pest --compact "${browser_tests_path}" "${browser_pest_args[@]}"
}

run_parallel_command() {
    local parallel_processes="$1"
    shift
    local -a browser_pest_args
    compose_browser_pest_args browser_pest_args "$@"

    if command -v timeout >/dev/null 2>&1; then
        "${run_clean_script}" timeout "${browser_timeout}" vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${browser_tests_path}" "${browser_pest_args[@]}"

        return
    fi

    if [[ -x /usr/bin/timeout ]]; then
        "${run_clean_script}" /usr/bin/timeout "${browser_timeout}" vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${browser_tests_path}" "${browser_pest_args[@]}"

        return
    fi

    "${run_clean_script}" vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${browser_tests_path}" "${browser_pest_args[@]}"
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

ensure_local_playwright_runtime() {
    if [[ "${SKIP_PLAYWRIGHT_INSTALL_PREFLIGHT:-0}" == "1" ]]; then
        return
    fi

    if ! command -v node >/dev/null 2>&1; then
        echo "Node is required for browser tests." >&2
        exit 1
    fi

    local playwright_bin="${root_dir}/node_modules/.bin/playwright"

    if [[ ! -x "${playwright_bin}" ]]; then
        echo "Missing Playwright CLI at ${playwright_bin}. Run npm install." >&2
        exit 1
    fi

    local runtime_version
    runtime_version="$(
        cd "${root_dir}" && node -p "require('./node_modules/playwright/package.json').version" 2>/dev/null || true
    )"

    if [[ -z "${runtime_version}" ]]; then
        echo "Unable to resolve installed Playwright runtime version." >&2
        exit 1
    fi

    local state_file="${root_dir}/${playwright_runtime_state_rel}"
    local state_dir
    state_dir="$(dirname "${state_file}")"
    mkdir -p "${state_dir}"

    local previous_version=""
    if [[ -f "${state_file}" ]]; then
        previous_version="$(<"${state_file}")"
    fi

    local missing_locations=0
    local location
    while IFS= read -r location; do
        location="$(printf '%s' "${location}" | xargs)"

        if [[ -z "${location}" ]]; then
            continue
        fi

        if [[ ! -d "${location}" ]]; then
            missing_locations=1
            break
        fi
    done < <(
        cd "${root_dir}" && "${playwright_bin}" install --dry-run 2>/dev/null | awk -F': ' '/Install location:/{print $2}'
    )

    if [[ "${missing_locations}" -eq 0 && "${previous_version}" == "${runtime_version}" ]]; then
        return
    fi

    echo "[testing:${script_name}] syncing Playwright runtime (version ${runtime_version})" >&2
    (
        cd "${root_dir}"
        "${playwright_bin}" install >/dev/null
    )
    printf '%s\n' "${runtime_version}" > "${state_file}"
}

disable_local_vite_hot_if_possible() {
    if [[ "${keep_vite_hot_in_tests}" == "1" ]]; then
        return
    fi

    local hot_file="${root_dir}/public/hot"
    local manifest_file="${root_dir}/public/build/manifest.json"
    local backup_file="${hot_file}.browser-tests.bak"

    if [[ ! -f "${hot_file}" || ! -f "${manifest_file}" ]]; then
        return
    fi

    mv "${hot_file}" "${backup_file}"
    trap 'if [[ -f "'"${backup_file}"'" ]]; then mv "'"${backup_file}"'" "'"${hot_file}"'"; fi' EXIT

    echo "[testing:${script_name}] temporarily disabled public/hot (using built Vite assets)" >&2
}

run_local() (
    cd "${root_dir}"
    ensure_local_browser_plugin_cache
    ensure_local_playwright_runtime
    disable_local_vite_hot_if_possible
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
    local -a browser_pest_args
    compose_browser_pest_args browser_pest_args "$@"

    docker exec \
        -e "BROWSER_TESTS_PATH=${browser_tests_path}" \
        -e "XDEBUG_MODE=${xdebug_mode}" \
        -e "TESTING_SCRIPT_NAME=${script_name}" \
        -e "TESTING_CONTAINER_NAME=${container_name}" \
        -e "TEST_RESERVED_CORES=${TEST_RESERVED_CORES:-1}" \
        -e "TEST_MAX_PROCESSES=${TEST_MAX_PROCESSES:-8}" \
        -e "TEST_BROWSER_MAX_PROCESSES=${TEST_BROWSER_MAX_PROCESSES:-}" \
        -e "TEST_CPU_CORES=${TEST_CPU_CORES:-}" \
        -e "SKIP_PLAYWRIGHT_INSTALL_PREFLIGHT=${SKIP_PLAYWRIGHT_INSTALL_PREFLIGHT:-0}" \
        -e "BROWSER_TEST_TIMEOUT=${browser_timeout}" \
        -e "KEEP_VITE_HOT_IN_TESTS=${keep_vite_hot_in_tests}" \
        -w "${container_project_root}" \
        "${container_name}" \
        sh -lc '
            set -eu
            browser_timeout="${BROWSER_TEST_TIMEOUT:-10m}"
            hot_file=""
            hot_backup=""

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

            ensure_container_playwright_runtime() {
                if [ "${SKIP_PLAYWRIGHT_INSTALL_PREFLIGHT:-0}" = "1" ]; then
                    return
                fi

                if ! command -v node >/dev/null 2>&1; then
                    echo "Node is required for browser tests." >&2
                    exit 1
                fi

                playwright_bin="./node_modules/.bin/playwright"

                if [ ! -x "${playwright_bin}" ]; then
                    echo "Missing Playwright CLI at ${playwright_bin}. Run npm install." >&2
                    exit 1
                fi

                runtime_version="$(node -p "require('./node_modules/playwright/package.json').version" 2>/dev/null || true)"

                if [ -z "${runtime_version}" ]; then
                    echo "Unable to resolve installed Playwright runtime version." >&2
                    exit 1
                fi

                state_file="'"${playwright_runtime_state_rel}"'"
                state_dir="$(dirname "${state_file}")"
                mkdir -p "${state_dir}"

                previous_version=""
                if [ -f "${state_file}" ]; then
                    previous_version="$(cat "${state_file}")"
                fi

                missing_locations=0
                while IFS= read -r location; do
                    location="$(printf "%s" "${location}" | xargs)"

                    if [ -z "${location}" ]; then
                        continue
                    fi

                    if [ ! -d "${location}" ]; then
                        missing_locations=1
                        break
                    fi
                done <<EOF
$(./node_modules/.bin/playwright install --dry-run 2>/dev/null | awk -F": " "/Install location:/{print \$2}")
EOF

                if [ "${missing_locations}" -eq 0 ] && [ "${previous_version}" = "${runtime_version}" ]; then
                    return
                fi

                echo "[testing:${TESTING_SCRIPT_NAME}] syncing Playwright runtime (version ${runtime_version})" >&2
                "${playwright_bin}" install >/dev/null
                printf "%s\n" "${runtime_version}" > "${state_file}"
            }

            disable_container_vite_hot_if_possible() {
                if [ "${KEEP_VITE_HOT_IN_TESTS:-0}" = "1" ]; then
                    return
                fi

                hot_file="./public/hot"
                manifest_file="./public/build/manifest.json"
                hot_backup="${hot_file}.browser-tests.bak"

                if [ ! -f "${hot_file}" ] || [ ! -f "${manifest_file}" ]; then
                    hot_file=""
                    hot_backup=""
                    return
                fi

                mv "${hot_file}" "${hot_backup}"
                trap '\''if [ -n "${hot_file}" ] && [ -n "${hot_backup}" ] && [ -f "${hot_backup}" ]; then mv "${hot_backup}" "${hot_file}"; fi'\'' EXIT INT TERM

                echo "[testing:${TESTING_SCRIPT_NAME}] temporarily disabled public/hot (using built Vite assets)" >&2
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
            ensure_container_playwright_runtime
            disable_container_vite_hot_if_possible

            echo "[testing:${TESTING_SCRIPT_NAME}] mode=docker container=${TESTING_CONTAINER_NAME} cpu=${cpu_cores} processes=${parallel_processes}" >&2

            if command -v timeout >/dev/null 2>&1; then
                .scripts/testing/support/run-clean.sh timeout "${browser_timeout}" vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${BROWSER_TESTS_PATH}" "$@"
                exit 0
            fi

            if [ -x /usr/bin/timeout ]; then
                .scripts/testing/support/run-clean.sh /usr/bin/timeout "${browser_timeout}" vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${BROWSER_TESTS_PATH}" "$@"
                exit 0
            fi

            .scripts/testing/support/run-clean.sh vendor/bin/pest --compact --parallel --processes="${parallel_processes}" "${BROWSER_TESTS_PATH}" "$@"
        ' sh "${browser_pest_args[@]}"
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
