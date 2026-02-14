#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
preflight_script="${root_dir}/.scripts/test-preflight.sh"

if [[ ! -x "${preflight_script}" ]]; then
    echo "Missing executable preflight script at ${preflight_script}" >&2
    exit 1
fi

if [[ "$#" -eq 0 ]]; then
    echo "Usage: .scripts/run-tests-clean.sh <command> [args...]" >&2
    exit 64
fi

cleanup() {
    "${preflight_script}" || true
}

"${preflight_script}"
trap cleanup EXIT INT TERM

cd "${root_dir}"
"$@"
