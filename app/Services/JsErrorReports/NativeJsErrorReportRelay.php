<?php

declare(strict_types=1);

namespace App\Services\JsErrorReports;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class NativeJsErrorReportRelay
{
    public function __construct(
        private HttpFactory $http,
    ) {}

    /**
     * @param  array{
     *     user_note: string,
     *     errors: array<int, array{
     *         type: string,
     *         time?: string|null,
     *         message: string,
     *         source?: string|null,
     *         line?: int|null,
     *         column?: int|null,
     *         stack?: string|null
     *     }>,
     *     context?: array{
     *         url?: string|null,
     *         user_agent?: string|null,
     *         language?: string|null,
     *         platform?: string|null,
     *         breakpoint?: string|null
     *     }
     * } $payload
     *
     * @throws RequestException
     */
    public function relay(array $payload): bool
    {
        $url = $this->resolveApiUrl();

        if ($url === null) {
            return false;
        }

        $timeoutInSeconds = $this->resolveTimeoutInSeconds();
        $connectTimeoutInSeconds = min(2, $timeoutInSeconds);

        $response = $this->http
            ->acceptJson()
            ->asJson()
            ->connectTimeout($connectTimeoutInSeconds)
            ->timeout($timeoutInSeconds)
            ->post($url, $payload);

        $response->throw();

        return true;
    }

    private function resolveApiUrl(): ?string
    {
        $configuredEndpoint = (string) config('app.custom.native_end_points.js_error_reports', '');

        if ($configuredEndpoint === '') {
            return null;
        }

        if (str_starts_with($configuredEndpoint, 'https://') || str_starts_with($configuredEndpoint, 'http://')) {
            return $configuredEndpoint;
        }

        $applicationUrl = rtrim((string) config('app.url'), '/');
        $applicationUrlScheme = parse_url($applicationUrl, PHP_URL_SCHEME);

        if (! is_string($applicationUrlScheme) || ! in_array(strtolower($applicationUrlScheme), ['http', 'https'], true)) {
            Log::warning('Skipping native JS error relay because APP_URL does not use an HTTP scheme.', [
                'app_url' => $applicationUrl,
            ]);

            return null;
        }

        return $applicationUrl.route('api.js-error-reports.store', [], false);
    }

    private function resolveTimeoutInSeconds(): int
    {
        $configuredTimeout = (int) config('app.custom.native_end_points.retries', 8);

        return max(2, min($configuredTimeout, 8));
    }
}
