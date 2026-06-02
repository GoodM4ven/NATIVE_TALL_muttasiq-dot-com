#!/usr/bin/env bash
set -euo pipefail

./.scripts/support/prepare.sh
./.scripts/native/mobile/android/support/prepare.sh

COMPOSER_NO_DEV=1 php artisan native:run android
