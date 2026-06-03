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

(cd "$root_dir" && php -r '
$path = $argv[1] ?? "";
if ($path === "" || ! is_file($path)) {
    exit(0);
}

$contents = file_get_contents($path);
$updated = str_replace(
    ["        '\''*.md'\'',", "        '\''docs'\'',"],
    ["        '\''vendor/*/*/*.md'\'',", "        '\''/docs'\'',"],
    $contents,
    $count,
);

if ($count > 0) {
    file_put_contents($path, $updated);
}
' vendor/nativephp/mobile/src/Support/BundleExclusions.php)

(cd "$root_dir" && php artisan app:native-bootstrap --no-interaction)
