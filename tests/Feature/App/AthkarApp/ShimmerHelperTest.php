<?php

declare(strict_types=1);

it('uses the shared athkar-agnostic shimmer helper from the reader script', function () {
    $indexSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/index.js'));
    $sharedSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/shared.js'));

    expect($indexSource)->not->toBeFalse()
        ->and($indexSource)->toContain("import * as shared from './shared';")
        ->and($indexSource)->not->toContain('createAthkarShimmerController');

    expect($sharedSource)->not->toBeFalse()
        ->and($sharedSource)->toContain("import { createShimmerController } from '../../shimmer';")
        ->and($sharedSource)->toContain('createShimmerController')
        ->and($sharedSource)->not->toContain('createAthkarShimmerController');

    $source = file_get_contents(resource_path('js/support/alpine/shimmer.js'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('export const createShimmerController')
        ->and($source)->not->toContain('athkar-')
        ->and($source)->not->toContain('createAthkarShimmerController');
});

it('guards progress stats getters when reader data is inspected without method bindings', function () {
    $sharedSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/shared.js'));
    $metricsSource = file_get_contents(resource_path('js/support/alpine/data/athkar-app-reader/metrics-module.js'));

    expect($sharedSource)->not->toBeFalse()
        ->and($sharedSource)->toContain('const resolveProgressStatsSafely = (context) => {')
        ->and($sharedSource)->toContain('if (typeof context?.resolveProgressStats !== \'function\') {');

    expect($metricsSource)->not->toBeFalse()
        ->and($metricsSource)->toContain('return resolveProgressStatsSafely(this).totalRequiredCount;')
        ->and($metricsSource)->toContain('return resolveProgressStatsSafely(this).totalCompletedCount;')
        ->and($metricsSource)->toContain('return resolveProgressStatsSafely(this).totalRequiredLetters;')
        ->and($metricsSource)->toContain('return resolveProgressStatsSafely(this).totalCompletedLetters;')
        ->and($metricsSource)->toContain('return resolveProgressStatsSafely(this).totalRemainingLetters;')
        ->and($metricsSource)->toContain('return resolveProgressStatsSafely(this).slideProgressPercent;')
        ->and($metricsSource)->toContain('return resolveProgressStatsSafely(this).maxNavigableIndex;');
});
