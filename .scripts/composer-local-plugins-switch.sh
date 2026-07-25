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

matching_repository_url() {
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
                echo $url;
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
    # A path repository whose URL this machine cannot resolve is not a working "on"
    # state — for example one carried over from another OS, where Composer leaves a
    # dangling symlink in vendor/ and the app fails to boot. Re-point it at the local
    # path instead of disabling it, which is what "toggle" would otherwise do and is
    # the opposite of what someone re-running this script on a new machine wants.
    matched_repository_url="$(matching_repository_url)"

    if [[ -n "${matched_repository_url}" && ! -d "${matched_repository_url}" ]]; then
        echo "[composer-local-plugins-switch] re-pointing unresolvable path repository for ${package_name}: ${matched_repository_url} -> ${package_path}"
    else
        remove_matching_repositories
        run_package_update
        echo "[composer-local-plugins-switch] disabled local path repository for ${package_name}"
        exit 0
    fi
fi

remove_matching_repositories
enable_local_repository
run_package_update
echo "[composer-local-plugins-switch] enabled local path repository for ${package_name}"
