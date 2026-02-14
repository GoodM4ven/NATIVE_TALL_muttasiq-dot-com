#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ ! -d "${root_dir}/node_modules" ]]; then
    (cd "$root_dir" && npm install)
fi

if [[ ! -d "${root_dir}/vendor" ]]; then
    (cd "$root_dir" && composer install)
fi

(cd "$root_dir" && composer dump-autoload)

(cd "$root_dir" && php artisan optimize:clear)
(cd "$root_dir" && php artisan migrate)
