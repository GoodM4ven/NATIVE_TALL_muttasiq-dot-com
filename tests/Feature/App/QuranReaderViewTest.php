<?php

declare(strict_types=1);

it('wires quran reader entry points from main menu to hash navigation and view mount', function () {
    $menuSource = file_get_contents(resource_path('views/components/partials/main-menu.blade.php'));
    $homeSource = file_get_contents(resource_path('views/home.blade.php'));
    $quranViewSource = file_get_contents(resource_path('views/livewire/quran-reader.blade.php'));

    expect($menuSource)->not->toBeFalse()
        ->and($menuSource)->toContain(":caption=\"'الكتاب'\"")
        ->and($menuSource)->toContain(":onClickCallback=\"'() => (\$viewNav(`quran-reader`))'\"");

    expect($homeSource)->not->toBeFalse()
        ->and($homeSource)->toContain("'quran-reader': {")
        ->and($homeSource)->toContain("'#quran-reader': () => runHashAction(() => {")
        ->and($homeSource)->toContain('<x-partials.quran-reader.index />');

    expect($quranViewSource)->not->toBeFalse()
        ->and($quranViewSource)->toContain('quran-ayah-line-run-rect')
        ->and($quranViewSource)->toContain('quran-ayah-line-run-centered')
        ->and($quranViewSource)->toContain('wire:click="selectAyah(');
});

it('registers qpc page font route contract used by quran reader pages', function () {
    expect(route('qpc-v2-font', ['page' => 1], false))->toBe('/qpc-v2-fonts/1.ttf');
});
