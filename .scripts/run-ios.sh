#!/usr/bin/env bash
set -euo pipefail

./.scripts/support/prepare.sh
./.scripts/native/mobile/ios/support/prepare.sh

simulator_udid="$("./.scripts/native/mobile/ios/support/select-simulator.sh")"
echo "[native-run:ios] using simulator ${simulator_udid}"
# xcrun simctl shutdown "${simulator_udid}" >/dev/null 2>&1 || true

COMPOSER_NO_DEV=1 php artisan native:run ios "${simulator_udid}" --build=debug
