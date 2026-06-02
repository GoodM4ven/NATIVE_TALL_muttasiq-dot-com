<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\get;

it('returns 404 for unmatched routes and logs repeated production web hits at threshold', function () {
    get('/route-does-not-exist')->assertNotFound();

    config([
        'app.env' => 'production',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
        'app.custom.security.unmatched_routes.window_seconds' => 300,
        'app.custom.security.unmatched_routes.alert_threshold' => 3,
        'app.custom.security.unmatched_routes.alert_repeat_every' => 3,
    ]);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Repeated unmatched route requests detected.'
                && $context['ip'] === '203.0.113.10'
                && $context['attempts_in_window'] === 3
                && $context['path'] === '/missing-three';
        });

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->get('/missing-one')->assertNotFound();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->get('/missing-two')->assertNotFound();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->get('/missing-three')->assertNotFound();
});

it('skips unmatched-route alert logging outside production or on non-web runtimes', function () {
    config([
        'app.env' => 'local',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
        'app.custom.security.unmatched_routes.window_seconds' => 300,
        'app.custom.security.unmatched_routes.alert_threshold' => 1,
        'app.custom.security.unmatched_routes.alert_repeat_every' => 1,
    ]);

    Log::spy();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])->get('/ignored-local')->assertNotFound();

    Log::shouldNotHaveReceived('warning');

    config([
        'app.env' => 'production',
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'app.custom.security.unmatched_routes.window_seconds' => 300,
        'app.custom.security.unmatched_routes.alert_threshold' => 1,
        'app.custom.security.unmatched_routes.alert_repeat_every' => 1,
    ]);

    Log::spy();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.12'])->get('/ignored-non-web')->assertNotFound();

    Log::shouldNotHaveReceived('warning');
});

it('returns 404 when unmatched-route tracking cannot reach the cache backend', function () {
    config([
        'app.env' => 'production',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
        'app.custom.security.unmatched_routes.window_seconds' => 300,
        'app.custom.security.unmatched_routes.alert_threshold' => 1,
        'app.custom.security.unmatched_routes.alert_repeat_every' => 1,
    ]);

    RateLimiter::shouldReceive('increment')
        ->once()
        ->andThrow(new RuntimeException('read error on connection to redis'));

    Log::spy();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.13'])
        ->get('/missing-cache-unavailable')
        ->assertNotFound();

    Log::shouldNotHaveReceived('warning');
});
