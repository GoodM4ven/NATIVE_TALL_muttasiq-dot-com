<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect([
        'dd',
        'dump',
        'var_dump',
        'echo',
        // 'Illuminate\Support\Facades\Log',
        // 'logger',
    ])
    ->each->not->toBeUsed();

arch('it uses strict typing everywhere')
    ->expect('App')
    ->toUseStrictTypes();

test('it will not point to dependency development versions', function () {
    expect(\Illuminate\Support\Facades\File::get(base_path('composer.json')))
        ->not
        ->toContain('dev-');
});
