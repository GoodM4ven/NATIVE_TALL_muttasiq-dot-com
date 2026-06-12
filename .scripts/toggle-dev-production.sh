#!/usr/bin/env bash

FILE=".env"

# Determine current mode
app_name=$(grep -E '^APP_NAME=' "$FILE" || true)
native_app_id=$(grep -E '^NATIVEPHP_APP_ID=' "$FILE" || true)

if [ -n "$app_name" ] && [[ "$app_name" == *'تطوير'* ]]; then
    current_mode="development"
else
    current_mode="production"
fi

if [ "$current_mode" = "development" ]; then
    sed -i 's/^APP_NAME="تطوير متسق"$/APP_NAME="متسق"/' "$FILE"
    sed -i 's/^NATIVEPHP_APP_ID="localdev.goodm4ven.muttasiq"$/NATIVEPHP_APP_ID="dev.goodm4ven.muttasiq"/' "$FILE"

    echo "Switched to production mode."
else
    sed -i 's/^APP_NAME="متسق"$/APP_NAME="تطوير متسق"/' "$FILE"
    sed -i 's/^NATIVEPHP_APP_ID="dev.goodm4ven.muttasiq"$/NATIVEPHP_APP_ID="localdev.goodm4ven.muttasiq"/' "$FILE"

    echo "Switched to development mode."
fi
