#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

"${project_root}/.scripts/support/prepare.sh"
"${project_root}/.scripts/native/mobile/ios/support/prepare.sh"

simulator_udid="$("${project_root}/.scripts/native/mobile/ios/support/select-simulator.sh")"
echo "[native-run:ios] using simulator ${simulator_udid}"
# xcrun simctl shutdown "${simulator_udid}" >/dev/null 2>&1 || true

(
    cd "${project_root}"
    COMPOSER_NO_DEV=1 php artisan native:run ios "${simulator_udid}" --build=debug
)
