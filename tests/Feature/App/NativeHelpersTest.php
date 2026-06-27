<?php

declare(strict_types=1);

use App\Events\UserRealtimeEvent;
use App\Livewire\AuthButton;
use App\Models\User;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;

use function Pest\Livewire\livewire;

it('returns correct link-open expressions for mobile and desktop runtimes', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'ios',
    ]);

    $expression = open_link_native_aware('https://example.com');

    expect($expression)
        ->toContain('window.browser?.open')
        ->toContain('window.open(`https://example.com`, `_blank`, `noopener`)');

    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'desktop',
    ]);

    expect(open_link_native_aware('https://example.com'))
        ->toBe('window.open(`https://example.com`, `_blank`, `noopener`)');
});

it('detects platform flags for web and native contexts', function () {
    config([
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
    ]);

    expect(is_platform('web'))->toBeTrue()
        ->and(is_platform('native'))->toBeFalse()
        ->and(is_platform('desktop'))->toBeFalse()
        ->and(is_platform('mobile'))->toBeFalse();

    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
    ]);

    expect(is_platform('web'))->toBeFalse()
        ->and(is_platform('native'))->toBeTrue()
        ->and(is_platform('mobile'))->toBeTrue()
        ->and(is_platform('android'))->toBeTrue();
});

it('detects the native bootstrap runtime from the platform env', function () {
    $previousPlatform = getenv('NATIVEPHP_PLATFORM');
    $previousRunning = getenv('NATIVEPHP_RUNNING');

    putenv('NATIVEPHP_PLATFORM');
    putenv('NATIVEPHP_RUNNING');
    expect(is_native_bootstrap_runtime())->toBeFalse();

    putenv('NATIVEPHP_RUNNING=true');
    expect(is_native_bootstrap_runtime())->toBeTrue();

    putenv('NATIVEPHP_RUNNING');
    putenv('NATIVEPHP_PLATFORM=android');
    expect(is_native_bootstrap_runtime())->toBeTrue();

    if ($previousPlatform === false) {
        putenv('NATIVEPHP_PLATFORM');
    } else {
        putenv('NATIVEPHP_PLATFORM='.$previousPlatform);
    }

    if ($previousRunning === false) {
        putenv('NATIVEPHP_RUNNING');

        return;
    }

    putenv('NATIVEPHP_RUNNING='.$previousRunning);
});

it('normalizes missing socket ids before broadcasting realtime events', function () {
    $event = new UserRealtimeEvent(123, 'dataSynced', null, 'undefined');

    expect($event->socket)->toBeNull();
});

it('keeps auth changes working when realtime broadcasting is down', function () {
    config(['nativephp-internal.running' => false]);

    $originalEvents = app('events');
    $throwingEvents = new class(app()) extends Dispatcher
    {
        public function dispatch($event, $payload = [], $halt = false)
        {
            if ($event instanceof UserRealtimeEvent) {
                throw new RuntimeException('broadcast unavailable');
            }

            return parent::dispatch($event, $payload, $halt);
        }
    };

    app()->instance('events', $throwingEvents);

    try {
        $user = User::factory()->create(['telegram_id' => 321]);
        Auth::login($user);

        livewire(AuthButton::class)->call('pushUserData', ['athkar-progress-v1' => 'value']);

        expect($user->fresh()->synced_data)->toBe(['athkar-progress-v1' => 'value']);
    } finally {
        app()->instance('events', $originalEvents);
    }
});
