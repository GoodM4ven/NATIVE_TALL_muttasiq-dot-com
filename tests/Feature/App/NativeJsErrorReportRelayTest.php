<?php

declare(strict_types=1);

use App\Services\JsErrorReports\NativeJsErrorReportRelay;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

function validNativeJsErrorRelayPayload(): array
{
    return [
        'user_note' => 'Pressed the athkar manager card and the app crashed.',
        'errors' => [[
            'type' => 'error',
            'time' => now()->toIso8601String(),
            'message' => 'Synthetic native relay failure',
            'source' => 'http://127.0.0.1/build/assets/app.js',
            'line' => 14,
            'column' => 3,
            'stack' => 'TypeError: relay test',
        ]],
        'context' => [
            'url' => 'http://127.0.0.1/#athkar-app-gate',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 16)',
            'language' => 'ar',
            'platform' => 'Native - Android',
            'breakpoint' => null,
        ],
    ];
}

it('relays native js error reports to configured absolute and relative API endpoints', function () {
    config([
        'app.url' => 'http://muttasiq.dev.localhost',
        'app.custom.native_end_points.retries' => 2,
        'app.custom.native_end_points.js_error_reports' => 'https://muttasiq.com/api/js-error-reports',
    ]);

    Http::fake([
        'https://muttasiq.com/api/js-error-reports' => Http::response([
            'id' => 51,
            'message' => 'ok',
        ], 201),
    ]);

    $relay = app(NativeJsErrorReportRelay::class);

    expect($relay->relay(validNativeJsErrorRelayPayload()))->toBeTrue();

    Http::assertSent(function (HttpRequest $request): bool {
        return $request->url() === 'https://muttasiq.com/api/js-error-reports'
            && $request['user_note'] === 'Pressed the athkar manager card and the app crashed.';
    });

    config([
        'app.url' => 'http://muttasiq.dev.localhost',
        'app.custom.native_end_points.js_error_reports' => 'js-error-reports',
    ]);

    Http::fake([
        'http://muttasiq.dev.localhost/api/js-error-reports' => Http::response([
            'id' => 52,
            'message' => 'ok',
        ], 201),
    ]);

    expect($relay->relay(validNativeJsErrorRelayPayload()))->toBeTrue();

    Http::assertSent(function (HttpRequest $request): bool {
        return $request->url() === 'http://muttasiq.dev.localhost/api/js-error-reports';
    });
});

it('skips native js error relaying when relative endpoints cannot be resolved safely', function () {
    config([
        'app.url' => 'php://127.0.0.1',
        'app.custom.native_end_points.js_error_reports' => 'js-error-reports',
    ]);

    Http::fake();

    $relay = app(NativeJsErrorReportRelay::class);

    expect($relay->relay(validNativeJsErrorRelayPayload()))->toBeFalse();

    Http::assertNothingSent();
});
