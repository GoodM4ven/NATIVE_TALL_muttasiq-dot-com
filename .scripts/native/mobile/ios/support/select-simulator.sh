#!/usr/bin/env bash
set -euo pipefail

if [[ "$(uname -s)" != "Darwin" ]]; then
    echo "[native-ios-sim] simulator selection requires macOS (Darwin)." >&2
    exit 1
fi

if ! command -v xcrun >/dev/null 2>&1; then
    echo "[native-ios-sim] xcrun was not found. Install Xcode command line tools." >&2
    exit 1
fi

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
project_root="$(cd "${script_dir}/../../../../.." && pwd)"
preferred_iphone_name="${NATIVE_IOS_SIMULATOR_NAME:-iPhone 17 Pro}"

simulator_udid="$(
    # shellcheck disable=SC2016
    xcrun simctl list devices --json | php -r '
    require $argv[1];

    $selector = new App\Services\Native\IosSimulatorSelector();
    $simulatorUdid = $selector->selectUdidFromJson(stream_get_contents(STDIN), $argv[2]);

    if ($simulatorUdid === null || $simulatorUdid === "") {
        exit(1);
    }

    echo $simulatorUdid;
    ' "${project_root}/vendor/autoload.php" "${preferred_iphone_name}"
)"

if [[ -z "${simulator_udid}" ]]; then
    echo "[native-ios-sim] required simulator ${preferred_iphone_name} was not found." >&2
    echo "[native-ios-sim] install it in Xcode or override NATIVE_IOS_SIMULATOR_NAME." >&2
    exit 1
fi

echo "${simulator_udid}"
