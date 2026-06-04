#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

ensure_nativephp_icu() {
    php -r '
array_shift($argv);

foreach ($argv as $path) {
    if ($path === "" || ! is_file($path)) {
        continue;
    }

    $contents = file_get_contents($path);

    if (! is_string($contents) || $contents === "") {
        continue;
    }

    $json = json_decode($contents, true);

    if (! is_array($json)) {
        continue;
    }

    $json["php"] = is_array($json["php"] ?? null) ? $json["php"] : [];

    if (! empty($json["php"]["icu"])) {
        continue;
    }

    $json["php"]["icu"] = true;
    file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    fwrite(STDOUT, "[native-run:ios] enabled ICU in {$path}\n");
}
' \
        "${project_root}/nativephp.lock" \
        "${project_root}/nativephp.json"
}

ensure_nativephp_icu

"${project_root}/.scripts/support/prepare.sh"
"${project_root}/.scripts/native/mobile/ios/support/prepare.sh"

simulator_udid="$("${project_root}/.scripts/native/mobile/ios/support/select-simulator.sh")"
echo "[native-run:ios] using simulator ${simulator_udid}"
# xcrun simctl shutdown "${simulator_udid}" >/dev/null 2>&1 || true

(
    cd "${project_root}"
    COMPOSER_NO_DEV=1 php artisan native:run ios "${simulator_udid}" --build=debug
)
