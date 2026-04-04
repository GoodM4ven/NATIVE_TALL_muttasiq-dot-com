<?php

declare(strict_types=1);

it('renders squircle loader indicator for quran calibration overlay', function () {
    $readerViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));
    $loaderRegistrationSource = file_get_contents(resource_path('js/packages/ldrs.js'));

    expect($readerViewSource)->not->toBeFalse()
        ->and($readerViewSource)->toContain('<l-squircle')
        ->and($readerViewSource)->toContain('size="37"')
        ->and($readerViewSource)->toContain('stroke="5"')
        ->and($readerViewSource)->toContain('stroke-length="0.15"')
        ->and($readerViewSource)->toContain('bg-opacity="0.1"')
        ->and($readerViewSource)->toContain('speed="0.9"')
        ->and($readerViewSource)->toContain('color="black"')
        ->and($readerViewSource)->not->toContain('quran-calibration-spinner-car')
        ->and($readerViewSource)->not->toContain('quran-calibration-spinner-track');

    expect($loaderRegistrationSource)->not->toBeFalse()
        ->and($loaderRegistrationSource)->toContain("import { squircle } from 'ldrs';")
        ->and($loaderRegistrationSource)->toContain('squircle.register();');
});
