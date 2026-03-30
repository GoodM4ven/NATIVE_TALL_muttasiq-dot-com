#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../../../" && pwd)"
source "${root_dir}/.scripts/native/mobile/support/prepare-platform.sh"

native_prepare_platform_install \
    "android" \
    "nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt"

(
    cd "${root_dir}"
    npm run build -- --mode=android
)
