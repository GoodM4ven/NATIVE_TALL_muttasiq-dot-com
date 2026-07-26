#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

source "${project_root}/.scripts/native/mobile/ios/support/configure-xcode.sh"

"${project_root}/.scripts/support/run-native-local-source-broadcast.sh" ios watch
