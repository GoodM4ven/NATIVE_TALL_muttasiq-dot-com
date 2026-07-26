#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

if ! command -v watchman >/dev/null 2>&1; then
    echo "[native-watch:android] watchman is required for NativePHP hot reloading." >&2
    echo "[native-watch:android] install it with: brew install watchman" >&2
    exit 1
fi

watchman shutdown-server || true

if ! command -v watchman-wait >/dev/null 2>&1; then
    export PATH="${project_root}/.scripts/support/bin:${PATH}"
    echo "[native-watch:android] watchman-wait is unavailable; using the bundled compatibility shim" >&2
fi

"${project_root}/.scripts/support/prepare.sh"
"${project_root}/.scripts/native/mobile/android/support/prepare.sh"

android_target="$("${project_root}/.scripts/native/mobile/android/support/select-emulator.sh")"
echo "[native-watch:android] using target ${android_target}"

(
    cd "${project_root}"
    ANDROID_SERIAL="${android_target}" \
        COMPOSER_NO_DEV=1 \
        php artisan native:run android "${android_target}" --build=debug --watch
)
