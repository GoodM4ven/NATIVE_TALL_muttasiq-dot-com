#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
project_root="$(cd "${script_dir}/.." && pwd)"
output_dir="${project_root}/storage/logs"
output_file="${output_dir}/native-log-android.txt"

read_env_var() {
    local key="$1"
    local env_file="${2:-${project_root}/.env}"

    if [[ ! -f "${env_file}" ]]; then
        return 1
    fi

    local line
    line="$(grep -E "^${key}=" "${env_file}" | tail -n 1 || true)"

    if [[ -z "${line}" ]]; then
        return 1
    fi

    line="${line#*=}"
    line="${line%\"}"
    line="${line#\"}"
    line="${line%\'}"
    line="${line#\'}"

    printf '%s' "${line}"
}

append_section() {
    local title="$1"
    printf '\n===== %s =====\n' "${title}" >>"${output_file}"
}

run_capture() {
    local title="$1"
    shift

    append_section "${title}"
    printf '+ %s\n' "$*" >>"${output_file}"

    if "$@" >>"${output_file}" 2>&1; then
        return 0
    fi

    local status=$?
    printf 'Command failed with exit code: %s\n' "${status}" >>"${output_file}"
}

run_capture_shell() {
    local title="$1"
    local command="$2"

    append_section "${title}"
    printf '+ %s\n' "${command}" >>"${output_file}"

    if bash -lc "${command}" >>"${output_file}" 2>&1; then
        return 0
    fi

    local status=$?
    printf 'Command failed with exit code: %s\n' "${status}" >>"${output_file}"
}

append_run_as_tail() {
    local serial="$1"
    local app_id="$2"
    local title="$3"
    local relative_path="$4"
    local lines="${5:-500}"

    append_section "${title}"

    local escaped_app_id
    escaped_app_id="$(printf '%s' "${app_id}" | sed "s/'/'\\\\''/g")"
    local escaped_relative_path
    escaped_relative_path="$(printf '%s' "${relative_path}" | sed "s/'/'\\\\''/g")"
    local command="run-as '${escaped_app_id}' sh -c 'tail -n ${lines} \"${escaped_relative_path}\" 2>/dev/null || echo \"Missing file: ${escaped_relative_path}\"'"

    printf '+ adb -s %s shell %s\n' "${serial}" "${command}" >>"${output_file}"

    if adb -s "${serial}" shell "${command}" >>"${output_file}" 2>&1; then
        return 0
    fi

    local status=$?
    printf 'Command failed with exit code: %s\n' "${status}" >>"${output_file}"
}

{
    mkdir -p "${output_dir}"
    printf 'native-log-android generated at: %s\n' "$(date -u '+%Y-%m-%d %H:%M:%S UTC')"
    printf 'working directory: %s\n' "${project_root}"
} >"${output_file}"

if ! command -v adb >/dev/null 2>&1; then
    append_section "Error"
    printf 'adb not found in PATH.\n' >>"${output_file}"
    printf 'Wrote %s\n' "${output_file}"
    exit 1
fi

serial="${1:-${ANDROID_SERIAL:-}}"
if [[ -z "${serial}" ]]; then
    serial="$(adb devices | awk 'NR > 1 && $2 == "device" { print $1; exit }')"
fi

app_id="${2:-${APP_ID:-${NATIVEPHP_APP_ID:-}}}"
if [[ -z "${app_id}" ]]; then
    app_id="$(read_env_var "NATIVEPHP_APP_ID" || true)"
fi

append_section "Detected Values"
printf 'SERIAL=%s\n' "${serial:-}" >>"${output_file}"
printf 'APP_ID=%s\n' "${app_id:-}" >>"${output_file}"

run_capture "adb version" adb version
run_capture "adb devices -l" adb devices -l

if [[ -z "${serial}" ]]; then
    append_section "Error"
    printf 'No online Android device/emulator found.\n' >>"${output_file}"
    printf 'Wrote %s\n' "${output_file}"
    exit 1
fi

run_capture "Device properties" adb -s "${serial}" shell getprop

if [[ -n "${app_id}" ]]; then
    run_capture "Package install state" adb -s "${serial}" shell pm list packages "${app_id}"
    run_capture "dumpsys package ${app_id}" adb -s "${serial}" shell dumpsys package "${app_id}"
fi

app_pid=""
if [[ -n "${app_id}" ]]; then
    app_pid="$(adb -s "${serial}" shell pidof -s "${app_id}" 2>/dev/null | tr -d '\r' || true)"
fi

append_section "Detected PID"
printf 'APP_PID=%s\n' "${app_pid:-}" >>"${output_file}"

if [[ -n "${app_pid}" ]]; then
    run_capture "logcat (pid ${app_pid})" adb -s "${serial}" logcat -d --pid="${app_pid}" -v threadtime
else
    append_section "logcat (pid scoped)"
    printf 'App PID not available. Skipping pid-scoped logcat.\n' >>"${output_file}"
fi

run_capture "logcat crash buffer" adb -s "${serial}" logcat -d -b crash -v threadtime
run_capture_shell \
    "logcat keyword filter (NativePHP/Laravel/PHP/WebView/Runtime)" \
    "adb -s '${serial}' logcat -d -v threadtime | grep -iE 'nativephp|laravel|php|webview|androidruntime|chromium' || true"

if [[ -n "${app_id}" ]] && adb -s "${serial}" shell run-as "${app_id}" true >/dev/null 2>&1; then
    run_capture_shell \
        "run-as log file discovery" \
        "adb -s '${serial}' shell run-as '${app_id}' sh -c 'find . -maxdepth 8 -type f \\( -name \"nativephp_debug.log\" -o -name \"laravel.log\" \\) 2>/dev/null'"
    append_run_as_tail "${serial}" "${app_id}" "run-as nativephp_debug.log (files/)" "files/nativephp_debug.log" 500
    append_run_as_tail "${serial}" "${app_id}" "run-as laravel.log (files/storage/logs/)" "files/storage/logs/laravel.log" 500
    append_run_as_tail "${serial}" "${app_id}" "run-as laravel.log (Documents/app/storage/logs/)" "files/Documents/app/storage/logs/laravel.log" 500
else
    append_section "run-as"
    printf 'run-as is unavailable for APP_ID=%s (app not installed, not debug build, or restricted shell).\n' "${app_id:-}" >>"${output_file}"
fi

printf 'Wrote %s\n' "${output_file}"
