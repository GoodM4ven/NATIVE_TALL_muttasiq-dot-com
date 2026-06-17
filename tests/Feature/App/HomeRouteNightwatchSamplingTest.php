<?php

declare(strict_types=1);

use App\Livewire\WebHomeViewTracker;
use App\Services\Monitoring\WebHomeActivityTracker;
use App\Services\Native\NativeVisitMetricsRelay;
use App\Services\Support\Enums\ViewName;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\Network;

use function Pest\Livewire\livewire;

beforeEach(function () {
    config([
        'app.custom.security.web_home_metrics.cache_store' => 'array',
    ]);
    Cache::store('array')->flush();
    Cache::flush();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-12 10:15:00'));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('tracks hits and unique visitors for web requests when metrics are enabled', function () {
    config([
        'app.custom.security.web_home_metrics.enabled' => true,
        'app.custom.security.web_home_metrics.cache_store' => 'array',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
    ]);

    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_USER_AGENT' => 'Agent A',
    ])->get('/')->assertSuccessful();

    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_USER_AGENT' => 'Agent A',
    ])->get('/')->assertSuccessful();

    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.11',
        'HTTP_USER_AGENT' => 'Agent B',
    ])->get('/')->assertSuccessful();

    $tracker = app(WebHomeActivityTracker::class);
    $today = $tracker->todaySummary();
    $last24Hours = $tracker->last24HoursSummary();
    $series = $tracker->dailySeries(days: 1);

    expect($today)->toBe([
        'hits' => 3,
        'unique_visitors' => 2,
    ])->and($last24Hours)->toBe([
        'hits' => 3,
        'unique_visitors' => 2,
    ])->and($series['hits'])->toBe([3])
        ->and($series['unique_visitors'])->toBe([2]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-12 10:17:30'));

    expect($tracker->todaySummary())->toBe([
        'hits' => 3,
        'unique_visitors' => 2,
    ]);
});

it('skips web home metrics tracking when disabled or when request platform is non-web', function () {
    config([
        'app.custom.security.web_home_metrics.enabled' => false,
        'app.custom.security.web_home_metrics.cache_store' => 'array',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
    ]);

    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.20',
        'HTTP_USER_AGENT' => 'Agent A',
    ])->get('/')->assertSuccessful();

    expect(app(WebHomeActivityTracker::class)->todaySummary())->toBe([
        'hits' => 0,
        'unique_visitors' => 0,
    ]);

    config([
        'app.custom.security.web_home_metrics.enabled' => true,
        'app.custom.security.web_home_metrics.cache_store' => 'array',
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
    ]);

    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.20',
        'HTTP_USER_AGENT' => 'Agent A',
    ])->get('/')->assertSuccessful();

    expect(app(WebHomeActivityTracker::class)->todaySummary())->toBe([
        'hits' => 0,
        'unique_visitors' => 0,
    ]);
});

it('persists web home metrics in the configured cache store even when default cache is ephemeral', function () {
    $metricsCachePath = storage_path('framework/cache/web-home-metrics-tests');
    if (! is_dir($metricsCachePath)) {
        mkdir($metricsCachePath, 0777, true);
    }

    config([
        'cache.default' => 'array',
        'cache.stores.file.path' => $metricsCachePath,
        'cache.stores.file.lock_path' => $metricsCachePath,
        'app.custom.security.web_home_metrics.enabled' => true,
        'app.custom.security.web_home_metrics.cache_store' => 'file',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
    ]);

    Cache::store('file')->flush();

    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.40',
        'HTTP_USER_AGENT' => 'Agent C',
    ])->get('/')->assertSuccessful();

    expect(app(WebHomeActivityTracker::class)->todaySummary())->toBe([
        'hits' => 1,
        'unique_visitors' => 1,
    ]);

    $this->refreshApplication();

    config([
        'cache.default' => 'array',
        'cache.stores.file.path' => $metricsCachePath,
        'cache.stores.file.lock_path' => $metricsCachePath,
        'app.custom.security.web_home_metrics.cache_store' => 'file',
    ]);

    expect(app(WebHomeActivityTracker::class)->todaySummary())->toBe([
        'hits' => 1,
        'unique_visitors' => 1,
    ]);

    Cache::store('file')->flush();
});

