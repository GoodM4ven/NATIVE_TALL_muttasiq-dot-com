#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

if ! command -v watchman >/dev/null 2>&1; then
    echo "[native-watch:ios] watchman is required for NativePHP hot reloading." >&2
    echo "[native-watch:ios] install it with: brew install watchman" >&2
    exit 1
fi

watchman shutdown-server || true

if ! command -v watchman-wait >/dev/null 2>&1; then
    export PATH="${project_root}/.scripts/support/bin:${PATH}"
    echo "[native-watch:ios] watchman-wait is unavailable; using the bundled compatibility shim" >&2
fi

"${project_root}/.scripts/support/prepare.sh"
"${project_root}/.scripts/native/mobile/ios/support/prepare.sh"

simulator_udid="$("${project_root}/.scripts/native/mobile/ios/support/select-simulator.sh")"
echo "[native-watch:ios] using simulator ${simulator_udid}"
xcrun simctl shutdown "${simulator_udid}" >/dev/null 2>&1 || true

(
    cd "${project_root}"
    COMPOSER_NO_DEV=1 php artisan native:run ios "${simulator_udid}" --build=debug --watch
)
