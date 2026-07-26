#!/usr/bin/env bash
set -euo pipefail

preferred_avd_name="${NATIVE_ANDROID_AVD_NAME:-Muttasiq_Pixel_9_Pro_API_36}"
emulator_log_file="${TMPDIR:-/tmp}/native-android-emulator-${preferred_avd_name}.log"

resolve_emulator_binary() {
    if command -v emulator >/dev/null 2>&1; then
        command -v emulator
        return 0
    fi

    if [[ -n "${ANDROID_HOME:-}" && -x "${ANDROID_HOME}/emulator/emulator" ]]; then
        printf '%s' "${ANDROID_HOME}/emulator/emulator"
        return 0
    fi

    if [[ -n "${ANDROID_SDK_ROOT:-}" && -x "${ANDROID_SDK_ROOT}/emulator/emulator" ]]; then
        printf '%s' "${ANDROID_SDK_ROOT}/emulator/emulator"
        return 0
    fi

    return 1
}

find_running_emulator_serial() {
    local avd_name="$1"
    local running_avd_name=""
    local serial=""

    while IFS= read -r serial; do
        running_avd_name="$(adb -s "${serial}" emu avd name 2>/dev/null | head -n 1 | tr -d '\r')"

        if [[ "${running_avd_name}" == "${avd_name}" ]]; then
            printf '%s' "${serial}"
            return 0
        fi
    done < <(adb devices | awk 'NR > 1 && $1 ~ /^emulator-/ && $2 == "device" { print $1 }')

    return 1
}

if ! command -v adb >/dev/null 2>&1; then
    echo "[native-android-emulator] adb was not found. Install Android SDK Platform-Tools." >&2
    exit 1
fi

adb start-server >/dev/null 2>&1

physical_device_serial="$(adb devices | awk 'NR > 1 && $1 !~ /^emulator-/ && $2 == "device" { print $1 }' | LC_ALL=C sort | head -n 1)"

if [[ -n "${physical_device_serial}" ]]; then
    echo "[native-android-emulator] using connected Android device ${physical_device_serial}" >&2
    printf '%s' "${physical_device_serial}"
    exit 0
fi

if running_emulator_serial="$(find_running_emulator_serial "${preferred_avd_name}" || true)" && [[ -n "${running_emulator_serial}" ]]; then
    echo "[native-android-emulator] using running ${preferred_avd_name} (${running_emulator_serial})" >&2
    printf '%s' "${running_emulator_serial}"
    exit 0
fi

emulator_binary="$(resolve_emulator_binary || true)"

if [[ -z "${emulator_binary}" ]]; then
    echo "[native-android-emulator] Android Emulator was not found." >&2
    exit 1
fi

if ! "${emulator_binary}" -list-avds 2>/dev/null | grep -Fxq "${preferred_avd_name}"; then
    echo "[native-android-emulator] required AVD ${preferred_avd_name} is not configured." >&2
    echo "[native-android-emulator] create it in Android Studio's Device Manager or override NATIVE_ANDROID_AVD_NAME." >&2
    exit 1
fi

emulator_arguments=()
if [[ -n "${NATIVEPHP_ANDROID_EMULATOR_ARGS:-}" ]]; then
    read -r -a emulator_arguments <<<"${NATIVEPHP_ANDROID_EMULATOR_ARGS}"
fi

echo "[native-android-emulator] starting ${preferred_avd_name}; log: ${emulator_log_file}" >&2
nohup "${emulator_binary}" -avd "${preferred_avd_name}" "${emulator_arguments[@]}" >"${emulator_log_file}" 2>&1 &
emulator_pid="$!"

for _ in {1..240}; do
    running_emulator_serial="$(find_running_emulator_serial "${preferred_avd_name}" || true)"

    if [[ -n "${running_emulator_serial}" ]] \
        && [[ "$(adb -s "${running_emulator_serial}" shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')" == "1" ]]; then
        echo "[native-android-emulator] ${preferred_avd_name} is ready (${running_emulator_serial})" >&2
        printf '%s' "${running_emulator_serial}"
        exit 0
    fi

    if ! kill -0 "${emulator_pid}" >/dev/null 2>&1; then
        echo "[native-android-emulator] ${preferred_avd_name} exited before completing startup. See ${emulator_log_file}." >&2
        exit 1
    fi

    sleep 0.5
done

echo "[native-android-emulator] timed out waiting for ${preferred_avd_name}. See ${emulator_log_file}." >&2
exit 1
