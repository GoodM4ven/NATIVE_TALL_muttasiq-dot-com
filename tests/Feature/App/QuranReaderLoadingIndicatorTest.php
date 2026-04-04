<?php

declare(strict_types=1);

it('renders teleported css orbit loader indicator for quran calibration overlay', function () {
    $readerViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));

    expect($readerViewSource)->not->toBeFalse()
        ->and($readerViewSource)->toContain('<template x-teleport="body">')
        ->and($readerViewSource)->toContain('class="quran-calibration-hud"')
        ->and($readerViewSource)->toContain('class="quran-calibration-spinner"')
        ->and($readerViewSource)->toContain('class="quran-calibration-spinner-track"')
        ->and($readerViewSource)->toContain('class="quran-calibration-spinner-car"')
        ->and($readerViewSource)->toContain('--uib-stroke: 5px;')
        ->and($readerViewSource)->toContain('border-radius: 38%;')
        ->and($readerViewSource)->toContain('background: conic-gradient(')
        ->and($readerViewSource)->toContain('animation: quran-calibration-car-orbit var(--uib-speed) linear infinite;')
        ->and($readerViewSource)->toContain('@keyframes quran-calibration-car-orbit')
        ->and($readerViewSource)->toContain('.quran-calibration-overlay::before')
        ->and($readerViewSource)->not->toContain('x-ref="calibrationSpinnerOrbit"')
        ->and($readerViewSource)->not->toContain('viewBox="0 0 37 37"')
        ->and($readerViewSource)->not->toContain('<l-squircle');
});
