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

simulator_udid="$(
    xcrun simctl list devices --json | php -r '
    require $argv[1];

    $selector = new App\Services\Native\IosSimulatorSelector();
    $simulatorUdid = $selector->selectUdidFromJson(stream_get_contents(STDIN));

    if ($simulatorUdid === null || $simulatorUdid === "") {
        exit(1);
    }

    echo $simulatorUdid;
    ' "${project_root}/vendor/autoload.php"
)"

if [[ -z "${simulator_udid}" ]]; then
    echo "[native-ios-sim] no booted simulator or available iPhone simulator was found." >&2
    exit 1
fi

echo "${simulator_udid}"
