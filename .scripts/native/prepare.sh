#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

if [[ ! -d "${root_dir}/nativephp" ]]; then
    (cd "$root_dir" && php artisan native:install --with-icu)
fi

(cd "$root_dir" && npm run build -- --mode=android)
