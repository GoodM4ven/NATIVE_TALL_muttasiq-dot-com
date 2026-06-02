<?php

function changelogImagesDirectory(): string
{
    return public_path('docs/updates/images');
}

it('keeps changelog images accessible and constrained to the public docs images directory across runtimes', function () {
    $imagePath = changelogImagesDirectory().'/v-0-5-0/web/changelogs-modal.webp';

    expect($imagePath)
        ->toBeFile()
        ->and(mime_content_type($imagePath))
        ->toBe('image/webp');

    $imagesDirectory = realpath(changelogImagesDirectory());
    $requestedPath = realpath(changelogImagesDirectory().'/../changelogs.md');

    expect($imagesDirectory)->not->toBeFalse();
    expect($requestedPath)->not->toStartWith($imagesDirectory.DIRECTORY_SEPARATOR);

    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'ios',
    ]);

    $imagePath = changelogImagesDirectory().'/v-0-4-0/android/responsive-text-overflow-handling.webp';

    expect($imagePath)
        ->toBeFile()
        ->and(mime_content_type($imagePath))
        ->toBe('image/webp');
});