it('tracks app-scoped metrics from Livewire view switches and counts main-menu visits independently', function () {
    config([
        'app.custom.security.web_home_metrics.enabled' => true,
        'app.custom.security.web_home_metrics.cache_store' => 'array',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
    ]);

    // Web page load records a home visit via middleware
    $this->withServerVariables([
        'REMOTE_ADDR' => '203.0.113.77',
        'HTTP_USER_AGENT' => 'Scoped Agent',
    ])->get('/')->assertSuccessful();

    // SPA navigation: athkar → same athkar sub-view (deduped) → main-menu (second home hit) → quran → same quran sub-view (deduped)
    livewire(WebHomeViewTracker::class)
        ->call('trackAppView', ViewName::AthkarAppGate->value)
        ->call('trackAppView', ViewName::AthkarAppSabah->value)
        ->call('trackAppView', ViewName::MainMenu->value)
        ->call('trackAppView', ViewName::QuranAppTilawa->value)
        ->call('trackAppView', ViewName::QuranAppHifth->value);

    $tracker = app(WebHomeActivityTracker::class);

    // Home: 2 hits (middleware load + return to main-menu), 2 unique visitors (different test fingerprints)
    expect($tracker->todaySummary())->toBe([
        'hits' => 2,
        'unique_visitors' => 2,
    ])
    // Athkar: 1 hit (gate + sub-view deduped to same context), 1 unique
        ->and($tracker->todaySummary(WebHomeActivityTracker::CONTEXT_ATHKAR_GATE))->toBe([
            'hits' => 1,
            'unique_visitors' => 1,
        ])
    // Quran: 1 hit (tilawa + hifth deduped to same context), 1 unique
        ->and($tracker->todaySummary(WebHomeActivityTracker::CONTEXT_QURAN_GATE))->toBe([
            'hits' => 1,
            'unique_visitors' => 1,
        ])
        ->and($tracker->dailySeries(1, WebHomeActivityTracker::CONTEXT_ATHKAR_GATE)['hits'])->toBe([1])
        ->and($tracker->dailySeries(1, WebHomeActivityTracker::CONTEXT_QURAN_GATE)['hits'])->toBe([1]);
});

it('records all three visit types independently via the api endpoint', function () {
    config([
        'app.custom.security.web_home_metrics.enabled' => true,
        'app.custom.security.web_home_metrics.cache_store' => 'array',
        'nativephp-internal.running' => false,
        'nativephp-internal.platform' => null,
    ]);

    $this->postJson('/api/visit-metrics', [
        'view' => ViewName::MainMenu->value,
    ])->assertOk();

    $this->postJson('/api/visit-metrics', [
        'view' => ViewName::QuranAppGate->value,
    ])->assertOk();

    $this->postJson('/api/visit-metrics', [
        'view' => ViewName::AthkarAppGate->value,
    ])->assertOk();

    $tracker = app(WebHomeActivityTracker::class);

    // All three contexts record independently — no replacement or suppression
    expect($tracker->todaySummary())->toBe([
        'hits' => 1,
        'unique_visitors' => 1,
    ])->and($tracker->todaySummary(WebHomeActivityTracker::CONTEXT_QURAN_GATE))->toBe([
        'hits' => 1,
        'unique_visitors' => 1,
    ])->and($tracker->todaySummary(WebHomeActivityTracker::CONTEXT_ATHKAR_GATE))->toBe([
        'hits' => 1,
        'unique_visitors' => 1,
    ]);
});

it('skips native visit metrics relay when the device is offline', function () {
    config([
        'app.custom.native_end_points.visit_metrics' => 'https://example.test/api/visit-metrics',
        'app.custom.native_end_points.retries' => 4,
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
    ]);

    Http::fake();
    Network::shouldReceive('status')
        ->once()
        ->andReturn((object) [
            'connected' => false,
            'type' => 'unknown',
        ]);

    $relay = app(NativeVisitMetricsRelay::class);
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Native Relay Agent',
    ]);

    expect($relay->relay(ViewName::QuranAppGate->value, $request))->toBeFalse();
    Http::assertNothingSent();
});

it('relays native visit metrics when the device is online', function () {
    config([
        'app.custom.native_end_points.visit_metrics' => 'https://example.test/api/visit-metrics',
        'app.custom.native_end_points.retries' => 4,
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
    ]);

    Http::fake([
        'https://example.test/api/visit-metrics' => Http::response([
            'message' => 'ok',
        ]),
    ]);

    Network::shouldReceive('status')
        ->once()
        ->andReturn((object) [
            'connected' => true,
            'type' => 'wifi',
        ]);

    $relay = app(NativeVisitMetricsRelay::class);
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Native Relay Agent',
    ]);

    expect($relay->relay(ViewName::AthkarAppGate->value, $request))->toBeTrue();

    Http::assertSent(function (HttpRequest $request): bool {
        $data = $request->data();
        $userAgent = $request->header('User-Agent')[0] ?? '';

        return $request->url() === 'https://example.test/api/visit-metrics'
            && ($data['view'] ?? null) === ViewName::AthkarAppGate->value
            && $userAgent === 'Native Relay Agent';
    });
});
