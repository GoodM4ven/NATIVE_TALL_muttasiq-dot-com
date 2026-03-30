<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

function createComposerManifestFixture(array $repositories): string
{
    $path = tempnam(sys_get_temp_dir(), 'composer-switch-');

    if ($path === false) {
        throw new RuntimeException('Failed to create temporary composer fixture.');
    }

    $composer = [
        'name' => 'tests/composer-switch-fixture',
        'require' => [
            'php' => '^8.3',
        ],
        'repositories' => $repositories,
    ];

    file_put_contents(
        $path,
        json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    );

    return $path;
}

it('reports local plugin switch status for enabled and disabled composer repository configurations', function () {
    $manifestPath = createComposerManifestFixture([
        'nativephp-muttasiq-patches' => [
            'type' => 'path',
            'url' => '/tmp/local-native-plugin',
        ],
    ]);

    try {
        artisan("app:verify-local-plugin-switch --skip-prompt --composer-file={$manifestPath}")
            ->expectsOutputToContain('Local plugin switch reminder')
            ->expectsOutputToContain('ENABLED')
            ->assertExitCode(0);
    } finally {
        @unlink($manifestPath);
    }

    $manifestPath = createComposerManifestFixture([
        [
            'name' => 'nativephp-muttasiq-patches',
            'type' => 'path',
            'url' => '/tmp/local-native-plugin',
            'options' => [
                'symlink' => true,
                'versions' => [
                    'goodm4ven/nativephp-muttasiq-patches' => '1.0.999999',
                ],
            ],
        ],
    ]);

    try {
        artisan("app:verify-local-plugin-switch --skip-prompt --composer-file={$manifestPath}")
            ->expectsOutputToContain('Local plugin switch reminder')
            ->expectsOutputToContain('ENABLED')
            ->expectsOutputToContain('/tmp/local-native-plugin')
            ->assertExitCode(0);
    } finally {
        @unlink($manifestPath);
    }

    $manifestPath = createComposerManifestFixture([]);

    try {
        artisan("app:verify-local-plugin-switch --skip-prompt --composer-file={$manifestPath}")
            ->expectsOutputToContain('looks good')
            ->assertExitCode(0);
    } finally {
        @unlink($manifestPath);
    }
});

it('honors confirmation flow when local plugin path override is enabled', function () {
    $declinedManifestPath = createComposerManifestFixture([
        [
            'name' => 'nativephp-muttasiq-patches',
            'type' => 'path',
            'url' => '/tmp/local-native-plugin',
            'options' => [
                'versions' => [
                    'goodm4ven/nativephp-muttasiq-patches' => '1.0.999999',
                ],
            ],
        ],
    ]);

    try {
        artisan("app:verify-local-plugin-switch --composer-file={$declinedManifestPath}")
            ->expectsConfirmation(
                'Local path plugin repository for goodm4ven/nativephp-muttasiq-patches is still enabled. Continue anyway?',
                'no',
            )
            ->assertExitCode(1);
    } finally {
        @unlink($declinedManifestPath);
    }

    $acceptedManifestPath = createComposerManifestFixture([
        [
            'name' => 'nativephp-muttasiq-patches',
            'type' => 'path',
            'url' => '/tmp/local-native-plugin',
            'options' => [
                'versions' => [
                    'goodm4ven/nativephp-muttasiq-patches' => '1.0.999999',
                ],
            ],
        ],
    ]);

    try {
        artisan("app:verify-local-plugin-switch --composer-file={$acceptedManifestPath}")
            ->expectsConfirmation(
                'Local path plugin repository for goodm4ven/nativephp-muttasiq-patches is still enabled. Continue anyway?',
                'yes',
            )
            ->assertExitCode(0);
    } finally {
        @unlink($acceptedManifestPath);
    }
});
