<?php

use App\Livewire\Settings;
use App\Models\Setting;

use function Pest\Livewire\livewire;

it('does not persist settings changes globally', function () {
    Setting::query()->firstOrCreate(
        ['name' => 'does_skip_notice_panels'],
        ['value' => false],
    );

    $initialSettings = Setting::query()->pluck('value', 'name')->all();

    $payload = [
        'does_automatically_switch_completed_athkar' => false,
        'does_clicking_switch_athkar_too' => false,
        'does_prevent_switching_athkar_until_completion' => false,
        'does_skip_notice_panels' => true,
    ];

    livewire(Settings::class)
        ->callAction('settings', data: $payload)
        ->assertHasNoFormErrors()
        ->assertDispatched('settings-updated');

    $updatedSettings = Setting::query()->pluck('value', 'name')->all();

    expect($updatedSettings)->toBe($initialSettings);
});
