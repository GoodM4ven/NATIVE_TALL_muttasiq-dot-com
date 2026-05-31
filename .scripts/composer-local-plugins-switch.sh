#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
action="toggle"
if [[ "${1:-}" == "on" || "${1:-}" == "off" || "${1:-}" == "toggle" ]]; then
    action="$1"
    shift
fi

package_name="${1:-goodm4ven/nativephp-muttasiq-patches}"
package_path="${2:-${HOME}/Code/LaravelPackages/NATIVE_PLUGIN_muttasiq-patches}"
forced_version_input="${3:-}"
repository_key="${package_name##*/}"

run_package_update() {
    if [[ "${COMPOSER_LOCAL_PLUGINS_SWITCH_SKIP_UPDATE:-0}" == "1" ]]; then
        echo "[composer-local-plugins-switch] skipped composer update via COMPOSER_LOCAL_PLUGINS_SWITCH_SKIP_UPDATE=1"
        return 0
    fi

    composer update "${package_name}" --with-dependencies
}

detect_local_forced_version() {
    local constraint="$1"

    if [[ "${constraint}" =~ ([0-9]+)\.([0-9]+)\.([0-9]+) ]]; then
        echo "${BASH_REMATCH[1]}.${BASH_REMATCH[2]}.999999"
        return 0
    fi

    if [[ "${constraint}" =~ ([0-9]+)\.([0-9]+) ]]; then
        echo "${BASH_REMATCH[1]}.${BASH_REMATCH[2]}.999999"
        return 0
    fi

    if [[ "${constraint}" =~ ([0-9]+) ]]; then
        echo "${BASH_REMATCH[1]}.999999.999999"
        return 0
    fi

    echo "1.0.999999"
}

has_matching_repositories() {
    php -r '
        $composer = json_decode(file_get_contents("composer.json"), true);
        $repositories = $composer["repositories"] ?? [];
        $packageName = $argv[1];
        $repositoryKey = $argv[2];
        $targetDirectoryName = $argv[3];

        foreach ($repositories as $repository) {
            if (! is_array($repository)) {
                continue;
            }

            if (($repository["type"] ?? null) !== "path") {
                continue;
            }

            $versions = $repository["options"]["versions"] ?? [];
            $url = (string) ($repository["url"] ?? "");
            $urlDirectoryName = $url !== "" ? basename(rtrim($url, "/\\")) : "";

            $matchesByName = ($repository["name"] ?? null) === $repositoryKey;
            $matchesByVersion = is_array($versions) && array_key_exists($packageName, $versions);
            $matchesByDirectory = $urlDirectoryName !== "" && $urlDirectoryName === $targetDirectoryName;

            if ($matchesByName || $matchesByVersion || $matchesByDirectory) {
                echo "1";
                break;
            }
        }
    ' "${package_name}" "${repository_key}" "$(basename "${package_path}")"
}

remove_matching_repositories() {
    php -r '
        $composerPath = "composer.json";
        $composer = json_decode(file_get_contents($composerPath), true);
        $repositories = $composer["repositories"] ?? [];
        $packageName = $argv[1];
        $repositoryKey = $argv[2];
        $targetDirectoryName = $argv[3];

        $composer["repositories"] = array_values(array_filter(
            $repositories,
            function (mixed $repository) use ($packageName, $repositoryKey, $targetDirectoryName): bool {
                if (! is_array($repository)) {
                    return true;
                }

                if (($repository["type"] ?? null) !== "path") {
                    return true;
                }

                $versions = $repository["options"]["versions"] ?? [];
                $url = (string) ($repository["url"] ?? "");
                $urlDirectoryName = $url !== "" ? basename(rtrim($url, "/\\")) : "";

                $matchesByName = ($repository["name"] ?? null) === $repositoryKey;
                $matchesByVersion = is_array($versions) && array_key_exists($packageName, $versions);
                $matchesByDirectory = $urlDirectoryName !== "" && $urlDirectoryName === $targetDirectoryName;

                return ! ($matchesByName || $matchesByVersion || $matchesByDirectory);
            }
        ));

        file_put_contents(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );
    ' "${package_name}" "${repository_key}" "$(basename "${package_path}")"
}

enable_local_repository() {
    if [[ ! -d "${package_path}" ]]; then
        echo "[composer-local-plugins-switch] missing package path: ${package_path}" >&2
        exit 1
    fi

    composer config "repositories.${repository_key}" --json "$(cat <<JSON
{
  "type": "path",
  "url": "${package_path}",
  "options": {
    "symlink": true,
    "versions": {
      "${package_name}": "${local_forced_version}"
    }
  }
}
JSON
)"
}

cd "${root_dir}"

package_constraint="$(php -r '$composer = json_decode(file_get_contents("composer.json"), true); $pkg = $argv[1]; echo $composer["require"][$pkg] ?? $composer["require-dev"][$pkg] ?? "";' "${package_name}")"
local_forced_version="${forced_version_input:-$(detect_local_forced_version "${package_constraint}")}"
has_matching_repository="$(has_matching_repositories)"

if [[ "${action}" == "off" ]]; then
    if [[ -z "${has_matching_repository}" ]]; then
        echo "[composer-local-plugins-switch] already disabled for ${package_name}"
        exit 0
    fi

    remove_matching_repositories
    run_package_update
    echo "[composer-local-plugins-switch] disabled local path repository for ${package_name}"
    exit 0
fi

if [[ "${action}" == "toggle" && -n "${has_matching_repository}" ]]; then
    remove_matching_repositories
    run_package_update
    echo "[composer-local-plugins-switch] disabled local path repository for ${package_name}"
    exit 0
fi

remove_matching_repositories
enable_local_repository
run_package_update
echo "[composer-local-plugins-switch] enabled local path repository for ${package_name}"
