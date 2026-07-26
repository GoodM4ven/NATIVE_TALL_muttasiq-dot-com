#!/usr/bin/env bash

native_configure_ios_toolchain() {
    if [[ "$(uname -s)" != "Darwin" ]]; then
        echo "[native-ios-toolchain] iOS development requires macOS (Darwin)." >&2
        return 1
    fi

    if ! command -v xcrun >/dev/null 2>&1; then
        echo "[native-ios-toolchain] xcrun was not found. Install Xcode and its command line tools." >&2
        return 1
    fi

    if xcrun --find simctl >/dev/null 2>&1; then
        return 0
    fi

    local active_developer_dir=""
    local candidate_developer_dir=""
    local -a candidate_developer_dirs=(
        "/Applications/Xcode.app/Contents/Developer"
        "${HOME}/Applications/Xcode.app/Contents/Developer"
    )

    active_developer_dir="$(xcode-select -p 2>/dev/null || true)"

    for candidate_developer_dir in "${candidate_developer_dirs[@]}"; do
        if [[ ! -d "${candidate_developer_dir}" ]]; then
            continue
        fi

        if DEVELOPER_DIR="${candidate_developer_dir}" xcrun --find simctl >/dev/null 2>&1; then
            export DEVELOPER_DIR="${candidate_developer_dir}"
            echo "[native-ios-toolchain] ${active_developer_dir:-the active developer directory} does not provide simctl; using ${DEVELOPER_DIR} for this run." >&2
            echo "[native-ios-toolchain] to make this selection system-wide, run: sudo xcode-select --switch \"${DEVELOPER_DIR}\"" >&2
            return 0
        fi
    done

    echo "[native-ios-toolchain] xcrun cannot find simctl." >&2
    echo "[native-ios-toolchain] install Xcode 16 or later, open it once to finish setup, then select it with xcode-select." >&2
    return 1
}

native_configure_ios_toolchain
unset -f native_configure_ios_toolchain
