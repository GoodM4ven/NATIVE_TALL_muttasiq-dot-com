<?php

use App\Livewire\ControlPanel;
use App\Models\Setting;
use App\Providers\AppServiceProvider;
use App\Services\Traits\HasControlPanelAboutTab;
use App\Services\Traits\HasControlPanelChangelogsTab;
use App\Services\Traits\HasControlPanelSettingsTab;

use function Pest\Livewire\livewire;

it('composes and executes the control panel lifecycle without persisting runtime-only updates', function () {
    $usedTraits = class_uses_recursive(ControlPanel::class);

    expect($usedTraits)
        ->toContain(HasControlPanelSettingsTab::class)
        ->toContain(HasControlPanelChangelogsTab::class)
        ->toContain(HasControlPanelAboutTab::class);

    expect(Setting::definitionsForGroup(Setting::GROUP_QURAN))
        ->toHaveKey(Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT)
        ->toHaveKey(Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY)
        ->toHaveKey(Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY)
        ->toHaveKey(Setting::DOES_QURAN_SHOW_IMMERSIVE_MOBILE_EDGE_CAPTIONS);

    Setting::query()->firstOrCreate(
        ['name' => Setting::DOES_SKIP_GUIDANCE_PANELS],
        ['value' => false],
    );

    $initialSettings = Setting::query()->pluck('value', 'name')->all();

    $payload = [
        Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR => false,
        Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO => false,
        Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION => false,
        Setting::DOES_SKIP_GUIDANCE_PANELS => true,
        Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS => false,
        Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY => false,
        Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT => true,
        Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY => false,
        Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY => true,
        Setting::MINIMUM_MAIN_TEXT_SIZE => 18,
        Setting::MAXIMUM_MAIN_TEXT_SIZE => 20,
    ];

    livewire(ControlPanel::class)
        ->callAction('controlPanel', data: $payload)
        ->assertHasNoFormErrors()
        ->assertDispatched('control-panel-updated');

    $updatedSettings = Setting::query()->pluck('value', 'name')->all();

    expect($updatedSettings)->toBe($initialSettings);

    livewire(ControlPanel::class)
        ->callAction('controlPanel', data: [
            Setting::MINIMUM_MAIN_TEXT_SIZE => Setting::MIN_MAIN_TEXT_SIZE_MIN - 1,
            Setting::MAXIMUM_MAIN_TEXT_SIZE => Setting::MAX_MAIN_TEXT_SIZE_MAX + 1,
        ])
        ->assertHasNoFormErrors()
        ->assertSet('clientControlPanel.minimum_main_text_size', Setting::MIN_MAIN_TEXT_SIZE_DEFAULT)
        ->assertSet('clientControlPanel.maximum_main_text_size', Setting::MAX_MAIN_TEXT_SIZE_DEFAULT);

    livewire(ControlPanel::class)
        ->callAction('controlPanel', data: [
            Setting::MINIMUM_MAIN_TEXT_SIZE => 19,
            Setting::MAXIMUM_MAIN_TEXT_SIZE => 16,
        ])
        ->assertHasNoFormErrors()
        ->assertSet('clientControlPanel.minimum_main_text_size', Setting::MIN_MAIN_TEXT_SIZE_DEFAULT)
        ->assertSet('clientControlPanel.maximum_main_text_size', Setting::MAX_MAIN_TEXT_SIZE_DEFAULT);

    livewire(ControlPanel::class)
        ->call('triggerReaderMaintenancePulse')
        ->assertSet('mountedActions', [])
        ->assertDispatched('control-panel-updated');
});

it('resolves the app version from settings and falls back to config defaults', function () {
    Setting::setAppVersion('2.0.0');

    expect(Setting::appVersion())->toBe('2.0.0');

    Setting::query()->where('name', Setting::APP_VERSION)->delete();
    config(['app.custom.app_version' => '9.9.9']);

    expect(Setting::appVersion())->toBe('9.9.9');
});

it('renders changelog image urls correctly across native ios, native android, and web runtimes', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'ios',
    ]);

    $provider = app()->getProvider(AppServiceProvider::class);
    expect($provider)->not->toBeNull();

    $provider->boot();

    $component = app(ControlPanel::class);
    $method = new ReflectionMethod($component, 'changelogsMarkdown');
    $method->setAccessible(true);

    $html = $method->invoke($component)->toHtml();

    expect($html)
        ->toContain('src="/_assets/docs/updates/images/')
        ->not->toContain('src="data:image/png;base64,')
        ->not->toContain('src="php://127.0.0.1/docs/updates/images/');

    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
    ]);

    $component = app(ControlPanel::class);
    $method = new ReflectionMethod($component, 'changelogsMarkdown');
    $method->setAccessible(true);

    $html = $method->invoke($component)->toHtml();

    expect($html)
        ->toContain('src="/_assets/docs/updates/images/')
        ->not->toContain('src="/docs/updates/image-proxy/')
        ->not->toContain('src="data:image/png;base64,');

    config([
        'nativephp-internal.running' => false,
    ]);

    $component = app(ControlPanel::class);
    $method = new ReflectionMethod($component, 'changelogsMarkdown');
    $method->setAccessible(true);

    $html = $method->invoke($component)->toHtml();

    expect($html)
        ->toContain('src="/docs/updates/images/')
        ->not->toContain('src="/_assets/docs/updates/images/');
});
