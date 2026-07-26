#!/usr/bin/env bash

set -euo pipefail

FILE=".env"

if [[ ! -f "$FILE" ]]; then
    echo "Missing $FILE" >&2
    exit 1
fi

sed_in_place() {
    if [[ "$OSTYPE" == darwin* ]]; then
        sed -i '' "$1" "$FILE"
    else
        sed -i "$1" "$FILE"
    fi
}

# Determine current mode
app_name=$(grep -E '^APP_NAME=' "$FILE" || true)

if [ -n "$app_name" ] && [[ "$app_name" == *'تطوير'* ]]; then
    current_mode="development"
else
    current_mode="production"
fi

if [ "$current_mode" = "development" ]; then
    sed_in_place 's/^APP_NAME="تطوير متسق"$/APP_NAME="متسق"/'
    sed_in_place 's/^NATIVEPHP_APP_ID="localdev.goodm4ven.muttasiq"$/NATIVEPHP_APP_ID="dev.goodm4ven.muttasiq"/'

    echo "Switched to production mode."
else
    sed_in_place 's/^APP_NAME="متسق"$/APP_NAME="تطوير متسق"/'
    sed_in_place 's/^NATIVEPHP_APP_ID="dev.goodm4ven.muttasiq"$/NATIVEPHP_APP_ID="localdev.goodm4ven.muttasiq"/'

    echo "Switched to development mode."
fi
