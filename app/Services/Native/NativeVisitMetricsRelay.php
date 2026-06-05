<?php

declare(strict_types=1);

namespace App\Services\Native;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Native\Mobile\Facades\Network;

class NativeVisitMetricsRelay
{
    public function __construct(
        private HttpFactory $http,
    ) {}

    public function relay(string $view, Request $request): bool
    {
        try {
            $status = Network::status();
        } catch (\Throwable $exception) {
            return false;
        }

        if (! is_object($status) || ! (bool) ($status->connected ?? false)) {
            return false;
        }

        $url = $this->resolveApiUrl();

        if ($url === null) {
            return false;
        }

        $timeoutInSeconds = $this->resolveTimeoutInSeconds();
        $connectTimeoutInSeconds = min(2, $timeoutInSeconds);

        try {
            $response = $this->http
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'User-Agent' => $this->resolveUserAgent($request),
                ])
                ->connectTimeout($connectTimeoutInSeconds)
                ->timeout($timeoutInSeconds)
                ->post($url, [
                    'view' => $view,
                ]);

            $response->throw();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Native visit metrics relay failed.', [
                'message' => $exception->getMessage(),
                'url' => $url,
                'view' => $view,
            ]);

            return false;
        }
    }

    private function resolveApiUrl(): ?string
    {
        $configuredEndpoint = trim((string) config('app.custom.native_end_points.visit_metrics', ''));

        if ($this->isValidHttpUrl($configuredEndpoint)) {
            return $configuredEndpoint;
        }

        $applicationUrl = rtrim((string) config('app.url'), '/');
        $applicationUrlScheme = parse_url($applicationUrl, PHP_URL_SCHEME);

        if (! is_string($applicationUrlScheme) || ! in_array(strtolower($applicationUrlScheme), ['http', 'https'], true)) {
            Log::warning('Skipping native visit metrics relay because APP_URL does not use an HTTP scheme.', [
                'app_url' => $applicationUrl,
            ]);

            return null;
        }

        return $applicationUrl.route('api.visit-metrics.store', [], false);
    }

    private function resolveTimeoutInSeconds(): int
    {
        $configuredTimeout = (int) config('app.custom.native_end_points.retries', 8);

        return max(2, min($configuredTimeout, 8));
    }

    private function resolveUserAgent(Request $request): string
    {
        $userAgent = trim((string) $request->userAgent());

        if ($userAgent !== '') {
            return $userAgent;
        }

        return 'Muttasiq Native Visit Relay';
    }

    private function isValidHttpUrl(string $url): bool
    {
        return $url !== '' && preg_match('/^https?:\/\//i', $url) === 1;
    }
}
