<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogRepeatedUnmatchedRouteHits
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->trackUnmatchedRouteAttempt($request, $response);

        return $response;
    }

    private function trackUnmatchedRouteAttempt(Request $request, Response $response): void
    {
        if (! $this->shouldTrackUnmatchedRouteAttempts()) {
            return;
        }

        if ($response->getStatusCode() !== Response::HTTP_NOT_FOUND) {
            return;
        }

        $ipAddress = trim((string) $request->ip());

        if ($ipAddress === '') {
            return;
        }

        $windowSeconds = max(1, (int) config('app.custom.security.unmatched_routes.window_seconds', 900));
        $alertThreshold = max(1, (int) config('app.custom.security.unmatched_routes.alert_threshold', 25));
        $alertRepeatEvery = max(1, (int) config('app.custom.security.unmatched_routes.alert_repeat_every', 25));

        $attempts = $this->incrementUnmatchedRouteAttempts($ipAddress, $windowSeconds);
        if ($attempts === null) {
            return;
        }

        Context::add('unmatched_route', [
            'ip' => $ipAddress,
            'attempts_in_window' => $attempts,
            'window_seconds' => $windowSeconds,
        ]);

        if ($attempts < $alertThreshold) {
            return;
        }

        if (($attempts - $alertThreshold) % $alertRepeatEvery !== 0) {
            return;
        }

        Log::warning('Repeated unmatched route requests detected.', [
            'ip' => $ipAddress,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'url' => $request->fullUrl(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255),
            'attempts_in_window' => $attempts,
            'window_seconds' => $windowSeconds,
            'alert_threshold' => $alertThreshold,
            'alert_repeat_every' => $alertRepeatEvery,
        ]);
    }

    private function rateLimitKey(string $ipAddress): string
    {
        return 'unmatched-route-ip:'.hash('sha256', $ipAddress);
    }

    private function incrementUnmatchedRouteAttempts(string $ipAddress, int $windowSeconds): ?int
    {
        try {
            return (int) RateLimiter::increment(
                key: $this->rateLimitKey($ipAddress),
                decaySeconds: $windowSeconds,
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }
    }

    private function shouldTrackUnmatchedRouteAttempts(): bool
    {
        if (config('app.env') !== 'production') {
            return false;
        }

        return is_platform('web');
    }
}
