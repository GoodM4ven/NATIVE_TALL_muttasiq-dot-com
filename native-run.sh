#!/usr/bin/env bash
set -euo pipefail

./.scripts/prepare.sh
./.scripts/native/prepare.sh
./.scripts/native/patches/system-ui.sh
./.scripts/native/patches/back-handler.sh

php artisan native:run android
