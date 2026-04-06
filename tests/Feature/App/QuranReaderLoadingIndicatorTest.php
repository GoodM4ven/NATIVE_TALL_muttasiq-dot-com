<?php

declare(strict_types=1);

it('renders teleported css jelly triangle loader indicator for quran calibration overlay', function () {
    $readerViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));

    expect($readerViewSource)->not->toBeFalse()
        ->and($readerViewSource)->toContain('<template x-teleport="body">')
        ->and($readerViewSource)->toContain('class="quran-calibration-hud"')
        ->and($readerViewSource)->toContain('x-transition:enter="transition duration-220 ease-out"')
        ->and($readerViewSource)->toContain('x-transition:leave="transition duration-180 ease-in"')
        ->and($readerViewSource)->toContain('class="quran-calibration-spinner"')
        ->and($readerViewSource)->toContain('<l-jelly-triangle')
        ->and($readerViewSource)->toContain('--uib-size: 34px;')
        ->and($readerViewSource)->toContain('.quran-calibration-spinner l-jelly-triangle')
        ->and($readerViewSource)->toContain('.quran-reader-panel--calibrating .quran-bottom-strip')
        ->and($readerViewSource)->toContain('contain: layout style;')
        ->and($readerViewSource)->toContain('overflow: visible;')
        ->and($readerViewSource)->toContain('.quran-calibration-overlay::before')
        ->and($readerViewSource)->not->toContain('x-ref="calibrationSpinnerOrbit"')
        ->and($readerViewSource)->not->toContain('viewBox="0 0 37 37"')
        ->and($readerViewSource)->not->toContain('<l-squircle');
});
