<?php

declare(strict_types=1);

it('does not exclude vite build output from native bundle cleanup', function () {
    expect(config('nativephp.cleanup_exclude_files'))
        ->not->toContain('build');
});
