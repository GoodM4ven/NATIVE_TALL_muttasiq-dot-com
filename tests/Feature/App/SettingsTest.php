<?php

use App\Livewire\ControlPanel;
use App\Models\Setting;
use App\Providers\AppServiceProvider;

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
        ->toContain('.webp')
        ->not->toContain('.png')
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
        ->toContain('.webp')
        ->not->toContain('.png')
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
        ->toContain('.webp')
        ->not->toContain('.png')
        ->not->toContain('src="/_assets/docs/updates/images/');
});
