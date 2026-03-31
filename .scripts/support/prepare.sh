#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

if [[ ! -d "${root_dir}/node_modules" ]]; then
    (cd "$root_dir" && npm install)
fi

if [[ ! -d "${root_dir}/vendor" ]]; then
    (cd "$root_dir" && composer install)
fi

if [[ "${NATIVE_PREPARE_DUMP_AUTOLOAD:-0}" == "1" ]]; then
    (cd "$root_dir" && composer dump-autoload)
fi

if [[ "${NATIVE_PREPARE_CLEAR_CACHE:-0}" == "1" ]]; then
    (cd "$root_dir" && php artisan optimize:clear)
fi

(cd "$root_dir" && php artisan migrate --force --no-interaction)
