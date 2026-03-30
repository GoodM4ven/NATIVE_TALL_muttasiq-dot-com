<?php

use App\Models\Setting;

test('athkar setting defaults are available for the home payload', function () {
    $defaults = Setting::defaults();

    expect($defaults)->toBeArray();
    expect(array_key_exists(Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::DOES_SKIP_GUIDANCE_PANELS, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::MINIMUM_MAIN_TEXT_SIZE, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::MAXIMUM_MAIN_TEXT_SIZE, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY, $defaults))->toBeTrue();
    expect(array_key_exists(Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY, $defaults))->toBeTrue();
    expect($defaults[Setting::DOES_SKIP_GUIDANCE_PANELS])->toBeFalse();
    expect($defaults[Setting::MINIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MIN_MAIN_TEXT_SIZE_DEFAULT);
    expect($defaults[Setting::MAXIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MAX_MAIN_TEXT_SIZE_DEFAULT);
    expect($defaults[Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS])->toBeTrue();
    expect($defaults[Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY])->toBeTrue();
    expect($defaults[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY])->toBeTrue();
    expect($defaults[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY])->toBeFalse();
});

test('it exposes main text size limits for frontend consumers', function () {
    $limits = Setting::mainTextSizeLimits();

    expect($limits[Setting::MINIMUM_MAIN_TEXT_SIZE])->toBe([
        'min' => Setting::MIN_MAIN_TEXT_SIZE_MIN,
        'max' => Setting::MIN_MAIN_TEXT_SIZE_MAX,
        'default' => Setting::MIN_MAIN_TEXT_SIZE_DEFAULT,
    ]);
    expect($limits[Setting::MAXIMUM_MAIN_TEXT_SIZE])->toBe([
        'min' => Setting::MAX_MAIN_TEXT_SIZE_MIN,
        'max' => Setting::MAX_MAIN_TEXT_SIZE_MAX,
        'default' => Setting::MAX_MAIN_TEXT_SIZE_DEFAULT,
    ]);
});

test('it resolves translated setting definitions without recursive arabic text lookup', function () {
    $definitions = Setting::definitions();
    $label = $definitions[Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY]['label'] ?? null;

    expect($definitions)->toBeArray()
        ->toHaveKey(Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY);

    expect($label)->toBeString();
    expect(strlen((string) $label))->toBeGreaterThan(0);
});

test('it normalizes settings payload values by their definitions', function () {
    $normalized = Setting::normalizeSettings([
        Setting::DOES_SKIP_GUIDANCE_PANELS => '1',
        Setting::MINIMUM_MAIN_TEXT_SIZE => 200,
        Setting::MAXIMUM_MAIN_TEXT_SIZE => 200,
    ]);

    expect($normalized[Setting::DOES_SKIP_GUIDANCE_PANELS])->toBeTrue();
    expect($normalized[Setting::MINIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MIN_MAIN_TEXT_SIZE_MAX);
    expect($normalized[Setting::MAXIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MAX_MAIN_TEXT_SIZE_MAX);

    $normalized = Setting::normalizeSettings([
        Setting::MINIMUM_MAIN_TEXT_SIZE => 7,
        Setting::MAXIMUM_MAIN_TEXT_SIZE => 7,
    ]);

    expect($normalized[Setting::MINIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MIN_MAIN_TEXT_SIZE_MIN);
    expect($normalized[Setting::MAXIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MAX_MAIN_TEXT_SIZE_MIN);

    $normalized = Setting::normalizeSettings([
        Setting::MINIMUM_MAIN_TEXT_SIZE => Setting::MIN_MAIN_TEXT_SIZE_MAX,
        Setting::MAXIMUM_MAIN_TEXT_SIZE => Setting::MAX_MAIN_TEXT_SIZE_MIN,
    ]);

    expect($normalized[Setting::MINIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MIN_MAIN_TEXT_SIZE_MIN);
    expect($normalized[Setting::MAXIMUM_MAIN_TEXT_SIZE])->toBe(Setting::MAX_MAIN_TEXT_SIZE_MIN);
});

test('it caps wird khatamat target to fixed daily and monthly maxima', function () {
    expect(Setting::quranWirdKhatmatMaxForFrequency(Setting::QURAN_WIRD_FREQUENCY_DAILY))
        ->toBe(Setting::QURAN_WIRD_KHATMAT_MAX);
    expect(Setting::quranWirdKhatmatMaxForFrequency(Setting::QURAN_WIRD_FREQUENCY_MONTHLY))
        ->toBe(Setting::QURAN_WIRD_KHATMAT_MONTHLY_MAX);

    $normalizedMonthly = Setting::normalizeSettings([
        Setting::QURAN_WIRD_FREQUENCY_MODE => Setting::QURAN_WIRD_FREQUENCY_MONTHLY,
        Setting::QURAN_WIRD_KHATMAT_TARGET => 999,
    ]);
    $normalizedDaily = Setting::normalizeSettings([
        Setting::QURAN_WIRD_FREQUENCY_MODE => Setting::QURAN_WIRD_FREQUENCY_DAILY,
        Setting::QURAN_WIRD_KHATMAT_TARGET => 999,
    ]);

    expect($normalizedMonthly[Setting::QURAN_WIRD_KHATMAT_TARGET])
        ->toBe(Setting::QURAN_WIRD_KHATMAT_MONTHLY_MAX);
    expect($normalizedDaily[Setting::QURAN_WIRD_KHATMAT_TARGET])
        ->toBe(Setting::QURAN_WIRD_KHATMAT_MAX);
});
