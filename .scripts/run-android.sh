#!/usr/bin/env bash
set -euo pipefail

if [[ -z "${NATIVEPHP_ANDROID_EMULATOR_ARGS:-}" && "$(uname -s)" == "Linux" ]]; then
    export NATIVEPHP_ANDROID_EMULATOR_ARGS="-gpu swiftshader_indirect"
fi

./.scripts/support/prepare.sh
./.scripts/native/mobile/android/support/prepare.sh

COMPOSER_NO_DEV=1 php artisan native:run android
