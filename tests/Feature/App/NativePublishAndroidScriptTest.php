<?php

declare(strict_types=1);

it('publishes android bundles from the bundled credentials', function () {
    $scriptPath = base_path('.scripts/publish-android.sh');

    expect($scriptPath)->toBeFile()
        ->and(is_executable($scriptPath))->toBeTrue();

    $script = file_get_contents($scriptPath);

    expect($script)->toContain('release_type="${1:-${RELEASE_TYPE:-patch}}"')
        ->and($script)->toContain('read_env_var()')
        ->and($script)->toContain('keystore_file="${ANDROID_KEYSTORE_FILE:-$(read_env_var "ANDROID_KEYSTORE_FILE" || true)}"')
        ->and($script)->toContain('key_alias="${ANDROID_KEY_ALIAS:-$(read_env_var "ANDROID_KEY_ALIAS" || true)}"')
        ->and($script)->toContain('php artisan native:release "${release_type}" --no-interaction')
        ->and($script)->toContain('php artisan native:package android \\')
        ->and($script)->toContain('--build-type=bundle')
        ->and($script)->not->toContain('--upload-to-play-store')
        ->and($script)->not->toContain('--play-store-track')
        ->and($script)->not->toContain('--google-service-key')
        ->and($script)->toContain('--no-tty');
});
