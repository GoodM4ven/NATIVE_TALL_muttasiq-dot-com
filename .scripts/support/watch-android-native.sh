#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

if command -v watchman >/dev/null 2>&1; then
    watchman shutdown-server || true
fi

if ! command -v watchman-wait >/dev/null 2>&1; then
    export PATH="${project_root}/.scripts/support/bin:${PATH}"
    echo "[native-watch:android] watchman-wait is unavailable; using bundled watchman shim" >&2
fi

"${project_root}/.scripts/support/prepare.sh"
"${project_root}/.scripts/native/mobile/android/support/prepare.sh"

(
    cd "${project_root}"
    php artisan native:run android --watch
)
