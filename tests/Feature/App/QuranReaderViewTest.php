<?php

declare(strict_types=1);

it('wires quran reader entry points from main menu to hash navigation and view mount', function () {
    $menuSource = file_get_contents(resource_path('views/components/partials/main-menu.blade.php'));
    $homeSource = file_get_contents(resource_path('views/home.blade.php'));
    $quranGateSource = file_get_contents(resource_path('views/components/partials/quran-app/gate.blade.php'));
    $quranIndexSource = file_get_contents(resource_path('views/components/partials/quran-app/index.blade.php'));
    $quranReaderPartialSource = file_get_contents(
        resource_path('views/components/partials/quran-app/reader.blade.php'),
    );
    $quranReaderViewSource = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));
    $quranReaderClassSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $routesSource = file_get_contents(base_path('routes/web.php'));
    $appJsSource = file_get_contents(resource_path('js/app.js'));

    expect($menuSource)->not->toBeFalse()
        ->and($menuSource)->toContain(":caption=\"'الكتاب'\"")
        ->and($menuSource)->toContain(":onClickCallback=\"'() => (\$viewNav(`quran-app-gate`))'\"");

    expect($homeSource)->not->toBeFalse()
        ->and($homeSource)->toContain("'quran-app-gate': {")
        ->and($homeSource)->toContain("'quran-app-tilawa': {")
        ->and($homeSource)->toContain("'quran-app-hifth': {")
        ->and($homeSource)->toContain("'quran-app-tadabbur': {")
        ->and($homeSource)->toContain("'#quran-app-gate': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-tilawa': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-hifth': () => runHashAction(() => {")
        ->and($homeSource)->toContain("'#quran-app-tadabbur': () => runHashAction(() => {")
        ->and($homeSource)->toContain('<x-partials.quran-app.index />');

    expect($quranIndexSource)->not->toBeFalse()
        ->and($quranIndexSource)->toContain('<x-partials.quran-app.gate />')
        ->and($quranIndexSource)->toContain('<x-partials.quran-app.reader />');

    expect($quranGateSource)->not->toBeFalse()
        ->and($quranGateSource)->toContain('x-data="quranAppGate"')
        ->and($quranGateSource)->toContain('images/background/quran/tilawa.webp')
        ->and($quranGateSource)->toContain('images/background/quran/hifth.webp')
        ->and($quranGateSource)->toContain('images/background/quran/tadabbur.webp')
        ->and($quranGateSource)->toContain('quran-app-sector__media--tilawa')
        ->and($quranGateSource)->toContain('quran-app-sector__media--hifth')
        ->and($quranGateSource)->toContain('quran-app-sector__media--tadabbur')
        ->and($quranGateSource)->toContain('quran-app-gate-orbit')
        ->and($quranGateSource)->toContain('x-on:pointermove.passive="handlePointerMove($event)"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'tilawa\')"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'hifth\')"')
        ->and($quranGateSource)->toContain('x-on:click="openMode(\'tadabbur\')"')
        ->and($quranGateSource)->not->toContain('M0 0 L50 53')
        ->and($quranGateSource)->not->toContain('M100 0 L50 53')
        ->and($quranGateSource)->not->toContain('quran-app-gate-needle');

    expect($quranReaderPartialSource)->not->toBeFalse()
        ->and($quranReaderPartialSource)->toContain('<livewire:quran-app.reader />')
        ->and($quranReaderPartialSource)->toContain("views['quran-app-tilawa'].isOpen")
        ->and($quranReaderPartialSource)->toContain("views['quran-app-hifth'].isOpen")
        ->and($quranReaderPartialSource)->toContain("views['quran-app-tadabbur'].isOpen");

    expect($quranReaderViewSource)->not->toBeFalse()
        ->and($quranReaderViewSource)->toContain('quran-ayah-line-run-rect')
        ->and($quranReaderViewSource)->toContain('quran-ayah-line-run-centered')
        ->and($quranReaderViewSource)->toContain('wire:click="selectAyah(')
        ->and($quranReaderViewSource)->toContain("x-on:click=\"\$viewNav('quran-app-gate')\"");

    expect($quranReaderClassSource)->not->toBeFalse()
        ->and($quranReaderClassSource)->toContain("view('livewire.quran-app.reader'")
        ->and($quranReaderClassSource)->toContain('p\'.$pageNumber.\'.woff2')
        ->and($quranReaderClassSource)->toContain("'format' => 'woff2'");

    expect($routesSource)->not->toBeFalse()
        ->and($routesSource)->toContain('p\'.$page.\'.woff2')
        ->and($routesSource)->toContain("'content_type' => 'font/woff2'")
        ->and($routesSource)->toContain("'Content-Type' => \$contentType");

    expect($appJsSource)->not->toBeFalse()
        ->and($appJsSource)->toContain("import './support/alpine/data/quran-app-gate';");
});

it('registers qpc page font route contract used by quran reader pages', function () {
    expect(route('qpc-v2-font', ['page' => 1], false))->toBe('/qpc-v2-fonts/1.ttf');
});
