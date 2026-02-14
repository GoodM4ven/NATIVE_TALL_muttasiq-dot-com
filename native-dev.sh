#!/usr/bin/env bash
set -euo pipefail

./.scripts/prepare.sh
./.scripts/web/prepare.sh

composer dev
