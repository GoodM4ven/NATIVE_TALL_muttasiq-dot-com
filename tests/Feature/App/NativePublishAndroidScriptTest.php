<?php

declare(strict_types=1);

it('publishes android bundles using the upload certificate alias and current version code', function () {
    $scriptPath = base_path('.scripts/publish-android.sh');

    expect($scriptPath)->toBeFile()
        ->and(is_executable($scriptPath))->toBeTrue();

    $script = file_get_contents($scriptPath);

    expect($script)->toContain('release_type="${1:-${RELEASE_TYPE:-minor}}"')
        ->and($script)->toContain('write_env_var()')
        ->and($script)->toContain('upload_certificate_file="${ANDROID_UPLOAD_CERTIFICATE_FILE:-${root_dir}/.credentials/upload_cert.der}"')
        ->and($script)->toContain('read_certificate_sha1()')
        ->and($script)->toContain('find_keystore_alias_by_fingerprint()')
        ->and($script)->toContain('key_alias="$(find_keystore_alias_by_fingerprint "${keystore_file}" "${keystore_password}" "${upload_certificate_sha1}" || true)"')
        ->and($script)->toContain('bundle_version_code="$((current_version_code + 1))"')
        ->and($script)->toContain('export NATIVEPHP_APP_VERSION_CODE="${bundle_version_code}"')
        ->and($script)->toContain('export NATIVEPHP_APP_VERSION="$(read_env_var "NATIVEPHP_APP_VERSION" || true)"')
        ->and($script)->toContain('export ANDROID_KEY_ALIAS="${key_alias}"')
        ->and($script)->toContain('php artisan native:release "${release_type}" --no-interaction')
        ->and($script)->toContain('php artisan native:package android \\')
        ->and($script)->toContain('--build-type=bundle')
        ->and($script)->toContain('write_env_var "NATIVEPHP_APP_VERSION_CODE" "${bundle_version_code}"')
        ->and($script)->not->toContain('--upload-to-play-store')
        ->and($script)->not->toContain('--play-store-track')
        ->and($script)->not->toContain('--google-service-key')
        ->and($script)->toContain('--no-tty');
});
