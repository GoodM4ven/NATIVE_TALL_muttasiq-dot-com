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

child_pid=""
collect_descendant_pids() {
    local pid="$1"
    local children=()

    mapfile -t children < <(pgrep -P "${pid}" 2>/dev/null || true)

    if [[ "${#children[@]}" -eq 0 ]]; then
        return
    fi

    local child
    for child in "${children[@]}"; do
        collect_descendant_pids "${child}"
        echo "${child}"
    done
}

kill_process_tree() {
    local root_pid="$1"
    local descendants=()
    local targets=()

    mapfile -t descendants < <(collect_descendant_pids "${root_pid}" || true)

    if [[ "${#descendants[@]}" -gt 0 ]]; then
        targets=("${descendants[@]}" "${root_pid}")
    else
        targets=("${root_pid}")
    fi

    kill -TERM "${targets[@]}" >/dev/null 2>&1 || true
    sleep 0.4

    local survivors=()
    mapfile -t survivors < <(ps -o pid= -p "${targets[@]}" 2>/dev/null | awk '{print $1}' || true)

    if [[ "${#survivors[@]}" -gt 0 ]]; then
        kill -KILL "${survivors[@]}" >/dev/null 2>&1 || true
    fi
}

cleanup() {
    if [[ -n "${child_pid}" ]] && kill -0 "${child_pid}" >/dev/null 2>&1; then
        kill_process_tree "${child_pid}"
    fi

    "${preflight_script}" || true
}

"${preflight_script}"
trap cleanup EXIT INT TERM

cd "${root_dir}"

"$@" &

child_pid="$!"

wait "${child_pid}"
