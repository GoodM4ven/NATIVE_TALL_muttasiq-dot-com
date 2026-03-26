<?php

declare(strict_types=1);

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\getJson;

it('returns normalized settings payload with limits/version and migrates legacy visual-enhancement key', function () {
    RateLimiter::for('settings', fn (Request $request): Limit => Limit::none());
    config([
        'app.custom.app_version' => '7.8.9',
    ]);
    Setting::setAppVersion('1.1.4');

    Setting::query()->updateOrCreate(
        ['name' => Setting::DOES_SKIP_GUIDANCE_PANELS],
        ['value' => 1],
    );

    $response = getJson(route('api.settings.index'));

    $response->assertSuccessful();

    $settings = $response->json('settings');

    expect($settings)
        ->toBeArray()
        ->toHaveKey(Setting::DOES_SKIP_GUIDANCE_PANELS, true)
        ->toHaveKey(Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS, true)
        ->toHaveKey(Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY, true)
        ->toHaveKey(Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY, true)
        ->toHaveKey(Setting::MINIMUM_MAIN_TEXT_SIZE)
        ->toHaveKey(Setting::MAXIMUM_MAIN_TEXT_SIZE);

    $limits = $response->json('mainTextSizeLimits');

    expect($limits)
        ->toBeArray()
        ->toHaveKey(Setting::MINIMUM_MAIN_TEXT_SIZE)
        ->toHaveKey(Setting::MAXIMUM_MAIN_TEXT_SIZE);

    expect($limits[Setting::MINIMUM_MAIN_TEXT_SIZE])
        ->toHaveKeys(['min', 'max', 'default']);

    expect($response->json('appVersion'))->toBe('1.1.4');

    Setting::query()->updateOrCreate(
        ['name' => Setting::MINIMUM_MAIN_TEXT_SIZE],
        ['value' => 18],
    );

    Setting::query()->updateOrCreate(
        ['name' => Setting::MAXIMUM_MAIN_TEXT_SIZE],
        ['value' => 20],
    );

    $response = getJson(route('api.settings.index'));

    $response->assertSuccessful();

    expect($response->json('settings.'.Setting::MINIMUM_MAIN_TEXT_SIZE))->toBe(18);
    expect($response->json('settings.'.Setting::MAXIMUM_MAIN_TEXT_SIZE))->toBe(20);

    Setting::query()->updateOrCreate(
        ['name' => Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS],
        ['value' => 0],
    );

    $response = getJson(route('api.settings.index'));

    $response->assertSuccessful();

    expect($response->json('settings.'.Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS))->toBeFalse();

    Setting::query()->where('name', Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS)->delete();

    Setting::query()->updateOrCreate(
        ['name' => 'does_enable_main_text_shimmering'],
        ['value' => 0],
    );

    $migration = require database_path(
        'migrations/2026_03_12_084345_rename_main_text_shimmering_setting_to_visual_enhancements.php',
    );
    $migration->up();

    expect(Setting::query()->where('name', 'does_enable_main_text_shimmering')->exists())->toBeFalse();
    expect((int) Setting::query()->where('name', Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS)->value('value'))
        ->toBe(0);

    Setting::query()->where('name', Setting::APP_VERSION)->delete();

    $response = getJson(route('api.settings.index'));

    $response->assertSuccessful();

    expect($response->json('appVersion'))->toBe('7.8.9');
});

it('rate limits the settings endpoint', function () {
    RateLimiter::for('settings', fn (Request $request): Limit => Limit::perMinute(2)->by('test'));

    getJson(route('api.settings.index'))->assertSuccessful();
    getJson(route('api.settings.index'))->assertSuccessful();
    getJson(route('api.settings.index'))->assertTooManyRequests();
});
