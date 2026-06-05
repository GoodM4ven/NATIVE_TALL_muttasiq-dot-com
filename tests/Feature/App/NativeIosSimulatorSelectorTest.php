<?php

declare(strict_types=1);

use App\Services\Native\IosSimulatorSelector;

it('prefers an already booted simulator even when it is not an iPhone', function () {
    $selector = app(IosSimulatorSelector::class);

    $selectedUdid = $selector->selectUdidFromJson(json_encode([
        'devices' => [
            'com.apple.CoreSimulator.SimRuntime.iOS-18-0' => [
                [
                    'name' => 'iPhone 16 Pro',
                    'udid' => 'iphone-16-pro',
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

    expect($selectedUdid)->toBe('ipad-air-11');
});

it('falls back to the iPhone 16 Pro when no simulator is already booted', function () {
    $selector = app(IosSimulatorSelector::class);

    $selectedUdid = $selector->selectUdidFromJson(json_encode([
        'devices' => [
            'com.apple.CoreSimulator.SimRuntime.iOS-18-0' => [
                [
                    'name' => 'iPhone 16',
                    'udid' => 'iphone-16',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
                [
                    'name' => 'iPhone 16 Pro',
                    'udid' => 'iphone-16-pro',
                    'state' => 'Shutdown',
                    'isAvailable' => true,
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    expect($selectedUdid)->toBe('iphone-16-pro');
});

it('falls back to the first available iphone when the iPhone 16 Pro is unavailable', function () {
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

    expect($selectedUdid)->toBe('iphone-15-pro');
});

it('returns null for invalid simulator payloads', function () {
    $selector = app(IosSimulatorSelector::class);

    expect($selector->selectUdidFromJson('not-json'))->toBeNull()
        ->and($selector->selectUdidFromJson(json_encode([], JSON_THROW_ON_ERROR)))->toBeNull();
});
