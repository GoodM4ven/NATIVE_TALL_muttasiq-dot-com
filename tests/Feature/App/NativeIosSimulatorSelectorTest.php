<?php

declare(strict_types=1);

use App\Services\Native\IosSimulatorSelector;

it('prefers the default iphone even when another simulator is already booted', function () {
    $selector = app(IosSimulatorSelector::class);

    $selectedUdid = $selector->selectUdidFromJson(json_encode([
        'devices' => [
            'com.apple.CoreSimulator.SimRuntime.iOS-26-0' => [
                [
                    'name' => 'iPhone 17 Pro',
                    'udid' => 'iphone-17-pro',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
                [
                    'name' => 'iPad Air 11-inch (M3)',
                    'udid' => 'ipad-air-11',
                    'state' => 'Booted',
                    'isAvailable' => true,
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    expect($selectedUdid)->toBe('iphone-17-pro');
});

it('falls back to the iPhone 17 Pro when no simulator is already booted', function () {
    $selector = app(IosSimulatorSelector::class);

    $selectedUdid = $selector->selectUdidFromJson(json_encode([
        'devices' => [
            'com.apple.CoreSimulator.SimRuntime.iOS-26-0' => [
                [
                    'name' => 'iPhone 17',
                    'udid' => 'iphone-17',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
                [
                    'name' => 'iPhone 17 Pro',
                    'udid' => 'iphone-17-pro',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    expect($selectedUdid)->toBe('iphone-17-pro');
});

it('allows the default iphone name to be overridden', function () {
    $selector = app(IosSimulatorSelector::class);

    $selectedUdid = $selector->selectUdidFromJson(json_encode([
        'devices' => [
            'com.apple.CoreSimulator.SimRuntime.iOS-26-0' => [
                [
                    'name' => 'iPhone 17 Pro',
                    'udid' => 'iphone-17-pro',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
                [
                    'name' => 'iPhone Air',
                    'udid' => 'iphone-air',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR), 'iPhone Air');

    expect($selectedUdid)->toBe('iphone-air');
});

it('does not silently select another iphone when the default iphone is unavailable', function () {
    $selector = app(IosSimulatorSelector::class);

    $selectedUdid = $selector->selectUdidFromJson(json_encode([
        'devices' => [
            'com.apple.CoreSimulator.SimRuntime.iOS-18-0' => [
                [
                    'name' => 'iPhone 15 Pro',
                    'udid' => 'iphone-15-pro',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
                [
                    'name' => 'iPad Air 11-inch (M3)',
                    'udid' => 'ipad-air-11',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    expect($selectedUdid)->toBeNull();
});

it('returns null for invalid simulator payloads', function () {
    $selector = app(IosSimulatorSelector::class);

    expect($selector->selectUdidFromJson('not-json'))->toBeNull()
        ->and($selector->selectUdidFromJson(json_encode([], JSON_THROW_ON_ERROR)))->toBeNull();
});
