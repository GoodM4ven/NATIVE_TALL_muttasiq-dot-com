<?php

declare(strict_types=1);
use Illuminate\Support\Facades\File;

arch('it will not use debugging functions')
    ->expect('App')
    ->not->toUse([
        'dd',
        'dump',
        'var_dump',
        'echo',
        // 'Illuminate\Support\Facades\Log',
        // 'logger',
    ]);

arch('it uses strict typing everywhere')
    ->expect('App')
    ->toUseStrictTypes();

test('it will not point to dependency development versions', function () {
    /** @var array{require?: array<string, string>, require-dev?: array<string, string>} $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $constraints = array_merge(
        array_values($composer['require'] ?? []),
        array_values($composer['require-dev'] ?? []),
    );

    foreach ($constraints as $constraint) {
        $normalizedConstraint = (string) $constraint;

        expect($normalizedConstraint)
            ->not->toStartWith('dev-')
            ->and($normalizedConstraint)->not->toContain('@dev');
    }
});
