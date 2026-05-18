<?php

declare(strict_types=1);

it('uses the shared athkar-agnostic shimmer helper from the reader script', function () {
    $source = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/index.js'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain("import { createShimmerController } from '../shimmer';")
        ->and($source)->toContain('createShimmerController({')
        ->and($source)->not->toContain('createAthkarShimmerController');

    $source = file_get_contents(resource_path('js/support/alpine/shimmer.js'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('export const createShimmerController')
        ->and($source)->not->toContain('athkar-')
        ->and($source)->not->toContain('createAthkarShimmerController');
});

it('guards progress stats getters when reader data is inspected without method bindings', function () {
    $source = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/index.js'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('const resolveProgressStatsSafely = (context) => {')
        ->and($source)->toContain('if (typeof context?.resolveProgressStats !== \'function\') {')
        ->and($source)->toContain('return resolveProgressStatsSafely(this).totalRequiredCount;')
        ->and($source)->toContain('return resolveProgressStatsSafely(this).totalCompletedCount;')
        ->and($source)->toContain('return resolveProgressStatsSafely(this).totalRequiredLetters;')
        ->and($source)->toContain('return resolveProgressStatsSafely(this).totalCompletedLetters;')
        ->and($source)->toContain('return resolveProgressStatsSafely(this).totalRemainingLetters;')
        ->and($source)->toContain('return resolveProgressStatsSafely(this).slideProgressPercent;')
        ->and($source)->toContain('return resolveProgressStatsSafely(this).maxNavigableIndex;');
});
