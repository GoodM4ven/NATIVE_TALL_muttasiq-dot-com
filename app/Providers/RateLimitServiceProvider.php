<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->rateLimitAthkar();
        $this->rateLimitSettings();
        $this->rateLimitQuranSnapshot();
        $this->rateLimitJsErrorReports();
        $this->rateLimitVisitMetrics();
    }

    private function rateLimitAthkar(): void
    {
        RateLimiter::for('athkar', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    private function rateLimitSettings(): void
    {
        RateLimiter::for('settings', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    private function rateLimitQuranSnapshot(): void
    {
        RateLimiter::for('quran-snapshot', function (Request $request): Limit {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });
    }

    private function rateLimitJsErrorReports(): void
    {
        RateLimiter::for('js-error-reports', function (Request $request): Limit {
            $clientFingerprint = trim((string) $request->input('context.user_agent', ''));
            $throttleKey = hash('sha256', $request->ip().'|'.$clientFingerprint);

            return Limit::perMinute(12)->by($throttleKey);
        });
    }

    private function rateLimitVisitMetrics(): void
    {
        RateLimiter::for('visit-metrics', function (Request $request): Limit {
            $throttleKey = hash('sha256', $request->ip().'|'.trim((string) $request->userAgent()));

            return Limit::perMinute(60)->by($throttleKey);
        });
    }
}
