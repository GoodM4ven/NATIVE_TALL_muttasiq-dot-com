<?php

declare(strict_types=1);

use App\Events\UserRealtimeEvent;
use App\Livewire\AuthButton;
use App\Models\User;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
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

it('normalizes socket ids before using them in realtime sync helpers', function () {
    expect(normalize_socket_id('undefined'))->toBeNull()
        ->and(normalize_socket_id('null'))->toBeNull()
        ->and(normalize_socket_id('  123|456  '))->toBe('123|456');
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

it('returns the authoritative settings snapshot for native sync pulls', function () {
    $user = User::factory()->create([
        'synced_data' => ['athkar-progress-v1' => 'remote'],
    ]);

    Sanctum::actingAs($user);

    getJson(route('api.native-sync.snapshot'))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('synced_data.athkar-progress-v1', 'remote');
});

it('pulls the server snapshot into the native mirror', function () {
    config(['nativephp-internal.running' => true]);

    Http::fake([
        '*/api/native-sync/snapshot' => Http::response([
            'ok' => true,
            'synced_data' => [
                'athkar-progress-v1' => 'remote',
                'quran-reader-last-page-v1' => '7',
            ],
            'synced_data_updated_at' => '2026-06-27T00:00:00Z',
        ]),
    ]);

    $user = User::factory()->create([
        'native_api_token' => 'dev-token',
        'synced_data' => ['athkar-progress-v1' => 'local'],
    ]);

    Auth::login($user);

    postJson(route('native.sync.pull'))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('synced_data.athkar-progress-v1', 'remote');

    expect($user->fresh()->synced_data)
        ->toBe([
            'athkar-progress-v1' => 'remote',
            'quran-reader-last-page-v1' => '7',
        ]);
});
